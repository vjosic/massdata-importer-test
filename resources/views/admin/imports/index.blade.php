@extends('layouts.adminlte')

@section('title', 'Import History')
@section('page_title', 'Import History')

@section('breadcrumb')
    <li class="active">Import History</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
                <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Import Operations</h3>
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-default btn-sm" onclick="refreshTable()">
                        <i class="fa fa-refresh"></i> Refresh
                    </button>
                </div>
            </div>
            
            <div class="box-body">
                <!-- Filters -->
                <div id="filters" class="panel panel-default" style="display: none;">
                    <div class="panel-body">
                        <form method="GET" action="{{ route('admin.imports.index') }}" class="form-horizontal">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="control-label">User</label>
                                        <select name="user" class="form-control">
                                            <option value="">All Users</option>
                                            @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ $request->user == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="control-label">Import Type</label>
                                        <select name="type" class="form-control">
                                            <option value="">All Types</option>
                                            @foreach($importTypes as $key => $config)
                                            <option value="{{ $key }}" {{ $request->type == $key ? 'selected' : '' }}>
                                                {{ $config['label'] }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="control-label">Status</label>
                                        <select name="status" class="form-control">
                                            <option value="">All Statuses</option>
                                            @foreach($statuses as $status)
                                            <option value="{{ $status }}" {{ $request->status == $status ? 'selected' : '' }}>
                                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="control-label">Date Range</label>
                                        <div class="input-group">
                                            <input type="date" name="date_from" value="{{ $request->date_from }}" class="form-control">
                                            <span class="input-group-addon">to</span>
                                            <input type="date" name="date_to" value="{{ $request->date_to }}" class="form-control">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-search"></i> Apply Filters
                                    </button>
                                    <a href="{{ route('admin.imports.index') }}" class="btn btn-default">
                                        <i class="fa fa-times"></i> Clear
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                @if($imports->count() > 0)
                <!-- Imports Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Import Type</th>
                                <th>File(s)</th>
                                <th>Status</th>
                                <th>Rows</th>
                                <th>Errors</th>
                                <th>Created</th>
                                <th>Duration</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($imports as $import)
                            @php
                                $config = config("imports.{$import->import_type}");
                                $statusClass = match($import->status) {
                                    'completed' => 'success',
                                    'failed' => 'danger',
                                    'completed_with_errors' => 'warning',
                                    'processing' => 'info',
                                    'pending' => 'default',
                                    default => 'default'
                                };
                                $processingTime = null;
                                if ($import->started_at && $import->finished_at) {
                                    $start = \Carbon\Carbon::parse($import->started_at);
                                    $end = \Carbon\Carbon::parse($import->finished_at);
                                    $processingTime = $start->diffInSeconds($end);
                                    // For very fast processing (same second), show at least 1 second
                                    if ($processingTime == 0) {
                                        $processingTime = 1;
                                    }
                                }
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('admin.imports.show', $import) }}" class="text-primary">
                                        #{{ $import->id }}
                                    </a>
                                </td>
                                <td>
                                    <strong>{{ $import->user->name ?? 'Unknown' }}</strong><br>
                                    <small class="text-muted">{{ $import->user->email ?? '' }}</small>
                                </td>
                                <td>
                                    <span class="label label-primary">{{ $config['label'] ?? $import->import_type }}</span>
                                </td>
                                <td>
                                    <strong>{{ $import->original_filename }}</strong><br>
                                    @if($import->file_names && is_array($import->file_names))
                                        <small class="text-muted">
                                            {{ count($import->file_names) }} file(s)
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    <span class="label label-{{ $statusClass }}">
                                        {{ ucfirst(str_replace('_', ' ', $import->status)) }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $processedRows = $import->inserted_rows + $import->updated_rows + $import->skipped_rows;
                                        $displayTotal = $import->total_rows > 0 ? $import->total_rows : $processedRows;
                                        
                                        // For better accuracy, calculate actual processed records from audit trail
                                        $actualProcessed = 0;
                                        if($import->status === 'completed') {
                                            try {
                                                $createdRecords = DB::table('audits')
                                                    ->where('import_id', $import->id)
                                                    ->where('column', 'created')
                                                    ->count();
                                                if($createdRecords > 0) {
                                                    $actualProcessed = $createdRecords;
                                                }
                                            } catch (Exception $e) {
                                                // Fallback to original calculation
                                                $actualProcessed = $processedRows;
                                            }
                                        }
                                        
                                        $showActual = $actualProcessed > 0 && $actualProcessed != $processedRows;
                                    @endphp
                                    @if($processedRows > 0 || $import->total_rows > 0)
                                        <strong>{{ number_format($displayTotal) }}</strong><br>
                                        @if($showActual)
                                            <small class="text-success">✓ {{ number_format($actualProcessed) }} successfully processed</small>
                                        @else
                                            @if($import->inserted_rows > 0)
                                                <small class="text-success">✓ {{ number_format($import->inserted_rows) }} inserted</small>
                                            @endif
                                            @if($import->updated_rows > 0)
                                                @if($import->inserted_rows > 0)<br>@endif
                                                <small class="text-info">↻ {{ number_format($import->updated_rows) }} updated</small>
                                            @endif
                                        @endif
                                        @if($import->skipped_rows > 0)
                                            <br><small class="text-warning">⊝ {{ number_format($import->skipped_rows) }} skipped</small>
                                        @endif
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($import->error_count > 0)
                                        <span class="label label-danger">{{ number_format($import->error_count) }}</span>
                                    @else
                                        <span class="text-success">✓ 0</span>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $import->created_at->format('M j, Y') }}</strong><br>
                                    <small class="text-muted">{{ $import->created_at->format('H:i:s') }}</small>
                                </td>
                                <td>
                                    @if($processingTime)
                                        @if($processingTime < 60)
                                            {{ $processingTime }}s
                                        @else
                                            {{ gmdate('i:s', $processingTime) }}
                                        @endif
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-info btn-xs" 
                                                onclick="showLogs({{ $import->id }})" 
                                                title="View Logs">
                                            <i class="fa fa-list-alt"></i>
                                        </button>
                                        <a href="{{ route('admin.imports.show', $import) }}" 
                                           class="btn btn-primary btn-xs" 
                                           title="View Details">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        @if(in_array($import->status, ['failed', 'completed_with_errors']))
                                            <button type="button" class="btn btn-warning btn-xs" 
                                                    onclick="retryImport({{ $import->id }})" 
                                                    title="Retry Import">
                                                <i class="fa fa-refresh"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="row">
                    <div class="col-sm-5">
                        <div class="dataTables_info">
                            Showing {{ $imports->firstItem() ?? 0 }} to {{ $imports->lastItem() ?? 0 }} 
                            of {{ $imports->total() }} imports
                        </div>
                    </div>
                    <div class="col-sm-7">
                        <div class="dataTables_paginate paging_simple_numbers">
                            {{ $imports->links() }}
                        </div>
                    </div>
                </div>
                @else
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i>
                    No imports found{{ request()->anyFilled(['user', 'type', 'status', 'date_from', 'date_to']) ? ' with current filters' : '' }}.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Import Logs Modal -->
