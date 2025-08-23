<?php
namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\backend\Permission;
use App\Models\backend\Role;
use App\Support\PermCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::orderByDesc('is_super')->orderBy('id')->get();

        // module/group অনুযায়ী permissions
        $permissions = Permission::where('is_active', 1)
            ->orderBy('module')->orderBy('sort')->orderBy('name')
            ->get()
            ->groupBy('module');

        // role_permissions → matrix: [role_id][permission_id] => ['view'=>.., ...]
        $matrix = [];
        $rows   = DB::table('role_permissions')
            ->select('role_id', 'permission_id', 'can_view', 'can_add', 'can_edit', 'can_delete', 'can_export')
            ->get();

        foreach ($rows as $r) {
            $rid                = (int) $r->role_id;
            $pid                = (int) $r->permission_id;
            $matrix[$rid][$pid] = [
                'view'   => (int) $r->can_view,
                'add'    => (int) $r->can_add,
                'edit'   => (int) $r->can_edit,
                'delete' => (int) $r->can_delete,
                'export' => (int) $r->can_export,
            ];
        }

        // config থেকে abilities + labels
        $abilities = config('perm.abilities', ['view', 'add', 'edit', 'delete', 'export']);
        $labels    = config('perm.labels', [
            'view' => 'View', 'add' => 'Add', 'edit' => 'Edit', 'delete' => 'Delete', 'export' => 'Export',
        ]);

        return view('backend.modules.role.index', compact(
            'roles', 'permissions', 'matrix', 'abilities', 'labels'
        ));
    }

    public function save(Request $req)
    {
        // ইনপুট কাঠামো: items[role_id][permission_id][ability] = 0/1
        $items = $req->input('items', []);

        DB::transaction(function () use ($items) {
            foreach ($items as $roleId => $perms) {
                foreach ($perms as $pid => $flags) {
                    DB::table('role_permissions')->updateOrInsert(
                        ['role_id' => (int) $roleId, 'permission_id' => (int) $pid],
                        [
                            'can_view'   => (int) ($flags['view'] ?? 0),
                            'can_add'    => (int) ($flags['add'] ?? 0),
                            'can_edit'   => (int) ($flags['edit'] ?? 0),
                            'can_delete' => (int) ($flags['delete'] ?? 0),
                            'can_export' => (int) ($flags['export'] ?? 0),
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }
                // role cache bust
                PermCache::forgetRole((int) $roleId);
            }
        });

        return back()->with('success', 'Role permissions saved successfully.');
    }

    public function create(Request $req)
    {
        // non-super হলে super role তৈরি নিষিদ্ধ—UI-তে checkbox দেখালেও backend গার্ড জরুরি
        return view('backend.modules.role.create');
    }

    public function store(Request $req)
    {
        $data = $req->validate([
            'name'     => ['required', 'string', 'max:100', Rule::unique('roles', 'name')],
            'key'      => ['nullable', 'string', 'max:100', Rule::unique('roles', 'key')],
            'is_super' => ['nullable', 'boolean'],
        ]);

        $isSuper = (bool) ($data['is_super'] ?? false);
        if ($isSuper && ! ($req->user()?->isSuper())) {
            abort(403, 'Only a super user can create a super role.');
        }

        // key না দিলে name থেকে slug (underscore) বানান; unique না হলে suffix দিন
        $key  = $data['key'] ?: Str::slug($data['name'], '_');
        $base = $key;
        $i    = 1;
        while (Role::where('key', $key)->exists()) {
            $key = $base . '_' . $i++;
        }

        Role::create([
            'name'     => $data['name'],
            'key'      => $key,
            'is_super' => $isSuper,
        ]);

        return redirect()->route('rbac.role.index')->with('success', 'Role created successfully.');
    }
}
