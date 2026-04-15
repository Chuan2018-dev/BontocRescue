<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ResponderProfile;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::min(6)],
            'role' => ['required', 'in:civilian,responder'],
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => $validated['role'],
                'is_admin' => false,
            ]);

            ResponderProfile::create([
                'user_id' => $user->id,
                'phone' => $validated['phone'],
                'assigned_station' => $validated['role'] === 'responder' ? 'Bontoc HQ' : 'Civilian Mobile',
                'emergency_contact_name' => $validated['name'].' Emergency Contact',
                'emergency_contact_phone' => $validated['phone'],
                'connectivity_mode' => 'auto_select',
                'notification_profile' => $validated['role'] === 'responder'
                    ? 'critical_alerts,push_notifications,sms_backup'
                    : 'push_notifications,sms_backup',
            ]);

            return $user;
        });

        event(new Registered($user));
        Auth::login($user);

        if ($user->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route($user->isCivilian() ? 'reports.create' : 'dashboard');
    }
}
