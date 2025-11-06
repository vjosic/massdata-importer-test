@extends('layouts.adminlte')

@section('title', $config['label'])
@section('page_title', $config['label'])

@section('breadcrumb')
    <li><a href="{{ route('admin.data.index') }}">Imported Data</a></li>
    <li class="active">{{ $config['label'] }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">{{ $config['label'] }} Data</h3>
                <div class="box-tools pull-right">
                    <a href="{{ route('admin.data.export', $dataset) }}{{ $search ? '?search=' . urlencode($search) : '' }}" 
                       class="btn btn-success btn-sm">
                        <i class="fa fa-download"></i> Export Excel
                    </a>
                </div>
            </div>
            <div class="box-body">
                @if(count($tables) > 1)
                <!-- Table Tabs -->
                <ul class="nav nav-tabs" role="tablist">
                    @foreach($tables as $tableKey => $tableInfo)
                    <li role="presentation" class="{{ $activeTable === $tableKey ? 'active' : '' }}">
                        <a href="{{ route('admin.data.dataset', $dataset) }}?table={{ $tableKey }}{{ $search ? '&search=' . urlencode($search) : '' }}">
                            <i class="fa fa-table"></i> {{ $tableInfo['label'] }}
                        </a>
                    </li>
                    @endforeach
                </ul>
                <br>
                @endif
                
                <!-- Search Form -->
                <form method="GET" action="{{ route('admin.data.dataset', $dataset) }}" class="form-horizontal">
                    @if(isset($activeTable))
                    <input type="hidden" name="table" value="{{ $activeTable }}">
                    @endif
                    <div class="input-group">
                        <input type="text" name="search" value="{{ $search }}" 
                               class="form-control" placeholder="Search all fields...">
                        <span class="input-group-btn">
                            <button class="btn btn-primary" type="submit">
                                <i class="fa fa-search"></i> Search
                            </button>
                            @if($search)
                            <a href="{{ route('admin.data.dataset', $dataset) }}{{ isset($activeTable) ? '?table=' . $activeTable : '' }}" 
                               class="btn btn-default">
                                <i class="fa fa-times"></i> Clear
                            </a>
                            @endif
                        </span>
                    </div>
                </form>

                <br>

                @if($data->count() > 0)
                <!-- Data Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                @foreach($headers as $field => $header)
                                <th>{{ $header['label'] }}</th>
                                @endforeach
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data as $record)
                            <tr>
                                @foreach($headers as $field => $header)
                                <td>
                                    @if($header['type'] === 'datetime' && isset($record->{$field}))
                                        {{ \Carbon\Carbon::parse($record->{$field})->format('Y-m-d H:i:s') }}
                                    @elseif($header['type'] === 'date' && isset($record->{$field}))
                                        {{ \Carbon\Carbon::parse($record->{$field})->format('Y-m-d') }}
                                    @elseif($header['type'] === 'double' && isset($record->{$field}))
                                        {{ number_format($record->{$field}, 2) }}
                                    @else
                                        {{ $record->{$field} ?? '' }}
                                    @endif
                                </td>
                                @endforeach
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-info btn-xs" 
                                                onclick="showAudits('{{ $dataset }}', '{{ $record->id }}')">
                                            <i class="fa fa-history"></i>
                                        </button>
                                        @if($canDelete)
                                        <button type="button" class="btn btn-danger btn-xs" 
                                                onclick="deleteRecord('{{ $dataset }}', '{{ $record->id }}')">
                                            <i class="fa fa-trash"></i>
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
                            Showing {{ $data->firstItem() ?? 0 }} to {{ $data->lastItem() ?? 0 }} 
                            of {{ $data->total() }} entries
                        </div>
                    </div>
                    <div class="col-sm-7">
                        <div class="dataTables_paginate paging_simple_numbers">
                            {{ $data->links() }}
                        </div>
                    </div>
                </div>
                @else
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i>
                    No data found{{ $search ? ' for search "' . $search . '"' : '' }}.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Audit Modal -->
<div class="modal fade" id="auditModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
                <h4 class="modal-title">Audit Trail</h4>
            </div>
            <div class="modal-body">
                <div id="auditContent">
                    <div class="text-center">
                        <i class="fa fa-spinner fa-spin"></i> Loading...
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>

@endsection

@push('js')
<script>
function showAudits(dataset, recordId) {
    $('#auditModal').modal('show');
    $('#auditContent').html('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</div>');
    
    $.get('/admin/data/' + dataset + '/' + recordId + '/audits')
        .done(function(response) {
            let html = '';
            if (response.audits && response.audits.length > 0) {
                html = '<div class="table-responsive"><table class="table table-bordered table-condensed">';
                html += '<thead><tr><th>Date</th><th>Column</th><th>Old Value</th><th>New Value</th></tr></thead>';
                html += '<tbody>';
                
                response.audits.forEach(function(audit) {
                    html += '<tr>';
                    html += '<td>' + new Date(audit.created_at).toLocaleString() + '</td>';
                    html += '<td>' + (audit.column || 'N/A') + '</td>';
                    html += '<td><small>' + (audit.old_value || 'N/A') + '</small></td>';
                    html += '<td><small>' + (audit.new_value || 'N/A') + '</small></td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table></div>';
            } else {
                html = '<div class="alert alert-info">No audit records found for this item.</div>';
            }
            $('#auditContent').html(html);
        })
        .fail(function() {
            $('#auditContent').html('<div class="alert alert-danger">Error loading audit data.</div>');
        });
}

function deleteRecord(dataset, recordId) {
    if (confirm('Are you sure you want to delete this record? This action cannot be undone.')) {
        $.ajax({
            url: '/admin/data/' + dataset + '/' + recordId,
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        })
        .done(function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        })
        .fail(function(xhr, status, error) {
            let errorMessage = 'Error deleting record. Please try again.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = 'Error: ' + xhr.responseJSON.message;
            } else if (xhr.status === 403) {
                errorMessage = 'Error: You do not have permission to delete this record.';
            } else if (xhr.status === 404) {
                errorMessage = 'Error: Record not found.';
            }
            alert(errorMessage);
        });
    }
}
</script>
@endpush