@extends('layouts.adminlte')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('breadcrumb')
    <li class="active">Dashboard</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Welcome to Mass Data Importer</h3>
            </div>
            <div class="box-body">
                <h4>Hello, {{ Auth::user()->name }}!</h4>
                <p>Welcome to the Mass Data Importer administration panel.</p>
                <p>Use the navigation menu on the left to access different features of the system.</p>
            </div>
        </div>
    </div>
</div>
@endsection
