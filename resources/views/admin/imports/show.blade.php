@extends('layouts.adminlte')

@section('title', 'Import Details')
@section('page_title', 'Import Details')

@section('breadcrumb')
    <li><a href="{{ route('admin.imports.index') }}">Imports</a></li>
    <li class="active">Import #{{ $import->id }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Import Information</h3>
                <div class="box-tools pull-right">
                    @if(in_array($import->status, ['failed', 'completed_with_errors']))
                        <button type="button" class="btn btn-warning btn-sm" onclick="retryImport({{ $import->id }})">
                            <i class="fa fa-refresh"></i> Retry Import
                        </button>
                    @endif
                </div>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-condensed">
                            <tr>
                                <td><strong>Import ID:</strong></td>
                                <td>#{{ $import->id }}</td>
                            </tr>
                            <tr>
                                <td><strong>Import Type:</strong></td>
                                <td>
                                    <span class="label label-primary">{{ $config['label'] ?? $import->import_type }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>User:</strong></td>
                                <td>
                                    <strong>{{ $import->user->name ?? 'Unknown' }}</strong><br>
                                    <small class="text-muted">{{ $import->user->email ?? '' }}</small>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Original Filename:</strong></td>
                                <td>{{ $import->original_filename }}</td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    @php
                                        $statusClass = match($import->status) {
                                            'completed' => 'success',
                                            'failed' => 'danger',
                                            'completed_with_errors' => 'warning',
                                            'processing' => 'info',
                                            'pending' => 'default',
                                            default => 'default'
                                        };
                                    @endphp
                                    <span class="label label-{{ $statusClass }}">
                                        {{ ucfirst(str_replace('_', ' ', $import->status)) }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-condensed">
                            <tr>
                                <td><strong>Created:</strong></td>
                                <td>{{ $import->created_at->format('M j, Y H:i:s') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Started:</strong></td>
                                <td>{{ $import->started_at ? $import->started_at->format('M j, Y H:i:s') : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Finished:</strong></td>
                                <td>{{ $import->finished_at ? $import->finished_at->format('M j, Y H:i:s') : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Processing Time:</strong></td>
                                <td>
                                    @if($stats['processing_time'])
                                        @if($stats['processing_time'] < 60)
                                            {{ $stats['processing_time'] }}s
                                        @else
                                            {{ gmdate('i:s', $stats['processing_time']) }}
                                        @endif
                                    @else
                                        N/A
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Files Processed:</strong></td>
                                <td>
                                    @if($import->file_names && is_array($import->file_names))
                                        {{ count($import->file_names) }} file(s)
                                    @else
                                        1 file
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                @if($import->error_message)
                <div class="alert alert-danger">
                    <h4><i class="fa fa-ban"></i> Error Message</h4>
                    {{ $import->error_message }}
                </div>
                @endif
            </div>
        </div>

        @if($import->importErrors->count() > 0)
        <div class="box box-danger">
            <div class="box-header with-border">
                <h3 class="box-title">Validation Errors ({{ $import->importErrors->count() }})</h3>
            </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Row</th>
                                <th>Column</th>
                                <th>Value</th>
                                <th>Error Message</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($import->importErrors->take(50) as $error)
                            <tr>
                                <td>{{ $error->row_number }}</td>
                                <td>{{ $error->column }}</td>
                                <td>
                                    <small>{{ Str::limit($error->value ?? 'N/A', 50) }}</small>
                                </td>
                                <td>{{ $error->message }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($import->importErrors->count() > 50)
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i>
                    Showing first 50 errors. Total: {{ $import->importErrors->count() }} errors.
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>

    <div class="col-md-4">
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">Processing Statistics</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-12">
                        <!-- Total Rows -->
                        <div class="info-box">
                            <span class="info-box-icon bg-blue"><i class="fa fa-files-o"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Rows</span>
                                <span class="info-box-number">{{ number_format($import->total_rows) }}</span>
                            </div>
                        </div>

                        <!-- Success Rate -->
                        <div class="info-box">
                            <span class="info-box-icon bg-green"><i class="fa fa-check"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Success Rate</span>
                                <span class="info-box-number">{{ $stats['success_rate'] }}%</span>
                                <div class="progress">
                                    <div class="progress-bar progress-bar-success" style="width: {{ $stats['success_rate'] }}%"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Error Rate -->
                        @if($stats['error_rate'] > 0)
                        <div class="info-box">
                            <span class="info-box-icon bg-red"><i class="fa fa-exclamation-triangle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Error Rate</span>
                                <span class="info-box-number">{{ $stats['error_rate'] }}%</span>
                                <div class="progress">
                                    <div class="progress-bar progress-bar-danger" style="width: {{ $stats['error_rate'] }}%"></div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <hr>

                <h4>Breakdown</h4>
                <table class="table table-condensed">
                    <tr>
                        <td><i class="fa fa-plus text-success"></i> Inserted:</td>
                        <td><strong>{{ number_format($import->inserted_rows) }}</strong></td>
                    </tr>
                    <tr>
                        <td><i class="fa fa-edit text-info"></i> Updated:</td>
                        <td><strong>{{ number_format($import->updated_rows) }}</strong></td>
                    </tr>
                    <tr>
                        <td><i class="fa fa-minus text-warning"></i> Skipped:</td>
                        <td><strong>{{ number_format($import->skipped_rows) }}</strong></td>
                    </tr>
                    <tr>
                        <td><i class="fa fa-times text-danger"></i> Errors:</td>
                        <td><strong>{{ number_format($import->error_count) }}</strong></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">File Information</h3>
            </div>
            <div class="box-body">
                @if($import->file_names && is_array($import->file_names))
                    @foreach($import->file_names as $key => $fileName)
                    <p>
                        <strong>{{ $key }}:</strong><br>
                        <small class="text-muted">{{ basename($fileName) }}</small>
                    </p>
                    @endforeach
                @else
                    <p>
                        <strong>File:</strong><br>
                        <small class="text-muted">{{ $import->original_filename }}</small>
                    </p>
                @endif

                @if($config && isset($config['files']))
                <hr>
                <h4>Expected Structure</h4>
                @foreach($config['files'] as $fileKey => $fileConfig)
                <p>
                    <strong>{{ $fileConfig['label'] }}:</strong><br>
                    <small class="text-muted">
                        {{ count($fileConfig['headers_to_db']) }} columns expected
                    </small>
                </p>
                @endforeach
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
function retryImport(importId) {
    if (confirm('Are you sure you want to retry this import? This will clear previous errors and restart the process.')) {
        $.post('/admin/imports/' + importId + '/retry', {
            _token: $('meta[name="csrf-token"]').attr('content')
        })
        .done(function(response) {
            if (response.success) {
                alert('Import has been queued for retry.');
                location.href = '{{ route("admin.imports.index") }}';
            } else {
                alert('Error: ' + response.message);
            }
        })
        .fail(function() {
            alert('Error retrying import. Please try again.');
        });
    }
}
</script>
@endpush