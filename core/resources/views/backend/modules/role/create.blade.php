@extends('backend.layouts.master')

@section('meta')
  <title>Add Role</title>
@endsection

@section('content')
<div class="page-header d-flex justify-content-between align-items-center p-3">
  <div>
    <h5>Add Role</h5>
    <p class="text-muted m-0">Create a new role</p>
  </div>
  <a href="{{ route('rbac.role.index') }}" class="btn btn-sm btn-secondary">Back to Role Matrix</a>
</div>

<div class="p-3">
  <div class="card">
    <form action="{{ route('rbac.role.store') }}" method="POST">
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
          <div class="col-12 col-md-4">
            <label class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" name="name" id="role_name" class="form-control" value="{{ old('name') }}" required>
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label">Key (optional)</label>
            <input type="text" name="key" id="role_key" class="form-control" value="{{ old('key') }}" placeholder="e.g. sales_manager">
            <small class="text-muted">Blank রাখলে নাম থেকে স্বয়ংক্রিয় তৈরি হবে</small>
          </div>

          <div class="col-12 col-md-4 d-flex align-items-end">
            <div class="form-check my-5">
              <input class="form-check-input" type="checkbox" name="is_super" id="is_super" value="1"
                     {{ old('is_super') ? 'checked' : '' }}>
              <label class="form-check-label mx-2" for="is_super">
                Super Role (all permissions)
              </label>
            </div>
          </div>
        </div>
      </div>

      <div class="card-footer d-flex justify-content-end">
        <button class="btn btn-primary">Create Role</button>
      </div>
    </form>
  </div>
</div>

{{-- auto key from name (slug with _) --}}
@section('script')
<script>
  (function () {
    const name = document.getElementById('role_name');
    const key  = document.getElementById('role_key');
    if (!name || !key) return;
    name.addEventListener('input', () => {
      if (key.value.trim().length) return; // user typed a key, don't override
      const slug = name.value
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g,'')
        .replace(/_{2,}/g,'_');
      key.value = slug;
    });
  })();
</script>
@endsection
@endsection
