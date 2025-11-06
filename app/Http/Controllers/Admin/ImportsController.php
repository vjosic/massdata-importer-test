<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Import;
use App\Models\ImportError;
use Illuminate\Support\Facades\Auth;

class ImportsController extends Controller
{
    /**
     * Display a listing of all imports
     */
    public function index(Request $request)
    {
        $query = Import::with('user', 'importErrors')
                      ->orderBy('created_at', 'desc');
        
        // Filter by user if specified
        if ($request->filled('user')) {
            $query->where('user_id', $request->user);
        }
        
        // Filter by import type
        if ($request->filled('type')) {
            $query->where('import_type', $request->type);
        }
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        $imports = $query->paginate(25)->appends($request->query());
        
        // Get filter options
        $users = \App\Models\User::orderBy('name')->get();
        $importTypes = config('imports');
        $statuses = ['pending', 'processing', 'completed', 'failed', 'completed_with_errors'];
        
        return view('admin.imports.index', compact(
            'imports', 
            'users', 
            'importTypes', 
            'statuses',
            'request'
        ));
    }
    
    /**
     * Get detailed logs for specific import
     */
    public function logs(Import $import)
    {
        // Load user relationship for proper display
        $import->load('user');
        
        // Get import errors
        $errors = ImportError::where('import_id', $import->id)
                           ->orderBy('row_number')
                           ->get();
        
        // Get audit trail for this import
        $audits = \App\Models\Audit::where('import_id', $import->id)
                                  ->orderBy('created_at', 'desc')
                                  ->get();
        
        // Get processing details
        $details = [
            'total_rows' => $import->total_rows,
            'inserted_rows' => $import->inserted_rows,
            'updated_rows' => $import->updated_rows,
            'skipped_rows' => $import->skipped_rows,
            'error_count' => $import->error_count,
            'started_at' => $import->started_at,
            'finished_at' => $import->finished_at,
            'processing_time' => $this->getProcessingTime($import),
            'file_names' => $import->file_names,
            'original_filename' => $import->original_filename,
        ];
        
        return response()->json([
            'import' => $import,
            'errors' => $errors,
            'audits' => $audits,
            'details' => $details,
            'config' => config("imports.{$import->import_type}")
        ]);
    }
    
    /**
     * Show specific import details page
     */
    public function show(Import $import)
    {
        $import->load('user', 'importErrors');
        
        // Get import configuration
        $config = config("imports.{$import->import_type}");
        
        // Get processing stats
        $stats = [
            'total_processed' => $import->inserted_rows + $import->updated_rows + $import->skipped_rows,
            'success_rate' => $import->total_rows > 0 ? 
                round((($import->inserted_rows + $import->updated_rows) / $import->total_rows) * 100, 2) : 0,
            'error_rate' => $import->total_rows > 0 ? 
                round(($import->error_count / $import->total_rows) * 100, 2) : 0,
            'processing_time' => $this->getProcessingTime($import)
        ];
        
        return view('admin.imports.show', compact('import', 'config', 'stats'));
    }
    
    /**
     * Retry failed import
     */
    public function retry(Import $import)
    {
        // Check if import can be retried
        if (!in_array($import->status, ['failed', 'completed_with_errors'])) {
            return response()->json([
                'success' => false, 
                'message' => 'Only failed or partially completed imports can be retried'
            ], 422);
        }
        
        // Check if user has permission for this import type
        $config = config("imports.{$import->import_type}");
        if (!$config || !\Illuminate\Support\Facades\Gate::allows($config['permission_required'])) {
            return response()->json([
                'success' => false, 
                'message' => 'Insufficient permissions to retry this import'
            ], 403);
        }
        
        // Reset import status
        $import->update([
            'status' => 'pending',
            'error_message' => null,
            'finished_at' => null,
            'processed_at' => null
        ]);
        
        // Clear previous errors
        ImportError::where('import_id', $import->id)->delete();
        
        // Reconstruct file paths from stored data
        $filePaths = [];
        if ($import->file_names && is_array($import->file_names)) {
            foreach ($import->file_names as $key => $fileName) {
                $filePaths[$key] = $fileName;
            }
        }
        
        // Dispatch job again
        \App\Jobs\ProcessImportJob::dispatch(
            $import,
            $import->import_type,
            $config,
            $filePaths,
            $import->user_id
        );
        
        return response()->json([
            'success' => true, 
            'message' => 'Import has been queued for retry'
        ]);
    }
    
    /**
     * Calculate processing time
     */
    private function getProcessingTime(Import $import)
    {
        if (!$import->started_at || !$import->finished_at) {
            return null;
        }
        
        $start = \Carbon\Carbon::parse($import->started_at);
        $end = \Carbon\Carbon::parse($import->finished_at);
        
        // Calculate difference in seconds (absolute value to handle same timestamps)
        $seconds = $start->diffInSeconds($end);
        
        // For very fast processing (same second), show at least 1 second
        return max($seconds, 1);
    }
}
