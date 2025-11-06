@extends('layouts.adminlte')

@section('title', 'Imported Data')
@section('page_title', 'Imported Data')

@section('breadcrumb')
    <li class="active">Imported Data</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-3">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Datasets</h3>
            </div>
            <div class="box-body">
                <ul class="nav nav-pills nav-stacked">
                    @foreach($importConfigs as $key => $config)
                    <li class="{{ $activeDataset === $key ? 'active' : '' }}">
                        <a href="{{ route('admin.data.dataset', $key) }}">
                            <i class="fa fa-database"></i>
                            {{ $config['label'] }}
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Select a Dataset</h3>
            </div>
            <div class="box-body">
                <p>Choose a dataset from the sidebar to view and manage imported data.</p>
                
                <div class="row">
                    @foreach($importConfigs as $key => $config)
                    <div class="col-md-4">
                        <div class="box box-solid">
                            <div class="box-header with-border">
                                <h5 class="box-title">{{ $config['label'] }}</h5>
                            </div>
                            <div class="box-body">
                                <p>Manage {{ strtolower($config['label']) }} data.</p>
                                <a href="{{ route('admin.data.dataset', $key) }}" 
                                   class="btn btn-primary">View Data</a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection