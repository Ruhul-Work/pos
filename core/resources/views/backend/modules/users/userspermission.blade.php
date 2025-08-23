@extends('backend.layouts.master')

@section('meta')
  <title>User Permission Overrides</title>
@endsection

@section('content')
<div class="page-header d-flex justify-content-between align-items-center p-3">
  <div>
    <h5>User Wise Permission </h5>
    <p class="text-muted m-0">Set per-user allow/deny. “Inherit” means use role baseline.</p>
  </div>
  <div>
    <span class="badge bg-secondary">{{ $user->name }}</span>
    <span class="badge bg-light text-dark"># {{ $user->id }}</span>
    @if (session('success'))
      <span class="badge bg-success ms-2">{{ session('success') }}</span>
    @endif
  </div>
</div>

{{-- Optional: profile tabs --}}
<ul class="nav nav-tabs px-3">
  <li class="nav-item">
    <a class="nav-link" href="{{ route('usermanage.users.profile') }}">Profile</a>
  </li>
  <li class="nav-item">
    <a class="nav-link active" href="{{ route('usermanage.userspermission.edit', $user->id) }}">Permission Overrides</a>
  </li>
</ul>

<form method="POST" action="{{ route('usermanage.userspermission.update', $user->id) }}">
  @csrf
  <div class="p-3">
    <div class="card">
      <div class="card-body table-responsive">
        <table class="table table-bordered align-middle">
          <thead>
            <tr>
              <th style="min-width:260px">Permission (Module → Name)</th>
              @foreach ($abilities as $ab)
                <th class="text-center">{{ $labels[$ab] ?? ucfirst($ab) }}</th>
              @endforeach
            </tr>
          </thead>
          <tbody>
          @forelse ($permissions as $module => $rows)
            <tr class="table-light">
              <td colspan="{{ 1 + count($abilities) }}"><strong>{{ $module }}</strong></td>
            </tr>
            @foreach ($rows as $p)
                @php
                  $o = $overrides[$p->id] ?? null;
                  $current = [
                    'view'   => $o->can_view   ?? null,
                    'add'    => $o->can_add    ?? null,
                    'edit'   => $o->can_edit   ?? null,
                    'delete' => $o->can_delete ?? null,
                    'export' => $o->can_export ?? null,
                  ];
                  $base = $roleMatrix[$p->id] ?? null; // ✅ role baseline
                  $colMap = ['view'=>'can_view','add'=>'can_add','edit'=>'can_edit','delete'=>'can_delete','export'=>'can_export'];
                @endphp

                <tr>
                  <td>
                    <div><code>{{ $p->key }}</code></div>
                    <small class="text-muted">{{ $p->name }}</small>
                  </td>

                            @foreach ($abilities as $ab)
                @php
                  $col = $colMap[$ab];
                  $roleAllows = $isTargetSuper ? true : (bool) (($roleMatrix[$p->id]->$col ?? 0));
                  $override   = $current[$ab];      // null | 1 | 0
                  $effective  = is_null($override) ? $roleAllows : (bool)$override;
                @endphp

                <td class="text-center">
                  @if ($isTargetSuper)
                    {{-- সুপার হলে override দরকার নেই, শুধু দেখান --}}
                    <span class="badge bg-primary">Super: All</span>
                  @else
                  <div class="d-flex align-items-center justify-content-center">
                    {{-- Override select --}}
                    <select class="form-select form-select-sm w-auto d-inline-block"
                            name="items[{{ $p->id }}][{{ $ab }}]">
                      <option value=""  {{ $override === null ? 'selected' : '' }}>Inherit</option>
                      <option value="1" {{ $override === 1    ? 'selected' : '' }}>Allow</option>
                      <option value="0" {{ $override === 0    ? 'selected' : '' }}>Deny</option>
                    </select>
                    
                    <div>
                    <span class="{{ $roleAllows ? 'bg-success-focus text-success-main px-20 py-4 rounded fw-medium text-sm' : 'bg-danger-focus text-danger-main px-20 py-4 rounded fw-medium text-sm' }} ">
                      Role: {{ $roleAllows ? 'Allow' : 'Deny' }}
                    </span>
                    <span class="{{ $effective ? 'bg-success-focus text-success-main px-20 py-4 rounded fw-medium text-sm mt-1'  : 'bg-danger-focus text-danger-main px-20 py-4 rounded fw-medium text-sm mt-1' }} ">
                      Effect: {{ $effective ? 'Allow' : 'Deny' }}
                    </span>
                    </div>
                    </div>
                  @endif
                </td>
              @endforeach
            </tr>
            @endforeach
          @empty
            <tr><td colspan="{{ 1 + count($abilities) }}" class="text-center text-muted">No permissions found.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
      <div class="card-footer d-flex justify-content-end">
        <button class="btn btn-primary">Save Overrides</button>
      </div>
    </div>
  </div>
</form>
@endsection
