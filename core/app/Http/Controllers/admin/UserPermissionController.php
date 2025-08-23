<?php
namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\backend\Permission;
use App\Models\backend\User;
use App\Support\PermCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserPermissionController extends Controller
{

    public function edit(User $user)
    {
        $permissions = Permission::where('is_active', 1)
            ->orderBy('module')->orderBy('sort')->orderBy('name')
            ->get()->groupBy('module');

        $overrides = DB::table('user_permissions')
            ->where('user_id', $user->id)
            ->get()->keyBy('permission_id');

        // role baseline
        $roleMatrix = DB::table('role_permissions')
            ->select('permission_id', 'can_view', 'can_add', 'can_edit', 'can_delete', 'can_export')
            ->where('role_id', $user->role_id)
            ->get()->keyBy('permission_id');

        $isTargetSuper = (bool) ($user->role->is_super ?? false);

        $abilities = config('perm.abilities', ['view', 'add', 'edit', 'delete', 'export']);
        $labels    = config('perm.labels', [
            'view' => 'View', 'add' => 'Add', 'edit' => 'Edit', 'delete' => 'Delete', 'export' => 'Export',
        ]);

        return view('backend.modules.users.userspermission', compact(
            'user', 'permissions', 'overrides', 'roleMatrix', 'abilities', 'labels', 'isTargetSuper'
        ));
    }

    public function update(Request $req, User $user)
    {
        $items     = $req->input('items', []);
        $abilities = config('perm.abilities', ['view', 'add', 'edit', 'delete', 'export']);
        $colMap    = ['view' => 'can_view', 'add' => 'can_add', 'edit' => 'can_edit', 'delete' => 'can_delete', 'export' => 'can_export'];

        // baseline লোড
        $roleMatrix = DB::table('role_permissions')
            ->select('permission_id', 'can_view', 'can_add', 'can_edit', 'can_delete', 'can_export')
            ->where('role_id', $user->role_id)
            ->get()->keyBy('permission_id');

        DB::transaction(function () use ($items, $colMap, $abilities, $roleMatrix, $user) {
            foreach ($items as $pid => $flags) {
                $pid = (int) $pid;

                // baseline map
                $base     = $roleMatrix[$pid] ?? null;
                $baseline = [
                    'view'   => $base ? (int) $base->can_view : 0,
                    'add'    => $base ? (int) $base->can_add : 0,
                    'edit'   => $base ? (int) $base->can_edit : 0,
                    'delete' => $base ? (int) $base->can_delete : 0,
                    'export' => $base ? (int) $base->can_export : 0,
                ];

                // posted → tri-state normalize
                $vals = [];
                foreach ($abilities as $ab) {
                    $raw = $flags[$ab] ?? null; // '', '1', '0' or null
                    $v   = ($raw === '' || $raw === null) ? null : (int) $raw;

                    // যদি baseline-এর সমান হয় → null (inherit) করে দিই
                    if ($v !== null && $v === $baseline[$ab]) {
                        $v = null;
                    }
                    $vals[$colMap[$ab]] = $v;
                }

                // সবই NULL হলে row ডিলিট, নাহলে upsert
                $allNull = collect($vals)->every(fn($x) => $x === null);
                if ($allNull) {
                    DB::table('user_permissions')->where(['user_id' => $user->id, 'permission_id' => $pid])->delete();
                } else {
                    DB::table('user_permissions')->updateOrInsert(
                        ['user_id' => $user->id, 'permission_id' => $pid],
                        $vals + ['updated_at' => now(), 'created_at' => now()]
                    );
                }
            }
            PermCache::forgetUser((int) $user->id);
        });

        return back()->with('success', 'User overrides updated.');
    }

    protected function norm($v): ?int
    {
        // '' বা null => NULL (inherit), '1' => 1 (allow), '0' => 0 (deny)
        if ($v === '' || $v === null) {
            return null;
        }

        return (int) $v;
    }

    protected function allNull(array $vals): bool
    {
        foreach ($vals as $v) {if ($v !== null) {
            return false;
        }}
        return true;
    }
}
