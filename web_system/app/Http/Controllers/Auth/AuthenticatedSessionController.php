<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
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
            'identity' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        if (! Auth::attempt([
            'email' => $credentials['identity'],
            'password' => $credentials['password'],
        ], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'identity' => 'The provided account credentials could not be verified.',
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user();
        $targetUrl = $user?->is_admin
            ? route('admin.dashboard')
            : ($user?->isCivilian() ? route('reports.create') : route('dashboard'));

        return redirect()->intended($targetUrl);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('welcome');
    }
}
