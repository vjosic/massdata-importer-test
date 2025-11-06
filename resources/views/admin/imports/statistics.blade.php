@extends('layouts.adminlte')

@section('title', 'Import Statistics')
@section('page_title', 'Import Statistics')

@section('breadcrumb')
    <li><a href="{{ route('admin.imports.index') }}">Imports</a></li>
    <li class="active">Statistics</li>
@endsection

@section('content')
<div class="row">
    <!-- Overall Statistics -->
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="info-box">
            <span class="info-box-icon bg-blue"><i class="fa fa-upload"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Imports</span>
                <span class="info-box-number">{{ number_format($overall['total_imports']) }}</span>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="info-box">
            <span class="info-box-icon bg-green"><i class="fa fa-check"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Success Rate</span>
                <span class="info-box-number">{{ $overall['success_rate'] }}%</span>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="info-box">
            <span class="info-box-icon bg-yellow"><i class="fa fa-files-o"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Records</span>
                <span class="info-box-number">{{ number_format($overall['total_records']) }}</span>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="info-box">
            <span class="info-box-icon bg-red"><i class="fa fa-times"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Errors</span>
                <span class="info-box-number">{{ number_format($overall['total_errors']) }}</span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Status Distribution -->
    <div class="col-md-6">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Imports by Status</h3>
            </div>
            <div class="box-body">
                <div class="chart">
                    <canvas id="statusChart" style="height: 250px; width: 100%;"></canvas>
                </div>
                <div class="row" style="margin-top: 20px;">
                    @foreach($by_status as $status => $count)
                    <div class="col-md-4 col-sm-6">
                        <div class="description-block">
                            @php
                                $statusClass = match($status) {
                                    'completed' => 'text-green',
                                    'failed' => 'text-red',
                                    'completed_with_errors' => 'text-yellow',
                                    'processing' => 'text-blue',
                                    'pending' => 'text-muted',
                                    default => 'text-muted'
                                };
                            @endphp
                            <span class="description-percentage {{ $statusClass }}">
                                <i class="fa fa-circle"></i> {{ number_format($count) }}
                            </span>
                            <h5 class="description-header">{{ ucfirst(str_replace('_', ' ', $status)) }}</h5>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Import Types -->
    <div class="col-md-6">
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">Imports by Type</h3>
            </div>
            <div class="box-body">
                <div class="chart">
                    <canvas id="typeChart" style="height: 250px; width: 100%;"></canvas>
                </div>
                <div class="row" style="margin-top: 20px;">
                    @foreach($by_type as $type => $count)
                    <div class="col-md-6">
                        <div class="description-block">
                            <span class="description-percentage text-blue">
                                <i class="fa fa-circle"></i> {{ number_format($count) }}
                            </span>
                            <h5 class="description-header">
                                {{ config("imports.{$type}.label", ucfirst(str_replace('_', ' ', $type))) }}
                            </h5>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Activity -->
    <div class="col-md-8">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">Recent Import Activity</h3>
            </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>User</th>
                                <th>Status</th>
                                <th>Records</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recent_imports as $import)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.imports.show', $import->id) }}">
                                        {{ $import->created_at->format('M j, H:i') }}
                                    </a>
                                </td>
                                <td>
                                    <span class="label label-primary">
                                        {{ config("imports.{$import->import_type}.label", $import->import_type) }}
                                    </span>
                                </td>
                                <td>{{ $import->user->name ?? 'Unknown' }}</td>
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
                                <td>{{ number_format($import->total_rows) }}</td>
                                <td>
                                    @if($import->started_at && $import->finished_at)
                                        @php
                                            $duration = $import->finished_at->diffInSeconds($import->started_at);
                                        @endphp
                                        @if($duration < 60)
                                            {{ $duration }}s
                                        @else
                                            {{ gmdate('i:s', $duration) }}
                                        @endif
                                    @else
                                        N/A
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Users -->
    <div class="col-md-4">
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title">Most Active Users</h3>
            </div>
            <div class="box-body">
                @foreach($top_users as $user_data)
                <div class="progress-group">
                    <span class="progress-text">{{ $user_data['name'] }}</span>
                    <span class="float-right"><b>{{ $user_data['total'] }}</b>/{{ $overall['total_imports'] }}</span>
                    <div class="progress progress-sm">
                        <div class="progress-bar progress-bar-primary" 
                             style="width: {{ ($user_data['total'] / max($overall['total_imports'], 1)) * 100 }}%">
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">Processing Times</h3>
            </div>
            <div class="box-body">
                <div class="info-box-content">
                    <div class="description-block">
                        <span class="description-percentage text-green">
                            @if($processing_stats['avg_duration'])
                                @if($processing_stats['avg_duration'] < 60)
                                    {{ round($processing_stats['avg_duration']) }}s
                                @else
                                    {{ gmdate('i:s', $processing_stats['avg_duration']) }}
                                @endif
                            @else
                                N/A
                            @endif
                        </span>
                        <h5 class="description-header">Average Duration</h5>
                    </div>
                    <div class="description-block">
                        <span class="description-percentage text-blue">
                            @if($processing_stats['fastest'])
                                @if($processing_stats['fastest'] < 60)
                                    {{ $processing_stats['fastest'] }}s
                                @else
                                    {{ gmdate('i:s', $processing_stats['fastest']) }}
                                @endif
                            @else
                                N/A
                            @endif
                        </span>
                        <h5 class="description-header">Fastest</h5>
                    </div>
                    <div class="description-block">
                        <span class="description-percentage text-red">
                            @if($processing_stats['slowest'])
                                @if($processing_stats['slowest'] < 60)
                                    {{ $processing_stats['slowest'] }}s
                                @else
                                    {{ gmdate('i:s', $processing_stats['slowest']) }}
                                @endif
                            @else
                                N/A
                            @endif
                        </span>
                        <h5 class="description-header">Slowest</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>
<script>
$(function() {
    // Status Chart
    var statusCtx = document.getElementById('statusChart').getContext('2d');
    var statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: [@foreach($by_status as $status => $count) '{{ ucfirst(str_replace("_", " ", $status)) }}',@endforeach],
            datasets: [{
                data: [{{ implode(',', array_values($by_status->toArray())) }}],
                backgroundColor: [
                    '#00a65a', // completed - green
                    '#dd4b39', // failed - red  
                    '#f39c12', // completed_with_errors - yellow
                    '#3c8dbc', // processing - blue
                    '#777777'  // pending - gray
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                display: false
            }
        }
    });

    // Type Chart
    var typeCtx = document.getElementById('typeChart').getContext('2d');
    var typeChart = new Chart(typeCtx, {
        type: 'doughnut',
        data: {
            labels: [@foreach($by_type as $type => $count) '{{ config("imports.{$type}.label", ucfirst(str_replace("_", " ", $type))) }}',@endforeach],
            datasets: [{
                data: [{{ implode(',', array_values($by_type->toArray())) }}],
                backgroundColor: [
                    '#3c8dbc',
                    '#00a65a', 
                    '#f39c12',
                    '#dd4b39',
                    '#605ca8',
                    '#00c0ef'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                display: false
            }
        }
    });
});
</script>
@endpush