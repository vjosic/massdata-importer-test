<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class ImportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the import page
     */
    public function index()
    {
        // Get all import configurations
        $importConfigs = config('imports');
        
        // Filter configs based on user permissions
        $availableImports = [];
        foreach ($importConfigs as $key => $config) {
            if (Gate::allows($config['permission_required'])) {
                $availableImports[$key] = $config;
            }
        }

        // If user has no import permissions, deny access
        if (empty($availableImports)) {
            abort(403, 'You do not have permission to access any import types.');
        }

        return view('admin.import.index', compact('availableImports'));
    }

    /**
     * Get required headers for specific import type via AJAX
     */
    public function getRequiredHeaders(Request $request)
    {
        $importType = $request->get('import_type');
        $importConfig = config("imports.{$importType}");

        if (!$importConfig || !Gate::allows($importConfig['permission_required'])) {
            return response()->json(['error' => 'Invalid import type or insufficient permissions'], 403);
        }

        $requiredHeaders = [];
        foreach ($importConfig['files'] as $fileKey => $fileConfig) {
            $headers = [];
            foreach ($fileConfig['headers_to_db'] as $header => $config) {
                $headers[] = [
                    'header' => $header,
                    'label' => $config['label'],
                    'required' => in_array('required', $config['validation'])
                ];
            }
            $requiredHeaders[$fileKey] = [
                'label' => $fileConfig['label'],
                'headers' => $headers
            ];
        }

        return response()->json($requiredHeaders);
    }

    /**
     * Get import configuration for AJAX requests
     */
    public function getConfig(Request $request)
    {
        $importType = $request->get('type');
        
        if (!$importType) {
            return response()->json(['error' => 'Import type is required'], 400);
        }
        
        $importConfig = config("imports.{$importType}");

        if (!$importConfig || !Gate::allows($importConfig['permission_required'])) {
            return response()->json(['error' => 'Invalid import type or insufficient permissions'], 403);
        }

        // Prepare headers for display
        $headers = [];
        if (isset($importConfig['files'])) {
            foreach ($importConfig['files'] as $fileKey => $fileConfig) {
                if (isset($fileConfig['headers_to_db'])) {
                    foreach ($fileConfig['headers_to_db'] as $header => $config) {
                        $headers[] = [
                            'name' => $header,
                            'description' => $config['label'] ?? '',
                            'required' => in_array('required', $config['validation'] ?? [])
                        ];
                    }
                }
            }
        }

        return response()->json([
            'config' => [
                'files' => $importConfig['files'] ?? [],
                'headers' => $headers
            ]
        ]);
    }

    /**
     * Handle file upload and start import process
     */
    public function upload(Request $request)
    {
        try {
            // Basic validation
            $request->validate([
                'import_type' => 'required|string'
            ]);

            $importType = $request->get('import_type');
            $importConfig = config("imports.{$importType}");

            // 1. Validate import type and permissions
        if (!$importConfig || !Gate::allows($importConfig['permission_required'])) {
            return redirect()->back()->withErrors(['import_type' => 'Invalid import type or insufficient permissions.']);
        }

        // 2. Validate that at least one file is uploaded (if multiple files are configured)
        $uploadedFiles = [];
        $hasAtLeastOneFile = false;
        
        foreach ($importConfig['files'] as $fileKey => $fileConfig) {
            if ($request->hasFile("files.{$fileKey}")) {
                $uploadedFiles[$fileKey] = $request->file("files.{$fileKey}");
                $hasAtLeastOneFile = true;
            }
        }

        if (!$hasAtLeastOneFile) {
            return redirect()->back()->withErrors(['files' => 'At least one file is required for this import type.']);
        }

        // 3. Validate file extensions for uploaded files
        foreach ($uploadedFiles as $fileKey => $file) {
            $allowedExtensions = ['xlsx', 'csv'];
            $extension = strtolower($file->getClientOriginalExtension());
            
            if (!in_array($extension, $allowedExtensions)) {
                return redirect()->back()->withErrors([
                    $fileKey => "Invalid file format. Only .xlsx and .csv files are allowed for {$importConfig['files'][$fileKey]['label']}."
                ]);
            }

            // Check file size (10MB max)
            if ($file->getSize() > 10485760) { // 10MB in bytes
                return redirect()->back()->withErrors([
                    $fileKey => "File size exceeds 10MB limit for {$importConfig['files'][$fileKey]['label']}."
                ]);
            }
        }

        // 4. Validate required headers for each uploaded file
        foreach ($uploadedFiles as $fileKey => $file) {
            $fileConfig = $importConfig['files'][$fileKey];
            $requiredHeaders = array_map('strtolower', array_keys($fileConfig['headers_to_db']));
            
            try {
                $fileHeaders = $this->extractHeadersFromFile($file);
                $missingHeaders = array_diff($requiredHeaders, $fileHeaders);
                
                if (!empty($missingHeaders)) {
                    $missingHeadersText = implode(', ', $missingHeaders);
                    return redirect()->back()->withErrors([
                        $fileKey => "Missing required headers in {$fileConfig['label']}: {$missingHeadersText}"
                    ]);
                }
            } catch (\Exception $e) {
                return redirect()->back()->withErrors([
                    $fileKey => "Error reading {$fileConfig['label']}: " . $e->getMessage()
                ]);
            }
        }

        // All validations passed - proceed with import
        
        // Store uploaded files
        $storedFilePaths = [];
        foreach ($uploadedFiles as $fileKey => $file) {
            $fileName = time() . '_' . $fileKey . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('imports', $fileName);
            $storedFilePaths[$fileKey] = $filePath;
        }

        // Create import record
        $importRecord = \App\Models\Import::create([
            'user_id' => Auth::id(),
            'import_type' => $importType,
            'file_key' => implode(',', array_keys($storedFilePaths)), // Store file keys
            'original_filename' => implode(',', array_map(function($file) { 
                return $file->getClientOriginalName(); 
            }, $uploadedFiles)),
            'file_names' => $storedFilePaths,
            'status' => 'pending',
            'total_rows' => 0, // Will be updated by ProcessImportJob
            'inserted_rows' => 0,
            'updated_rows' => 0,
            'skipped_rows' => 0,
            'error_count' => 0,
            'started_at' => now()
        ]);

        // Dispatch background job
        \App\Jobs\ProcessImportJob::dispatch(
            $importRecord,
            $importType,
            $importConfig,
            $storedFilePaths,
            Auth::id()
        );

        return redirect()->back()->with('success', 'Import has been started and is being processed in the background. You will be notified when complete.');
        
        } catch (\Exception $e) {
            Log::error('Import upload failed: ' . $e->getMessage());
            return redirect()->back()->withErrors(['general' => 'An error occurred while processing your import. Please check your files and try again.']);
        }
    }

    /**
     * Extract headers from uploaded file
     */
    private function extractHeadersFromFile($file)
    {
        $extension = strtolower($file->getClientOriginalExtension());
        
        if ($extension === 'csv') {
            // Read CSV headers
            $handle = fopen($file->getPathname(), 'r');
            if ($handle === false) {
                throw new \Exception('Cannot read CSV file');
            }
            
            $headers = fgetcsv($handle);
            fclose($handle);
            
            if ($headers === false) {
                throw new \Exception('CSV file appears to be empty or corrupted');
            }
            
            // Clean headers (trim whitespace, convert to lowercase for comparison)
            return array_map(function($header) {
                return trim(strtolower($header));
            }, $headers);
            
        } elseif ($extension === 'xlsx') {
            // Use Laravel Excel to read headers
            try {
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
                $reader->setReadDataOnly(true);
                $reader->setReadEmptyCells(false);
                
                $spreadsheet = $reader->load($file->getPathname());
                $worksheet = $spreadsheet->getActiveSheet();
                
                // Get the first row (headers)
                $headers = [];
                $highestColumn = $worksheet->getHighestColumn();
                $columnRange = range('A', $highestColumn);
                
                foreach ($columnRange as $column) {
                    $cellValue = $worksheet->getCell($column . '1')->getValue();
                    if ($cellValue !== null && $cellValue !== '') {
                        $headers[] = trim(strtolower((string)$cellValue));
                    }
                }
                
                return $headers;
                
            } catch (\Exception $e) {
                throw new \Exception('Error reading Excel file: ' . $e->getMessage());
            }
        }
        
        throw new \Exception('Unsupported file format');
    }
}
