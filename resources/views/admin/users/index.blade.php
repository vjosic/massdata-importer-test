@extends('layouts.adminlte')

@section('title', 'Users Management')
@section('page_title', 'Users')
@section('page_description', 'Manage system users')

@section('breadcrumb')
    <li class="active">Users</li>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title">Users List</h3>
                <div class="box-tools">
                    <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">
                        <i class="fa fa-plus"></i> Add New User
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
                            <th>Email</th>
                            <th>Roles</th>
                            <th>Permissions</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                        @foreach($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @foreach($user->roles as $role)
                                    <span class="label label-primary">{{ $role->name }}</span>
                                @endforeach
                            </td>
                            <td>
                                @foreach($user->permissions as $permission)
                                    <span class="label label-success">{{ $permission->name }}</span>
                                @endforeach
                            </td>
                            <td>{{ $user->created_at->format('d/m/Y') }}</td>
                            <td>
                                <a href="{{ route('admin.users.show', $user) }}" class="btn btn-info btn-xs">
                                    <i class="fa fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-warning btn-xs">
                                    <i class="fa fa-edit"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" style="display: inline;">
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
                {{ $users->links() }}
            </div>
        </div>
        <!-- /.box -->
    </div>
</div>
@endsection