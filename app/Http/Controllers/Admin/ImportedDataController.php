<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;
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
        
        // Get the table name from first file config
        $tableName = $this->getTableNameFromDataset($dataset);
        
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
        $headers = $this->getDisplayHeaders($dataset);
        
        // Check if user can delete records
        $canDelete = Gate::allows($config['permission_required']);
        
        return view('admin.imported-data.dataset', compact(
            'dataset', 
            'config', 
            'data', 
            'headers', 
            'search', 
            'canDelete',
            'importConfigs'
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
        $importConfigs = config('imports');
        $config = $importConfigs[$dataset] ?? null;
        
        if (!$config) {
            abort(404, 'Dataset not found');
        }
        
        // Check permission
        if (!Gate::allows($config['permission_required'])) {
            abort(403, 'Unauthorized to delete from this dataset');
        }
        
        $tableName = $this->getTableNameFromDataset($dataset);
        
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
        
        return response()->json(['success' => false, 'message' => 'Record not found'], 404);
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
     * Get table name from dataset configuration
     */
    private function getTableNameFromDataset($dataset)
    {
        // Map dataset to table names
        $tableMap = [
            'orders' => 'orders',
            'inventory' => 'stock_levels',
            'suppliers' => 'suppliers'
        ];
        
        return $tableMap[$dataset] ?? $dataset;
    }
    
    /**
     * Get display headers for dataset
     */
    private function getDisplayHeaders($dataset)
    {
        $config = config("imports.{$dataset}");
        $headers = [];
        
        // Get headers from first file configuration
        $firstFile = array_values($config['files'])[0];
        
        foreach ($firstFile['headers_to_db'] as $field => $fieldConfig) {
            $headers[$field] = [
                'label' => $fieldConfig['label'],
                'type' => $fieldConfig['type'] ?? 'string'
            ];
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
