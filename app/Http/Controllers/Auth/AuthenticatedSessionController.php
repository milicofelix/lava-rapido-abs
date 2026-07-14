<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\User;
use App\Support\Access\AccessControl;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'As credenciais informadas não conferem.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        $user = $request->user();
        $fallbackRoute = match (true) {
            $user?->hasRole(User::ROLE_SUPER_ADMIN) => route('super-admin.location-requests.index'),
            AccessControl::allows($user, AccessControl::VIEW_DASHBOARD) => route('dashboard'),
            AccessControl::allows($user, AccessControl::VIEW_KANBAN) => route('kanban'),
            default => route('login'),
        };

        return redirect()->intended($fallbackRoute);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
