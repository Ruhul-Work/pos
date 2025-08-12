<?php
namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{

    // --- Web (session) ---
    public function showLogin()
    {
        return view('auth.layouts.login');
    }

    public function showRegister()
    {
        return view('auth.layouts.register');
    }

    public function register(Request $req)
    {

        $data = $req->validate([
            'name'      => ['required', 'string', 'max:150'],
            'email'     => ['nullable', 'email', 'max:191', 'unique:users,email', 'required_without_all:phone,username'],
            'phone'     => ['nullable', 'string', 'max:50', 'unique:users,phone', 'required_without_all:email,username'],
            'username'  => ['nullable', 'string', 'max:100', 'unique:users,username', 'required_without_all:email,phone'],
            'password'  => ['required', 'string', 'min:6', 'confirmed'],
            'role_id'   => ['nullable', 'integer'],
            'branch_id' => ['nullable', 'integer'],
        ], [
            'email.required_without_all'    => 'Email/Phone/Username এর যেকোনো একটি দিন।',
            'phone.required_without_all'    => 'Email/Phone/Username এর যেকোনো একটি দিন।',
            'username.required_without_all' => 'Email/Phone/Username এর যেকোনো একটি দিন।',
        ]);

        // username auto-generate (optional)
        if (empty($data['username'])) {
            $base = strtolower(preg_replace('/\s+/', '', $data['name']));
            $try  = $base;
            $i    = 1;
            while (User::where('username', $try)->exists()) {
                $try = $base . $i++;
            }
            $data['username'] = $try;
        }

        $roleId = $data['role_id'] ?? 1;

        $user            = new User();
        $user->name      = $data['name'];
        $user->email     = $data['email'] ?? null;
        $user->phone     = $data['phone'] ?? null;
        $user->username  = $data['username'] ?? null;
        $user->password  = Hash::make($data['password']);
        $user->role_id   = $roleId;
        $user->branch_id = $data['branch_id'] ?? null;
        $user->status    = 1;
        $user->save();

        // রেজিস্ট্রেশন শেষে auto-login (চাইলে বন্ধ রাখতে পারেন)
        Auth::login($user);
        $req->session()->regenerate();

        return redirect()->route('backend.dashboard')->with('success', 'Registration successful!');
        // auto-login না চাইলে:
        // return redirect()->route('login')->with('success','Registration successful! Please login.');
    }

    public function login(Request $req)
    {
        $data = $req->validate([
            'identifier' => 'required|string',
            'password'   => 'required|string',
            'remember'   => 'nullable|boolean',
        ]);

        $user = User::query()
            ->where('email', $data['identifier'])
            ->orWhere('phone', $data['identifier'])
            ->orWhere('username', $data['identifier'])
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password) || $user->status != 1) {
            return back()->withErrors(['identifier' => 'Invalid credentials'])->withInput();
        }

        Auth::login($user, (bool) ($data['remember'] ?? false));
        $req->session()->regenerate(); // session fixation protection
        return redirect()->intended(route('backend.dashboard'));
    }

    public function logout(Request $req)
    {
        Auth::logout();
        $req->session()->invalidate();
        $req->session()->regenerateToken();
        return redirect()->route('backend.login');
    }
}
