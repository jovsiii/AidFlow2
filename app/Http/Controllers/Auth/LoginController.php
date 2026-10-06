<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/** Handles login views, Firebase-backed authentication, session cleanup, and role redirects. */
class LoginController extends Controller
{
    /** Show the login form, or send an existing session to its role's home page. */
    public function showLogin()
    {
        if (session()->has('user')) {
            return $this->redirectForRole(session('user.role'));
        }

        return view('auth.login');
    }

    /** Validate credentials, establish the session, and route the account by role. */
    public function login(Request $request, FirebaseService $firebase)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $account = $firebase->getAccount($request->username);
        if (!$account) {
            return back()->withErrors([
                'username' => 'Account not found.',
            ]);
        }

        if (($account['password'] ?? '') !== $request->password) {
            return back()->withErrors([
                'password' => 'Incorrect password.',
            ]);
        }


        session([
            'user' => $account,
            'username' => $request->username,
        ]);

        $request->session()->regenerate();

        return $this->redirectForRole($account['role'] ?? null);
    }

    /** Clear the authenticated session and return to the login page. */
    public function logout(Request $request)
    {
        $request->session()->forget(['user', 'username']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /** Choose the home page available to the account's role. */
    private function redirectForRole(?string $role)
    {
        return redirect()->route($role === 'admin' ? 'dashboard' : 'phoneFeatures');
    }
}