@extends('layouts.adminlte')

@section('title', 'View User')
@section('page_title', 'User Details')
@section('page_description', 'View user information')

@section('breadcrumb')
    <li><a href="{{ route('admin.users.index') }}">Users</a></li>
    <li class="active">{{ $user->name }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">User Information</h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                <table class="table table-bordered">
                    <tr>
                        <th style="width: 30%">ID</th>
                        <td>{{ $user->id }}</td>
                    </tr>
                    <tr>
                        <th>Name</th>
                        <td>{{ $user->name }}</td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td>{{ $user->email }}</td>
                    </tr>
                    <tr>
                        <th>Created</th>
                        <td>{{ $user->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    <tr>
                        <th>Updated</th>
                        <td>{{ $user->updated_at->format('d/m/Y H:i') }}</td>
                    </tr>
                </table>
            </div>
            <!-- /.box-body -->
            <div class="box-footer">
                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-warning">
                    <i class="fa fa-edit"></i> Edit User
                </a>
                <a href="{{ route('admin.users.index') }}" class="btn btn-default">
                    <i class="fa fa-arrow-left"></i> Back to Users
                </a>
            </div>
        </div>
        <!-- /.box -->
    </div>
    
    <div class="col-md-6">
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">Roles & Permissions</h3>
            </div>
            <div class="box-body">
                <div class="form-group">
                    <label>Assigned Roles</label>
                    @if($user->roles->count() > 0)
                        <br>
                        @foreach($user->roles as $role)
                            <span class="label label-primary" style="margin-right: 5px; margin-bottom: 5px; display: inline-block;">
                                {{ $role->name }}
                            </span>
                        @endforeach
                    @else
                        <p class="text-muted">No roles assigned</p>
                    @endif
                </div>
                
                <div class="form-group">
                    <label>Direct Permissions</label>
                    @if($user->permissions->count() > 0)
                        <br>
                        @foreach($user->permissions as $permission)
                            <span class="label label-success" style="margin-right: 5px; margin-bottom: 5px; display: inline-block;">
                                {{ $permission->name }}
                            </span>
                        @endforeach
                    @else
                        <p class="text-muted">No direct permissions assigned</p>
                    @endif
                </div>
                
                <div class="form-group">
                    <label>All Permissions (via roles + direct)</label>
                    @php $allPermissions = $user->getAllPermissions(); @endphp
                    @if($allPermissions->count() > 0)
                        <br>
                        @foreach($allPermissions as $permission)
                            <span class="label label-info" style="margin-right: 5px; margin-bottom: 5px; display: inline-block;">
                                {{ $permission->name }}
                            </span>
                        @endforeach
                    @else
                        <p class="text-muted">No permissions available</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection