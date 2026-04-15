<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthApiController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'role' => ['required', 'in:civilian,responder'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
            'is_admin' => false,
        ]);

        $user->responderProfile()->create([
            'phone' => $validated['phone'] ?? null,
            'assigned_station' => $validated['role'] === 'responder' ? 'Bontoc HQ' : 'Civilian Mobile',
            'emergency_contact_name' => $validated['name'].' Emergency Contact',
            'emergency_contact_phone' => $validated['phone'] ?? null,
            'connectivity_mode' => 'auto_select',
            'notification_profile' => $validated['role'] === 'responder'
                ? 'critical_alerts,push_notifications,sms_backup'
                : 'push_notifications,sms_backup',
        ]);

        $token = $this->issueToken($user);

        return response()->json([
            'message' => 'Account created successfully.',
            'token' => $token,
            'user' => $this->userPayload($user->fresh('responderProfile')),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid email or password.',
            ], 422);
        }

        $token = $this->issueToken($user);

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'user' => $this->userPayload($user->fresh('responderProfile')),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'user' => $this->userPayload($user->fresh('responderProfile')),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->forceFill(['api_token' => null])->save();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    private function issueToken(User $user): string
    {
        $plainTextToken = Str::random(80);

        $user->forceFill([
            'api_token' => hash('sha256', $plainTextToken),
        ])->save();

        return $plainTextToken;
    }

    private function userPayload(User $user): array
    {
        $profile = $user->responderProfile;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'is_admin' => (bool) $user->is_admin,
            'phone' => $profile?->phone,
            'station' => $profile?->assigned_station ?? ($user->isCivilian() ? 'Civilian Mobile' : 'Bontoc HQ'),
            'connectivity_mode' => $profile?->connectivity_mode ?? 'auto_select',
            'notification_profile' => $profile?->notification_profile ?? 'push_notifications',
        ];
    }
}
