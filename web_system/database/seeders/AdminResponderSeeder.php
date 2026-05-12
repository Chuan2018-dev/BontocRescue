<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminResponderSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin.responder@stitch.local'],
            [
                'name' => 'Admin Responder',
                'password' => 'AdminResponder123!',
                'is_admin' => true,
                'role' => 'responder',
            ]
        );

        $admin->responderProfile()->updateOrCreate(
            ['user_id' => $admin->id],
            [
                'phone' => '09170000001',
                'assigned_station' => 'Bontoc Command Center',
                'emergency_contact_name' => 'Dispatch Supervisor',
                'emergency_contact_phone' => '09170000002',
                'connectivity_mode' => 'auto_select',
                'notification_profile' => 'critical_alerts,push_notifications,sms_backup',
            ]
        );
    }
}