<div class="modal fade" id="logsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
                <h4 class="modal-title">Import Logs</h4>
            </div>
            <div class="modal-body">
                <div id="logsContent">
                    <div class="text-center">
                        <i class="fa fa-spinner fa-spin"></i> Loading logs...
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
function toggleFilters() {
    $('#filters').toggle();
}

function showLogs(importId) {
    $('#logsModal').modal('show');
    $('#logsContent').html('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading logs...</div>');
    
    $.get('/admin/imports/' + importId + '/logs')
        .done(function(response) {
            let html = '';
            
            // Import Details
            html += '<div class="row">';
            html += '<div class="col-md-6">';
            html += '<h4>Import Details</h4>';
            html += '<table class="table table-condensed">';
            html += '<tr><td><strong>ID:</strong></td><td>#' + response.import.id + '</td></tr>';
            html += '<tr><td><strong>Type:</strong></td><td>' + (response.config.label || response.import.import_type) + '</td></tr>';
            html += '<tr><td><strong>User:</strong></td><td>' + (response.import.user ? response.import.user.name : 'Unknown') + '</td></tr>';
            html += '<tr><td><strong>Status:</strong></td><td><span class="label label-' + getStatusClass(response.import.status) + '">' + response.import.status.replace('_', ' ') + '</span></td></tr>';
            html += '<tr><td><strong>Created:</strong></td><td>' + new Date(response.import.created_at).toLocaleString() + '</td></tr>';
            if (response.details.processing_time) {
                html += '<tr><td><strong>Duration:</strong></td><td>' + response.details.processing_time + 's</td></tr>';
            }
            html += '</table>';
            html += '</div>';
            
            html += '<div class="col-md-6">';
            html += '<h4>Processing Stats</h4>';
            html += '<table class="table table-condensed">';
            html += '<tr><td><strong>Total Rows:</strong></td><td>' + (response.details.total_rows || 0) + '</td></tr>';
            html += '<tr><td><strong>Inserted:</strong></td><td><span class="text-success">' + (response.details.inserted_rows || 0) + '</span></td></tr>';
            html += '<tr><td><strong>Updated:</strong></td><td><span class="text-info">' + (response.details.updated_rows || 0) + '</span></td></tr>';
            html += '<tr><td><strong>Skipped:</strong></td><td><span class="text-warning">' + (response.details.skipped_rows || 0) + '</span></td></tr>';
            html += '<tr><td><strong>Errors:</strong></td><td><span class="text-danger">' + (response.details.error_count || 0) + '</span></td></tr>';
            html += '</table>';
            html += '</div>';
            html += '</div>';
            
            // Error Messages
            if (response.import.error_message) {
                html += '<div class="alert alert-danger">';
                html += '<h4>Error Message</h4>';
                html += '<p>' + response.import.error_message + '</p>';
                html += '</div>';
            }
            
            // Validation Errors
            if (response.errors && response.errors.length > 0) {
                html += '<h4>Validation Errors (' + response.errors.length + ')</h4>';
                html += '<div class="table-responsive" style="max-height: 300px; overflow-y: auto;">';
                html += '<table class="table table-bordered table-condensed">';
                html += '<thead><tr><th>Row</th><th>Column</th><th>Value</th><th>Message</th></tr></thead>';
                html += '<tbody>';
                
                response.errors.forEach(function(error) {
                    html += '<tr>';
                    html += '<td>' + error.row_number + '</td>';
                    html += '<td>' + error.column + '</td>';
                    html += '<td><small>' + (error.value || 'N/A') + '</small></td>';
                    html += '<td>' + error.message + '</td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table></div>';
            }
            
            // Audit Trail
            if (response.audits && response.audits.length > 0) {
                html += '<h4>Processing Audit Trail (' + response.audits.length + ')</h4>';
                html += '<div class="table-responsive" style="max-height: 200px; overflow-y: auto;">';
                html += '<table class="table table-bordered table-condensed">';
                html += '<thead><tr><th>Time</th><th>Table</th><th>Action</th><th>Record ID</th></tr></thead>';
                html += '<tbody>';
                
                response.audits.forEach(function(audit) {
                    html += '<tr>';
                    html += '<td>' + new Date(audit.created_at).toLocaleString() + '</td>';
                    html += '<td>' + audit.table + '</td>';
                    html += '<td>' + audit.column + '</td>';
                    html += '<td>' + audit.row_pk + '</td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table></div>';
            }
            
            $('#logsContent').html(html);
        })
        .fail(function() {
            $('#logsContent').html('<div class="alert alert-danger">Error loading logs.</div>');
        });
}

function retryImport(importId) {
    if (confirm('Are you sure you want to retry this import? This will clear previous errors and restart the process.')) {
        $.post('/admin/imports/' + importId + '/retry', {
            _token: $('meta[name="csrf-token"]').attr('content')
        })
        .done(function(response) {
            if (response.success) {
                alert('Import has been queued for retry.');
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        })
        .fail(function() {
            alert('Error retrying import. Please try again.');
        });
    }
}

function getStatusClass(status) {
    switch(status) {
        case 'completed': return 'success';
        case 'failed': return 'danger';
        case 'completed_with_errors': return 'warning';
        case 'processing': return 'info';
        case 'pending': return 'default';
        default: return 'default';
    }
}
</script>
@endpush