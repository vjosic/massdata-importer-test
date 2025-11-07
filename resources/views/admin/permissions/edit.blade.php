@extends('layouts.adminlte')

@section('title', 'Edit Permission')
@section('page_title', 'Edit Permission')
@section('page_description', 'Modify system permission')

@section('breadcrumb')
    <li><a href="{{ route('admin.permissions.index') }}">Permissions</a></li>
    <li class="active">Edit</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Edit Permission Information</h3>
            </div>
            <!-- /.box-header -->
            <!-- form start -->
            <form role="form" method="POST" action="{{ route('admin.permissions.update', $permission) }}">
                @csrf
                @method('PUT')
                <div class="box-body">
                    <div class="form-group @error('name') has-error @enderror">
                        <label for="name">Permission Name</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               placeholder="e.g. user-create, order-view, etc." 
                               value="{{ old('name', $permission->name) }}" required>
                        @error('name')
                            <span class="help-block">{{ $message }}</span>
                        @enderror
                        <span class="help-block">Use lowercase with hyphens (e.g. user-management, order-create)</span>
                    </div>
                    
                    <div class="form-group">
                        <label>Permission ID</label>
                        <p class="form-control-static">{{ $permission->id }}</p>
                    </div>
                    
                    <div class="form-group">
                        <label>Created At</label>
                        <p class="form-control-static">{{ $permission->created_at->format('Y-m-d H:i:s') }}</p>
                    </div>
                    
                    <div class="form-group">
                        <label>Last Updated</label>
                        <p class="form-control-static">{{ $permission->updated_at->format('Y-m-d H:i:s') }}</p>
                    </div>
                </div>
                <!-- /.box-body -->

                <div class="box-footer">
                    <button type="submit" class="btn btn-primary">Update Permission</button>
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
                <p><strong>Format:</strong> Use lowercase letters and hyphens</p>
                <ul>
                    <li><code>user-management</code> - Manage users</li>
                    <li><code>user-create</code> - Create users</li>
                    <li><code>user-edit</code> - Edit users</li>
                    <li><code>user-delete</code> - Delete users</li>
                    <li><code>order-view</code> - View orders</li>
                    <li><code>report-generate</code> - Generate reports</li>
                </ul>
                
                <div class="alert alert-warning">
                    <h4><i class="icon fa fa-warning"></i> Warning!</h4>
                    Changing permission names may affect existing role assignments. Make sure to update any roles that use this permission.
                </div>
            </div>
        </div>
        
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title">Permission Usage</h3>
            </div>
            <div class="box-body">
                <p>This permission is currently assigned to:</p>
                @if($permission->roles->count() > 0)
                    <ul>
                        @foreach($permission->roles as $role)
                            <li><strong>{{ $role->name }}</strong></li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted">No roles are currently using this permission.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection