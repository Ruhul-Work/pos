<?php
namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\backend\Permission;
use App\Models\backend\PermissionRoute;
use App\Support\PermCache;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::with('routes')
            ->orderBy('module')->orderBy('sort')->get();

        return view('backend.modules.permissions.index', compact('permissions'));
    }

    public function create()
    {
        // চাইলে টাইপ অপশন/ডিফল্ট পাঠাতে পারেন
        $types = ['route' => 'Route', 'feature' => 'Feature'];
        return view('backend.modules.permissions.create', compact('types'));
    }

    public function store(Request $req)
    {
        $data = $req->validate([
            'key'       => ['required', 'max:191', Rule::unique('permissions', 'key')],
            'name'      => ['required', 'max:150'],
            'module'    => ['required', 'max:150'],
            'type'      => ['nullable', 'in:route,feature'],
            'sort'      => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['type']      = $data['type'] ?? 'route';
        $data['sort']      = $data['sort'] ?? 0;
        $data['is_active'] = (int) ($data['is_active'] ?? 1);

        Permission::create($data);

        PermCache::forgetMaps();
        return back()->with('success', 'Permission created');
    }

    public function attachRoute(Request $req, Permission $permission)
    {
        $payload = $req->validate([
            'route_name' => ['required', 'max:191'],
        ]);

        PermissionRoute::updateOrCreate(
            ['permission_id' => $permission->id, 'route_name' => $payload['route_name']],
            []
        );

        PermCache::forgetMaps();
        return back()->with('success', 'Route attached');
    }

    public function detachRoute(Permission $permission, string $routeName)
    {
        PermissionRoute::where([
            'permission_id' => $permission->id,
            'route_name'    => $routeName,
        ])->delete();

        PermCache::forgetMaps();
        return back()->with('success', 'Route detached');
    }
}
