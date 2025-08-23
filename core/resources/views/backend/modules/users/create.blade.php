@extends('backend.layouts.master')

@section('meta')
  <title>Add User</title>
@endsection

@section('content')
<div class="page-header d-flex justify-content-between align-items-center p-3">
  <div>
    <h5>Add User</h5>
    <p class="text-muted m-0">Create a new user and assign a role</p>
  </div>
  <a href="{{ route('usermanage.users.index') }}" class="btn btn-sm btn-secondary">Back to Users</a>
</div>

<div class="p-3">
  <div class="card">
    <form action="{{ route('usermanage.users.store') }}" method="POST">
      @csrf
      <div class="card-body">
        @if ($errors->any())
          <div class="alert alert-danger">
            <ul class="mb-0">
              @foreach ($errors->all() as $e)
                <li>{{ $e }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <div class="row g-3">
          {{-- Row 1 --}}
          <div class="col-12 col-md-4">
            <label class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label">Role <span class="text-danger">*</span></label>
            <select name="role_id" class="form-select" required>
              <option value="">-- Select Role --</option>
              @foreach ($roles as $r)
                <option value="{{ $r->id }}" {{ old('role_id')==$r->id ? 'selected':'' }}>
                  {{ $r->name }} @if($r->is_super) (Super) @endif
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="example@gmail.com">
            <small class="text-muted">Email বা Username—অন্তত একটি দিন</small>
          </div>

          {{-- Row 2 --}}
          <div class="col-12 col-md-4">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" value="{{ old('username') }}" placeholder="optional">
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="optional">
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label">Branch ID</label>
            <input type="number" name="branch_id" class="form-control" value="{{ old('branch_id') }}" placeholder="optional">
          </div>

          {{-- Row 3 --}}
          <div class="col-12 col-md-4">
            <label class="form-label">Password <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control" required>
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
            <input type="password" name="password_confirmation" class="form-control" required>
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label d-block">Status</label>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="status" id="st1" value="1" {{ old('status','1')=='1'?'checked':'' }}>
              <label class="form-check-label" for="st1">Active</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="status" id="st0" value="0" {{ old('status')=='0'?'checked':'' }}>
              <label class="form-check-label" for="st0">Inactive</label>
            </div>
          </div>
        </div>
      </div>

      <div class="card-footer d-flex justify-content-end">
        <button class="btn btn-primary">Create User</button>
      </div>
    </form>
  </div>
</div>
@endsection
