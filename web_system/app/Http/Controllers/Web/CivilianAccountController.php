<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\IncidentReport;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CivilianAccountController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->redirectIfCivilian($request->user())) {
            return $redirect;
        }

        $activeStatuses = ['received', 'acknowledged', 'dispatched', 'responding'];
        $civilianAccounts = User::query()
            ->where('role', 'civilian')
            ->with('responderProfile')
            ->orderBy('name')
            ->get();

        return view('civilian-accounts.index', [
            'civilianAccounts' => $civilianAccounts,
            'stats' => [
                'active' => IncidentReport::query()->whereIn('status', $activeStatuses)->count(),
                'civilian_accounts' => $civilianAccounts->count(),
                'updated_today' => User::query()->where('role', 'civilian')->whereDate('updated_at', now()->toDateString())->count(),
                'missing_phone' => $civilianAccounts->filter(static fn (User $civilian): bool => blank($civilian->responderProfile?->phone))->count(),
            ],
            'fatalAlerts' => IncidentReport::query()
                ->where('severity', 'Fatal')
                ->whereIn('status', $activeStatuses)
                ->latest()
                ->take(5)
                ->get(),
            'connectivity' => $this->connectivityIntelligence(),
        ]);
    }

    public function update(Request $request, User $civilianAccount): RedirectResponse
    {
        if ($redirect = $this->redirectIfCivilian($request->user())) {
            return $redirect;
        }

        abort_unless($civilianAccount->isCivilian(), 404);

        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($civilianAccount->id)],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ]);

        $civilianAccount->email = $validated['email'];
        $passwordUpdated = filled($validated['password'] ?? null);

        if ($passwordUpdated) {
            $civilianAccount->password = $validated['password'];
        }

        $civilianAccount->save();

        $statusMessage = $passwordUpdated
            ? 'Civilian account email and password updated successfully.'
            : 'Civilian account email updated successfully.';

        return redirect()
            ->to(route('civilian-accounts.index').'#civilian-account-'.$civilianAccount->id)
            ->with('status', $statusMessage);
    }

    private function redirectIfCivilian(?User $user): ?RedirectResponse
    {
        if ($user?->isCivilian()) {
            return redirect()
                ->route('dashboard')
                ->with('status', 'Civilian account management is only available to responder accounts.');
        }

        return null;
    }

    private function connectivityIntelligence(): array
    {
        $recentReports = IncidentReport::query()
            ->latest()
            ->take(12)
            ->get();

        $loraRecent = $recentReports->where('transmission_type', 'lora')->count();
        $onlineRecent = $recentReports->where('transmission_type', 'online')->count();

        return [
            'online_status' => $onlineRecent > 0 ? 'Online gateway stable' : 'Online standby',
            'lora_status' => $loraRecent > 0 ? 'LoRa fallback active' : 'LoRa standby',
            'internet_status' => $loraRecent > $onlineRecent ? 'Internet degraded - fallback preferred' : 'Internet uplink healthy',
            'gateway_status' => $loraRecent > 0 || $onlineRecent > 0 ? 'Gateway synchronized' : 'Awaiting field traffic',
            'websocket_status' => 'Reverb live sync online',
            'queue_status' => 'Immediate broadcast channel',
            'api_status' => 'Laravel API healthy',
        ];
    }
}
