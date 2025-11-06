@extends('layouts.adminlte')

@section('title', 'Assign Permissions')
@section('page_title', 'Assign Permissions')
@section('page_description', 'Assign permissions to users')

@section('breadcrumb')
    <li><a href="{{ route('admin.permissions.index') }}">Permissions</a></li>
    <li class="active">Assign</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
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
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
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
                            @foreach($permissions as $permission)
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
                    <a href="{{ route('admin.permissions.index') }}" class="btn btn-default">Back to Permissions</a>
                </div>
            </form>
        </div>
        <!-- /.box -->
    </div>
</div>

<!-- Current Assignments -->
<div class="row">
    <div class="col-md-12">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">Current User Permissions</h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <tbody>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Permissions</th>
                        </tr>
                        @foreach($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @foreach($user->permissions as $permission)
                                    <span class="label label-success">{{ $permission->name }}</span>
                                @endforeach
                                @if($user->permissions->count() == 0)
                                    <span class="text-muted">No direct permissions</span>
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
@endsection