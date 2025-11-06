@extends('layouts.adminlte')

@section('title', 'Permissions Management')
@section('page_title', 'Permissions')
@section('page_description', 'Manage system permissions')

@section('breadcrumb')
    <li class="active">Permissions</li>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <!-- Nav tabs -->
        <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
                <li class="active"><a href="#tab_1" data-toggle="tab">Permissions List</a></li>
                <li><a href="#tab_2" data-toggle="tab">Assign Permissions</a></li>
            </ul>
            <div class="tab-content">
                <!-- Permissions List Tab -->
                <div class="tab-pane active" id="tab_1">
                    <div class="box">
                        <div class="box-header">
                            <h3 class="box-title">Permissions List</h3>
                            <div class="box-tools">
                                <a href="{{ route('admin.permissions.create') }}" class="btn btn-primary btn-sm">
                                    <i class="fa fa-plus"></i> Add New Permission
                                </a>
                            </div>
                        </div>
                        <!-- /.box-header -->
                        <div class="box-body table-responsive no-padding">
                            <table class="table table-hover">
                                <tbody>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Guard</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                    @foreach($permissions as $permission)
                                    <tr>
                                        <td>{{ $permission->id }}</td>
                                        <td>{{ $permission->name }}</td>
                                        <td>{{ $permission->guard_name }}</td>
                                        <td>{{ $permission->created_at->format('d/m/Y') }}</td>
                                        <td>
                                            <a href="{{ route('admin.permissions.edit', $permission) }}" class="btn btn-warning btn-xs">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <form method="POST" action="{{ route('admin.permissions.destroy', $permission) }}" style="display: inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-xs" onclick="return confirm('Are you sure?')">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <!-- /.box-body -->
                        <div class="box-footer">
                            {{ $permissions->links() }}
                        </div>
                    </div>
                    <!-- /.box -->
                </div>
                
                <!-- Assign Permissions Tab -->
                <div class="tab-pane" id="tab_2">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">Assign Permissions to User</h3>
                        </div>
                        <!-- /.box-header -->
                        <!-- form start -->
                        <form role="form" method="POST" action="{{ route('admin.permissions.store-assignment') }}">
                            @csrf
                            <div class="box-body">
                                <div class="form-group @error('user_id') has-error @enderror">
                                    <label for="user_id">Select User</label>
                                    <select class="form-control" id="user_id" name="user_id" required>
                                        <option value="">Choose a user...</option>
                                        @php $users = \App\Models\User::with('permissions')->get(); @endphp
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}">
                                                {{ $user->name }} ({{ $user->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('user_id')
                                        <span class="help-block">{{ $message }}</span>
                                    @enderror
                                </div>
                                
                                <div class="form-group">
                                    <label>Permissions</label>
                                    <div class="row">
                                        @php $allPermissions = \Spatie\Permission\Models\Permission::all(); @endphp
                                        @foreach($allPermissions as $permission)
                                            <div class="col-md-4">
                                                <div class="checkbox">
                                                    <label>
                                                        <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"> 
                                                        {{ $permission->name }}
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <!-- /.box-body -->
                            
                            <div class="box-footer">
                                <button type="submit" class="btn btn-primary">Assign Permissions</button>
                            </div>
                        </form>
                    </div>
                    <!-- /.box -->
                </div>
            </div>
        </div>
    </div>
</div>
@endsection