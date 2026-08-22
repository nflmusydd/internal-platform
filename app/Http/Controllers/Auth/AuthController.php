<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect('/');
        }
        return view('auth.login');
    }

    public function showAuth()
    {
        if (Auth::check()) {
            return redirect('/');
        }
        return view('auth.auth');
    }

    public function login(Request $request)
    {
        $request->merge([
            'email' => strtolower(trim($request->email ?? '')),
        ]);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => __('auth.failed'),
            ])->onlyInput('email');
        }

        if (!$user->is_active) {
            Auth::logout();
            return back()->withErrors([
                'email' => __('auth.inactive'),
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended('/');
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return redirect('/');
        }
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->merge([
            'name' => trim($request->name ?? ''),
            'email' => strtolower(trim($request->email ?? '')),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'is_active' => 1,
                'created_by' => 0,
                'updated_by' => 0,
            ]);

            $user->update(['created_by' => $user->id, 'updated_by' => $user->id]);

            DB::commit();
            return redirect('/login')->with('success', __('auth.register_success'));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Register Error', ['message' => $e->getMessage()]);
            return back()->withErrors(['email' => __('auth.register_failed')])->onlyInput('email');
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
