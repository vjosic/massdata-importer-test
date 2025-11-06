<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use App\Models\Audit;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ImportedDataController extends Controller
{
    /**
     * Show main imported data page with all datasets
     */
    public function index(Request $request)
    {
        $importConfigs = config('imports');
        $activeDataset = $request->get('dataset', array_key_first($importConfigs));
        
        // Check if user has permission to view this dataset
        $config = $importConfigs[$activeDataset] ?? null;
        if (!$config) {
            abort(404, 'Dataset not found');
        }
        
        return view('admin.imported-data.index', compact('importConfigs', 'activeDataset'));
    }
    
    /**
     * Show specific dataset with search and pagination
     */
    public function dataset(Request $request, $dataset)
    {
        $importConfigs = config('imports');
        $config = $importConfigs[$dataset] ?? null;
        
        if (!$config) {
            abort(404, 'Dataset not found');
        }
        
        // Get all tables for this dataset
        $tables = $this->getTablesFromDataset($dataset);
        $activeTable = $request->get('table', array_key_first($tables));
        
        // Validate active table
        if (!array_key_exists($activeTable, $tables)) {
            $activeTable = array_key_first($tables);
        }
        
        $tableName = $tables[$activeTable]['table'];
        
        // Get search query
        $search = $request->get('search', '');
        
        // Build query
        $query = DB::table($tableName);
        
        // Apply search filter
        if ($search) {
            $this->applySearchFilter($query, $tableName, $search);
        }
        
        // Paginate results
        $data = $query->paginate(25)->appends($request->query());
        
        // Get headers for display
        $headers = $this->getDisplayHeaders($dataset, $activeTable);
        
        // Check if user can delete records
        $canDelete = Gate::allows($config['permission_required']);
        
        return view('admin.imported-data.dataset', compact(
            'dataset', 
            'config', 
            'data', 
            'headers', 
            'search', 
            'canDelete',
            'importConfigs',
            'tables',
            'activeTable'
        ));
    }
    
    /**
     * Export dataset to Excel
     */
    public function export(Request $request, $dataset)
    {
        $importConfigs = config('imports');
        $config = $importConfigs[$dataset] ?? null;
        
        if (!$config) {
            abort(404, 'Dataset not found');
        }
        
        $tableName = $this->getTableNameFromDataset($dataset);
        $search = $request->get('search', '');
        
        // Build query
        $query = DB::table($tableName);
        
        // Apply search filter
        if ($search) {
            $this->applySearchFilter($query, $tableName, $search);
        }
        
        $data = $query->get();
        $headers = $this->getDisplayHeaders($dataset);
        
        // Create Excel file
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set headers
        $column = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($column . '1', $header['label']);
            $column++;
        }
        
        // Set data
        $row = 2;
        foreach ($data as $record) {
            $column = 'A';
            foreach ($headers as $field => $header) {
                $value = $record->{$field} ?? '';
                $sheet->setCellValue($column . $row, $value);
                $column++;
            }
            $row++;
        }
        
        // Generate file
        $writer = new Xlsx($spreadsheet);
        $filename = $dataset . '_export_' . date('Y-m-d_H-i-s') . '.xlsx';
        $tempPath = storage_path('app/temp/' . $filename);
        
        // Ensure temp directory exists
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }
        
        $writer->save($tempPath);
        
        return response()->download($tempPath, $filename)->deleteFileAfterSend();
    }
    
    /**
     * Delete a record from dataset
     */
    public function delete(Request $request, $dataset, $id)
    {
        try {
            $importConfigs = config('imports');
            $config = $importConfigs[$dataset] ?? null;
            
            if (!$config) {
                return response()->json(['success' => false, 'message' => 'Dataset not found'], 404);
            }
            
            // Check permission
            if (!Gate::allows($config['permission_required'])) {
                return response()->json(['success' => false, 'message' => 'Unauthorized to delete from this dataset'], 403);
            }
            
            $tableName = $this->getTableNameFromDataset($dataset);
            
            // Check if record exists before deletion
            $record = DB::table($tableName)->where('id', $id)->first();
            if (!$record) {
                return response()->json(['success' => false, 'message' => 'Record not found'], 404);
            }
            
            // Find and delete record
            $deleted = DB::table($tableName)->where('id', $id)->delete();
            
            if ($deleted) {
                // Log audit trail
                Audit::create([
                    'import_id' => null,
                    'table' => $tableName,
                    'row_pk' => $id,
                    'column' => 'delete_action',
                    'old_value' => 'record_deleted',
                    'new_value' => 'deleted_by_user_' . Auth::id()
                ]);
                
                return response()->json(['success' => true, 'message' => 'Record deleted successfully']);
            }
            
            return response()->json(['success' => false, 'message' => 'Failed to delete record'], 500);
            
        } catch (\Exception $e) {
            Log::error('Error deleting record', [
                'dataset' => $dataset,
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['success' => false, 'message' => 'Error occurred while deleting record: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Get audit trail for a record
     */
    public function audits($dataset, $id)
    {
        $tableName = $this->getTableNameFromDataset($dataset);
        
        $audits = Audit::where('table', $tableName)
                      ->where('row_pk', $id)
                      ->orderBy('created_at', 'desc')
                      ->get();
        
        return response()->json(['audits' => $audits]);
    }
    
    /**
     * Get table mapping for dataset
     */
    private function getTableNameFromDataset($dataset)
    {
        // Map dataset to table names
        $tableMap = [
            'orders' => 'orders',
            'inventory' => 'stock_levels', // Default table for backward compatibility
            'suppliers' => 'suppliers'
        ];
        
        return $tableMap[$dataset] ?? $dataset;
    }
    
    /**
     * Get all tables available for a dataset
     */
    private function getTablesFromDataset($dataset)
    {
        $config = config("imports.{$dataset}");
        $tables = [];
        
        if (isset($config['files'])) {
            // Dataset with multiple files (like inventory)
            foreach ($config['files'] as $fileKey => $fileConfig) {
                $tableName = $this->getTableNameFromFileKey($fileKey);
                $tables[$tableName] = [
                    'table' => $tableName,
                    'label' => $fileConfig['label'],
                    'file_key' => $fileKey
                ];
            }
        } else {
            // Single table dataset
            $tableName = $this->getTableNameFromDataset($dataset);
            $tables[$tableName] = [
                'table' => $tableName,
                'label' => $config['label'],
                'file_key' => $dataset
            ];
        }
        
        return $tables;
    }
    
    /**
     * Get table name from file key
     */
    private function getTableNameFromFileKey($fileKey)
    {
        // Map file keys to actual table names
        $tableMap = [
            'orders_file' => 'orders',
            'customers_file' => 'customers', 
            'tracking_file' => 'tracking',
            'products_file' => 'products',
            'stock_levels_file' => 'stock_levels',
            'suppliers_file' => 'suppliers'
        ];
        
        return $tableMap[$fileKey] ?? str_replace('_file', '', $fileKey);
    }
    
    /**
     * Get display headers for dataset
     */
    private function getDisplayHeaders($dataset, $activeTable = null)
    {
        $config = config("imports.{$dataset}");
        $headers = [];
        
        if (isset($config['files']) && $activeTable) {
            // Find the corresponding file configuration for the active table
            $tables = $this->getTablesFromDataset($dataset);
            $fileKey = $tables[$activeTable]['file_key'] ?? null;
            
            if ($fileKey && isset($config['files'][$fileKey])) {
                $fileConfig = $config['files'][$fileKey];
                
                foreach ($fileConfig['headers_to_db'] as $field => $fieldConfig) {
                    $headers[$field] = [
                        'label' => $fieldConfig['label'],
                        'type' => $fieldConfig['type'] ?? 'string'
                    ];
                }
            }
        } else {
            // Fallback for single table datasets or when no active table specified
            if (isset($config['files'])) {
                $firstFile = array_values($config['files'])[0];
            } else {
                $firstFile = $config;
            }
            
            if (isset($firstFile['headers_to_db'])) {
                foreach ($firstFile['headers_to_db'] as $field => $fieldConfig) {
                    $headers[$field] = [
                        'label' => $fieldConfig['label'],
                        'type' => $fieldConfig['type'] ?? 'string'
                    ];
                }
            }
        }
        
        // Add system fields
        $headers['id'] = ['label' => 'ID', 'type' => 'integer'];
        $headers['created_at'] = ['label' => 'Created At', 'type' => 'datetime'];
        $headers['updated_at'] = ['label' => 'Updated At', 'type' => 'datetime'];
        
        return $headers;
    }
    
    /**
     * Apply search filter to query
     */
    private function applySearchFilter($query, $tableName, $search)
    {
        // Get all columns for the table
        $columns = DB::getSchemaBuilder()->getColumnListing($tableName);
        
        $query->where(function($q) use ($columns, $search) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'LIKE', "%{$search}%");
            }
        });
    }
}
