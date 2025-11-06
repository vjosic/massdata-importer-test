<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Events\ImportErrorOccurred;
use App\Models\Import;
use Exception;

class ProcessImportJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    protected $importRecord;
    protected $importType;
    protected $importConfig;
    protected $filePaths;
    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct($importRecord, $importType, $importConfig, $filePaths, $userId)
    {
        $this->importRecord = $importRecord;
        $this->importType = $importType;
        $this->importConfig = $importConfig;
        $this->filePaths = $filePaths;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Starting import job for import ID: {$this->importRecord->id}");
            
            // Update import status to processing
            $this->importRecord->update(['status' => 'processing']);
            
            $totalProcessed = 0;
            $totalErrors = 0;

            // Process each uploaded file
            foreach ($this->filePaths as $fileKey => $filePath) {
                $fileConfig = $this->importConfig['files'][$fileKey];
                
                Log::info("Processing file: {$fileKey} - {$filePath}");
                
                $result = $this->processFile($filePath, $fileKey, $fileConfig);
                $totalProcessed += $result['processed'];
                $totalErrors += $result['errors'];
            }

            // Update import record with final status
            $this->importRecord->update([
                'status' => $totalErrors > 0 ? 'completed_with_errors' : 'completed',
                'finished_at' => now(),
                'processed_at' => now(),
                'inserted_rows' => $totalProcessed,
                'error_count' => $totalErrors
            ]);

            Log::info("Import completed. Processed: {$totalProcessed}, Errors: {$totalErrors}");

        } catch (Exception $e) {
            Log::error("Import job failed: " . $e->getMessage());
            
            // Update import record to failed
            $this->importRecord->update([
                'status' => 'failed',
                'finished_at' => now(),
                'processed_at' => now(),
                'error_message' => $e->getMessage()
            ]);

            // Fire event for email notification
            event(new ImportErrorOccurred($this->importRecord, $e->getMessage(), $this->userId));
            
            throw $e;
        }
    }

    /**
     * Process individual file
     */
    private function processFile($filePath, $fileKey, $fileConfig)
    {
        $processed = 0;
        $errors = 0;
        
        try {
            $data = $this->readFileData($filePath);
            $headers = array_shift($data); // Remove header row
            
            // Map headers to database columns
            $headerMapping = $this->createHeaderMapping($headers, $fileConfig['headers_to_db']);
            
            foreach ($data as $rowIndex => $row) {
                try {
                    $mappedData = $this->mapRowToDatabase($row, $headerMapping, $fileConfig['headers_to_db']);
                    
                    // Validate row data
                    $validationResult = $this->validateRowData($mappedData, $fileConfig['headers_to_db'], $rowIndex + 2, $fileConfig['update_or_create'] ?? []);
                    
                    if (!$validationResult['valid']) {
                        // Log validation errors
                        foreach ($validationResult['errors'] as $error) {
                            DB::table('import_errors')->insert([
                                'import_id' => $this->importRecord->id,
                                'row_number' => $rowIndex + 2,
                                'column' => $error['column'],
                                'value' => $error['value'],
                                'message' => $error['message'],
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);
                        }
                        $errors++;
                        continue; // Skip this row
                    }
                    
                    // Determine target table based on file key
                    $tableName = $this->getTableNameFromFileKey($fileKey);
                    
                    // Use update or create based on config
                    $updateOrCreateKeys = $fileConfig['update_or_create'] ?? [];
                    $this->upsertRecord($tableName, $mappedData, $updateOrCreateKeys, $rowIndex + 2);
                    
                    $processed++;
                    
                } catch (Exception $e) {
                    $errors++;
                    
                    // Log individual row error
                    DB::table('import_errors')->insert([
                        'import_id' => $this->importRecord->id,
                        'row_number' => $rowIndex + 2, // +2 because we removed header and arrays are 0-indexed
                        'column' => 'general',
                        'value' => json_encode($row),
                        'message' => $e->getMessage(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    
                    Log::warning("Row error in file {$fileKey}, row {$rowIndex}: " . $e->getMessage());
                }
            }
            
        } catch (Exception $e) {
            Log::error("File processing error for {$fileKey}: " . $e->getMessage());
            throw $e;
        }
        
        return ['processed' => $processed, 'errors' => $errors];
    }

    /**
     * Read file data (CSV or Excel)
     */
    private function readFileData($filePath)
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        if ($extension === 'csv') {
            return $this->readCsvFile($filePath);
        } elseif ($extension === 'xlsx') {
            return $this->readExcelFile($filePath);
        }
        
        throw new Exception("Unsupported file format: {$extension}");
    }

    /**
     * Read CSV file
     */
    private function readCsvFile($filePath)
    {
        $data = [];
        $fullPath = Storage::disk('local')->path($filePath);
        $handle = fopen($fullPath, 'r');
        
        if ($handle === false) {
            throw new Exception("Cannot read CSV file: {$filePath}");
        }
        
        while (($row = fgetcsv($handle)) !== false) {
            $data[] = $row;
        }
        
        fclose($handle);
        return $data;
    }

    /**
     * Read Excel file
     */
    private function readExcelFile($filePath)
    {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        
        $fullPath = Storage::disk('local')->path($filePath);
        $spreadsheet = $reader->load($fullPath);
        $worksheet = $spreadsheet->getActiveSheet();
        
        $data = [];
        foreach ($worksheet->getRowIterator() as $row) {
            $rowData = [];
            foreach ($row->getCellIterator() as $cell) {
                $rowData[] = $cell->getValue();
            }
            $data[] = $rowData;
        }
        
        return $data;
    }

    /**
     * Create header mapping from file headers to config headers
     */
    private function createHeaderMapping($fileHeaders, $configHeaders)
    {
        $mapping = [];
        $configHeadersLower = array_map('strtolower', array_keys($configHeaders));
        
        foreach ($fileHeaders as $index => $header) {
            $headerLower = strtolower(trim($header));
            $configIndex = array_search($headerLower, $configHeadersLower);
            
            if ($configIndex !== false) {
                $configHeader = array_keys($configHeaders)[$configIndex];
                $mapping[$index] = $configHeader;
            }
        }
        
        return $mapping;
    }

    /**
     * Map row data to database columns
     */
    private function mapRowToDatabase($row, $headerMapping, $configHeaders)
    {
        $mappedData = [];
        
        foreach ($headerMapping as $fileIndex => $dbColumn) {
            $value = isset($row[$fileIndex]) ? trim($row[$fileIndex]) : null;
            $config = $configHeaders[$dbColumn];
            
            // Type conversion
            $mappedData[$dbColumn] = $this->convertValue($value, $config['type']);
        }
        
        return $mappedData;
    }

    /**
     * Convert value based on type
     */
    private function convertValue($value, $type)
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        switch ($type) {
            case 'date':
                return date('Y-m-d', strtotime($value));
            case 'double':
            case 'decimal':
                return (float) $value;
            case 'integer':
                return (int) $value;
            case 'email':
            case 'string':
            default:
                return $value;
        }
    }

    /**
     * Get table name from file key
     */
    private function getTableNameFromFileKey($fileKey)
    {
        // Map file keys to table names
        $mapping = [
            'orders_file' => 'orders',
            'customers_file' => 'customers', 
            'tracking_file' => 'tracking',
            'products_file' => 'products',
            'stock_levels_file' => 'stock_levels',
            'suppliers_file' => 'suppliers'
        ];
        
        return $mapping[$fileKey] ?? $fileKey;
    }

    /**
     * Upsert record using update_or_create logic
     */
    private function upsertRecord($tableName, $data, $updateOrCreateKeys, $rowNumber)
    {
        $whereClause = [];
        foreach ($updateOrCreateKeys as $key) {
            if (isset($data[$key])) {
                $whereClause[$key] = $data[$key];
            }
        }
        
        $recordId = null;
        $isUpdate = false;
        $oldRecord = null;
        
        if (empty($whereClause)) {
            // If no keys for update_or_create, just insert
            $recordId = DB::table($tableName)->insertGetId(array_merge($data, [
                'created_at' => now(),
                'updated_at' => now()
            ]));
            
            // Log creation audit
            DB::table('audits')->insert([
                'import_id' => $this->importRecord->id,
                'table' => $tableName,
                'row_pk' => $recordId,
                'column' => 'created',
                'old_value' => null,
                'new_value' => json_encode($data),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
        } else {
            // Check if record exists
            $existingRecord = DB::table($tableName)->where($whereClause)->first();
            
            if ($existingRecord) {
                $isUpdate = true;
                $oldRecord = (array) $existingRecord;
                $recordId = $existingRecord->id;
                
                // Update existing record
                DB::table($tableName)->where($whereClause)->update(array_merge($data, [
                    'updated_at' => now()
                ]));
                
                // Log each changed field
                foreach ($data as $column => $newValue) {
                    $oldValue = $oldRecord[$column] ?? null;
                    
                    // Only log if value actually changed
                    if ($oldValue != $newValue) {
                        DB::table('audits')->insert([
                            'import_id' => $this->importRecord->id,
                            'table' => $tableName,
                            'row_pk' => $recordId,
                            'column' => $column,
                            'old_value' => $oldValue,
                            'new_value' => $newValue,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }
                
            } else {
                // Insert new record
                $recordId = DB::table($tableName)->insertGetId(array_merge($data, [
                    'created_at' => now(),
                    'updated_at' => now()
                ]));
                
                // Log creation audit
                DB::table('audits')->insert([
                    'import_id' => $this->importRecord->id,
                    'table' => $tableName,
                    'row_pk' => $recordId,
                    'column' => 'created',
                    'old_value' => null,
                    'new_value' => json_encode($data),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
        
        return $recordId;
    }

    /**
     * Validate row data according to configuration rules
     */
    private function validateRowData($data, $configHeaders, $rowNumber, $updateOrCreateKeys = [])
    {
        $errors = [];
        $valid = true;

        foreach ($configHeaders as $column => $config) {
            $value = $data[$column] ?? null;
            $validation = $config['validation'] ?? [];

            foreach ($validation as $ruleKey => $ruleValue) {
                // Handle simple rules (e.g., 'required')
                if (is_numeric($ruleKey)) {
                    $rule = $ruleValue;
                    $ruleConfig = null;
                } else {
                    // Handle complex rules (e.g., 'in' => [...])
                    $rule = $ruleKey;
                    $ruleConfig = $ruleValue;
                }

                $validationResult = $this->validateField($value, $rule, $ruleConfig, $column, $data, $updateOrCreateKeys);
                
                if (!$validationResult['valid']) {
                    $errors[] = [
                        'column' => $column,
                        'value' => $value,
                        'message' => $validationResult['message']
                    ];
                    $valid = false;
                }
            }
        }

        return [
            'valid' => $valid,
            'errors' => $errors
        ];
    }

    /**
     * Validate individual field based on rule
     */
    private function validateField($value, $rule, $ruleConfig, $column, $allData, $updateOrCreateKeys = [])
    {
        switch ($rule) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    return [
                        'valid' => false,
                        'message' => "The {$column} field is required."
                    ];
                }
                break;

            case 'unique':
                if (!empty($value) && $ruleConfig) {
                    $table = $ruleConfig['table'];
                    $tableColumn = $ruleConfig['column'];
                    $ignoreOnUpdate = $ruleConfig['ignore_on_update'] ?? false;
                    
                    $query = DB::table($table)->where($tableColumn, $value);
                    
                    // If this is an update operation and ignore_on_update is true
                    if ($ignoreOnUpdate && !empty($updateOrCreateKeys)) {
                        $whereClause = [];
                        foreach ($updateOrCreateKeys as $key) {
                            if (isset($allData[$key])) {
                                $whereClause[$key] = $allData[$key];
                            }
                        }
                        
                        // Check if record exists with update keys
                        if (!empty($whereClause)) {
                            $existingRecord = DB::table($table)->where($whereClause)->first();
                            if ($existingRecord) {
                                // If updating existing record, exclude it from unique check
                                $query->where('id', '!=', $existingRecord->id);
                            }
                        }
                    }
                    
                    if ($query->exists()) {
                        return [
                            'valid' => false,
                            'message' => "The {$column} value '{$value}' already exists."
                        ];
                    }
                }
                break;

            case 'exists':
                if (!empty($value) && $ruleConfig) {
                    $table = $ruleConfig['table'];
                    $tableColumn = $ruleConfig['column'];
                    
                    if (!DB::table($table)->where($tableColumn, $value)->exists()) {
                        return [
                            'valid' => false,
                            'message' => "The {$column} value '{$value}' does not exist in {$table}.{$tableColumn}."
                        ];
                    }
                }
                break;

            case 'in':
                if (!empty($value) && is_array($ruleConfig)) {
                    if (!in_array($value, $ruleConfig)) {
                        $allowedValues = implode(', ', $ruleConfig);
                        return [
                            'valid' => false,
                            'message' => "The {$column} value '{$value}' is not in allowed values: {$allowedValues}."
                        ];
                    }
                }
                break;

            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return [
                        'valid' => false,
                        'message' => "The {$column} field must be a valid email address."
                    ];
                }
                break;

            case 'nullable':
                // Always valid for nullable fields
                break;

            default:
                // Unknown validation rule - skip
                break;
        }

        return ['valid' => true, 'message' => ''];
    }
}
