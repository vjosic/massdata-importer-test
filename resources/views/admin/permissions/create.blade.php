@extends('layouts.adminlte')

@section('title', 'Create Permission')
@section('page_title', 'Create Permission')
@section('page_description', 'Add new system permission')

@section('breadcrumb')
    <li><a href="{{ route('admin.permissions.index') }}">Permissions</a></li>
    <li class="active">Create</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Permission Information</h3>
            </div>
            <!-- /.box-header -->
            <!-- form start -->
            <form role="form" method="POST" action="{{ route('admin.permissions.store') }}">
                @csrf
                <div class="box-body">
                    <div class="form-group @error('name') has-error @enderror">
                        <label for="name">Permission Name</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               placeholder="e.g. user-create, order-view, etc." 
                               value="{{ old('name') }}" required>
                        @error('name')
                            <span class="help-block">{{ $message }}</span>
                        @enderror
                        <span class="help-block">Use lowercase with hyphens (e.g. user-management, order-create)</span>
                    </div>
                </div>
                <!-- /.box-body -->

                <div class="box-footer">
                    <button type="submit" class="btn btn-primary">Create Permission</button>
                    <a href="{{ route('admin.permissions.index') }}" class="btn btn-default">Cancel</a>
                </div>
            </form>
        </div>
        <!-- /.box -->
    </div>
    
    <div class="col-md-6">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">Permission Naming Guidelines</h3>
            </div>
            <div class="box-body">
                <p><strong>Common permission patterns:</strong></p>
                <ul>
                    <li><code>resource-action</code> format</li>
                    <li><code>user-create</code> - Create users</li>
                    <li><code>user-edit</code> - Edit users</li>
                    <li><code>user-delete</code> - Delete users</li>
                    <li><code>user-view</code> - View users</li>
                    <li><code>user-management</code> - Access user management section</li>
                    <li><code>order-import</code> - Import orders</li>
                    <li><code>product-export</code> - Export products</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection