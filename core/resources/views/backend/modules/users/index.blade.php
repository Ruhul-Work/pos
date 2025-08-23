@extends('backend.layouts.master')

@section('meta')
  <title>Users List</title>
@endsection

@section('content')
<div class="page-header d-flex justify-content-between align-items-center p-3">
  <div>
    <h5>Users List</h5>
    <p class="text-muted m-0">Manage application users</p>
  </div>
  <form class="d-flex" method="get" action="{{ route('usermanage.users.index') }}">
    <input name="q" value="{{ $q }}" class="form-control form-control-sm me-2 " placeholder="Search name/email/phone/username">
    <button class="btn btn-sm btn-outline-primary mx-2">Search</button>
  </form>
</div>
<div class="p-3">
<div class="card">
  <div class="card-body table-responsive">
    <table class="table table-striped align-middle">
      <thead>
        <tr>
          <th style="width:60px">#</th>
          <th>Name</th>
          <th>Username</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Role</th>
          <th class="text-center">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($users as $u)
        
          <tr>
            <td>{{ $u->id }}</td>
            <td>{{ $u->name }}</td>
            <td>{{ $u->username }}</td>
            <td>{{ $u->email }}</td>
            <td>{{ $u->phone }}</td>
            <td>
              <span class="{{ ($u->role->is_super ?? false) ? 'bg-warning-focus text-warning-600 border border-warning-main px-24 py-4 radius-4 fw-medium text-sm' : 'bg-success-focus text-success-600 border border-success-main px-24 py-4 radius-4 fw-medium text-sm' }}">
                {{ $u->role->name ?? '—' }}
              </span>
            </td>
            {{-- <td class="text-center">
              <div class="d-flex align-items-center gap-10 justify-content-center">
                  @perm('usermanage.users.edit','edit')
                    <a class="bg-success-focus text-success-600 bg-hover-success-200 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle"
                      href="{{ route('usermanage.users.edit', $u->id) }}">
                      <iconify-icon icon="lucide:edit" class="menu-icon"></iconify-icon>
                    </a>
                  @endperm

                  @perm('usermanage.userspermission.edit','edit')
                    <a class="btn btn-sm btn-warning"
                      href="{{ route('usermanage.userspermission.edit', $u->id) }}">
                      <i class="ri-shield-keyhole-line me-1"></i>Give Permission
                    </a>
                  @endperm
                </div>
            </td> --}}

                  <td class="text-center">
          <div class="d-flex align-items-center gap-10 justify-content-center">

            @perm('usermanage.userspermission.edit','edit')
              <a class="btn btn-sm btn-warning"
                href="{{ route('usermanage.userspermission.edit', $u->id) }}">
                <i class="ri-shield-keyhole-line me-1"></i>Give Permission
              </a>
            @endperm

            @perm('usermanage.users.edit','edit')
              <a class="bg-success-focus text-success-600 bg-hover-success-200 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle"
                href="{{ route('usermanage.users.edit', $u->id) }}">
                <iconify-icon icon="lucide:edit" class="menu-icon"></iconify-icon>
              </a>
            @endperm

            @perm('usermanage.users.destroy','delete')
              <form action="{{ route('usermanage.users.destroy', $u->id) }}"
                    method="POST"
                    onsubmit="return confirm('Delete this user permanently?');"
                    class="d-inline">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="bg-danger-focus text-danger-600 bg-hover-danger-200 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle border-0">
                  <iconify-icon icon="mdi:trash-can-outline" class="menu-icon"></iconify-icon>
                </button>
              </form>
            @endperm

          </div>
      </td>

          </tr>
        @empty
          <tr><td colspan="7" class="text-center text-muted">No users found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="card-footer">
    {{ $users->links() }}
  </div>
</div>
</div>
@endsection
