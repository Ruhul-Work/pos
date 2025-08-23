<?php
namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\backend\Role;
use App\Models\backend\User;
use App\Support\PermCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $req)
    {
        $q = trim($req->get('q', ''));

        $users = User::with('role')
            ->when($q, function ($query) use ($q) {
                $query->where(function ($x) use ($q) {
                    $x->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('username', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('backend.modules.users.index', compact('users', 'q'));
    }

    public function create(Request $req)
    {
        // current user সুপার না হলে সুপার রোল লিস্ট থেকে বাদ
        $roles = Role::query()
            ->when(! ($req->user()?->isSuper()), fn($q) => $q->where('is_super', false))
            ->orderByDesc('is_super')->orderBy('name')
            ->get();

        return view('backend.modules.users.create', compact('roles'));
    }

    public function store(Request $req)
    {
        $data = $req->validate([
            'name'      => ['required', 'string', 'max:150'],
            'email'     => ['nullable', 'email', 'max:191', 'unique:users,email'],
            'username'  => ['nullable', 'string', 'max:100', 'unique:users,username'],
            'phone'     => ['nullable', 'string', 'max:50', 'unique:users,phone'],
            'password'  => ['required', 'string', 'min:6', 'confirmed'],
            'role_id'   => ['required', 'integer', 'exists:roles,id'],
            'branch_id' => ['nullable', 'integer'],
            'status'    => ['required', 'in:0,1'],
        ]);

        $user            = new User();
        $user->name      = $data['name'];
        $user->email     = $data['email'] ?? null;
        $user->username  = $data['username'] ?? null;
        $user->phone     = $data['phone'] ?? null;
        $user->password  = Hash::make($data['password']);
        $user->role_id   = (int) $data['role_id'];
        $user->branch_id = $data['branch_id'] ?? null;
        $user->status    = (int) $data['status'];
        $user->meta      = null;
        $user->save();

        return redirect()->route('usermanage.users.index')
            ->with('success', 'User created & role assigned.');
    }

    public function edit(Request $req, User $user)
    {
        // non-super ইউজার যেন super রোল/ইউজার এডিট করতে না পারে
        if (! ($req->user()?->isSuper())) {
            if (($user->role->is_super ?? false)) {
                abort(403, 'Cannot edit a super user.');
            }
        }

        $roles = Role::query()
            ->when(! ($req->user()?->isSuper()), fn($q) => $q->where('is_super', false))
            ->orderByDesc('is_super')->orderBy('name')
            ->get();

        return view('backend.modules.users.edit', compact('user', 'roles'));
    }

    public function update(Request $req, User $user)
    {
        // validation: unique ignore current user
        $data = $req->validate([
            'name'      => ['required', 'string', 'max:150'],
            'email'     => ['nullable', 'email', 'max:191'],
            'username'  => ['nullable', 'string', 'max:100'],
            'phone'     => ['nullable', 'string', 'max:50'],
            'password'  => ['nullable', 'string', 'min:6', 'confirmed'],
            'role_id'   => ['required', 'integer'],
            'branch_id' => ['nullable', 'integer'],
            'status'    => ['required', 'in:0,1'],
        ]);

        // কমপক্ষে email/username একটাও না থাকলে ব্লক করুন
        if (empty($data['email']) && empty($data['username'])) {
            return back()->withInput()->withErrors([
                'email'    => 'Email বা Username—অন্তত একটি দিতে হবে।',
                'username' => 'Email বা Username—অন্তত একটি দিতে হবে।',
            ]);
        }

        // super guard: non-super ইউজার যেন super রোলে সেট করতে না পারে/সুপার ইউজার এডিট না করে
        $acting  = $req->user();
        $newRole = Role::findOrFail((int) $data['role_id']);
        if (! ($acting?->isSuper())) {
            if (($user->role->is_super ?? false) || $newRole->is_super) {
                abort(403, 'Insufficient permission to set/edit super role.');
            }
        }

        // explicit assignment
        $user->name     = $data['name'];
        $user->email    = $data['email'] ?? null;
        $user->username = $data['username'] ?? null;
        $user->phone    = $data['phone'] ?? null;
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->role_id   = (int) $data['role_id'];
        $user->branch_id = $data['branch_id'] ?? null;
        $user->status    = (int) $data['status'];
        $user->save();

        PermCache::forgetUser((int) $user->id);

        return redirect()->route('usermanage.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy( Request $req, User $user)
{
    // 1) নিজের অ্যাকাউন্ট ডিলিট ব্লক
    if ((int)$req->user()->id === (int)$user->id) {
        return back()->with('error', 'You cannot delete your own account.');
    }

    // 2) non-super → super ইউজার ডিলিট ব্লক
    if (! $req->user()->isSuper() && (bool)($user->role->is_super ?? false)) {
        abort(403, 'Insufficient permission to delete a super user.');
    }

    \DB::transaction(function () use ($user) {
        // soft delete user
        $user->delete();

        // (ঐচ্ছিক) orphan clean-up: user overrides মুছে দিন
        \DB::table('user_permissions')->where('user_id', $user->id)->delete();

        // cache bust
        \App\Support\PermCache::forgetUser((int)$user->id);
    });

    return back()->with('success', 'User deleted successfully.');
}


}
