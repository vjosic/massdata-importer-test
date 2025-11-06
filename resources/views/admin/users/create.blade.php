@extends('layouts.adminlte')

@section('title', 'Create User')
@section('page_title', 'Create User')
@section('page_description', 'Add new system user')

@section('breadcrumb')
    <li><a href="{{ route('admin.users.index') }}">Users</a></li>
    <li class="active">Create</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">User Information</h3>
            </div>
            <!-- /.box-header -->
            <!-- form start -->
            <form role="form" method="POST" action="{{ route('admin.users.store') }}">
                @csrf
                <div class="box-body">
                    <div class="form-group @error('name') has-error @enderror">
                        <label for="name">Name</label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="Enter name" value="{{ old('name') }}" required>
                        @error('name')
                            <span class="help-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group @error('email') has-error @enderror">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Enter email" value="{{ old('email') }}" required>
                        @error('email')
                            <span class="help-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group @error('password') has-error @enderror">
                        <label for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                        @error('password')
                            <span class="help-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="password_confirmation">Confirm Password</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Confirm Password" required>
                    </div>
                </div>
                <!-- /.box-body -->
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
                    <label>Roles</label>
                    @foreach($roles as $role)
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="roles[]" value="{{ $role->name }}"> {{ $role->name }}
                            </label>
                        </div>
                    @endforeach
                </div>
                
                <div class="form-group">
                    <label>Direct Permissions</label>
                    @foreach($permissions as $permission)
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"> {{ $permission->name }}
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xs-12">
        <div class="box-footer">
            <button type="submit" class="btn btn-primary">Create User</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-default">Cancel</a>
        </div>
        </form>
    </div>
</div>
@endsection