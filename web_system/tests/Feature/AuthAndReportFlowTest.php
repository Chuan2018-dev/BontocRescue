<?php

namespace Tests\Feature;

use App\Events\IncidentFeedUpdated;
use App\Models\IncidentReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthAndReportFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_screen_wires_role_cards_password_fields_and_updated_branding(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('data-auth-toggle-target="password"', false)
            ->assertSee('data-auth-toggle-target="password_confirmation"', false)
            ->assertSee('name="role"', false)
            ->assertSee('type="radio"', false)
            ->assertSee('Min. 6 characters required')
            ->assertSee('Bontoc Rescue')
            ->assertSee('User / Civilian')
            ->assertSee('Responder')
            ->assertSee('AI-Powered LoRa Emergency Response and Severity Detection System with Intelligent Location Monitoring');
    }

    public function test_login_and_welcome_pages_show_updated_system_branding(): void
    {
        $branding = 'AI-Powered LoRa Emergency Response and Severity Detection System with Intelligent Location Monitoring';
        $shortBrand = 'Bontoc Rescue';

        $this->get('/')
            ->assertOk()
            ->assertSee($shortBrand)
            ->assertSee($branding)
            ->assertSee('Severity AI');

        $this->get('/login')
            ->assertOk()
            ->assertSee($shortBrand)
            ->assertSee($branding)
            ->assertSee('data-auth-toggle-target="password"', false)
            ->assertSee('Minimum of 6 characters.')
            ->assertSee('Register an account');
    }

    public function test_system_version_endpoint_returns_no_store_json_for_auto_updates(): void
    {
        $response = $this->get(route('system.version'))
            ->assertOk()
            ->assertJsonStructure(['version', 'generated_at']);

        $cacheControl = (string) $response->headers->get('Cache-Control');

        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
    }

    public function test_mobile_api_registration_accepts_civilian_role_selection(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Civilian Reporter',
            'phone' => '09171234567',
            'email' => 'civilian@example.com',
            'password' => 'abc123',
            'password_confirmation' => 'abc123',
            'role' => 'civilian',
        ])
            ->assertCreated()
            ->assertJsonPath('user.role', 'civilian')
            ->assertJsonPath('user.station', 'Civilian Mobile');

        $user = User::query()->where('email', 'civilian@example.com')->firstOrFail();

        $this->assertSame('civilian', $user->role);
    }

    public function test_web_registration_accepts_civilian_role_selection(): void
    {
        $this->post('/register', [
            'name' => 'Civilian Reporter',
            'phone' => '09179990000',
            'email' => 'civilian.web@example.com',
            'password' => 'abc123',
            'password_confirmation' => 'abc123',
            'role' => 'civilian',
        ])->assertRedirect(route('reports.create'));

        $user = User::query()->where('email', 'civilian.web@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('civilian', $user->role);
        $this->assertSame('Civilian Mobile', $user->responderProfile?->assigned_station);
    }

    public function test_civilian_dashboard_uses_a_reporting_focused_design(): void
    {
        $civilian = User::factory()->create([
            'name' => 'Civilian Reporter',
            'role' => 'civilian',
        ]);

        IncidentReport::query()->create([
            'report_code' => 'INC-CIV-DASH-1',
            'reported_by' => $civilian->id,
            'reporter_name' => $civilian->name,
            'incident_type' => 'Road Obstruction',
            'severity' => 'Minor',
            'status' => 'received',
            'channel' => 'Internet',
            'transmission_type' => 'online',
            'location_text' => 'Bontoc Main Road',
            'description' => 'Tree branch blocking one lane.',
            'transmitted_at' => now(),
            'status_updated_at' => now(),
        ]);

        $this->actingAs($civilian)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Civilian Reporting')
            ->assertSee('Send Emergency Report')
            ->assertSee('Open Report History')
            ->assertSee('Open Civilian Profile')
            ->assertDontSee('Responder Command Deck')
            ->assertDontSee('Open Full Incident Feed');
    }

    public function test_civilian_report_history_only_shows_own_reports_and_hides_responder_controls(): void
    {
        $civilian = User::factory()->create([
            'role' => 'civilian',
            'name' => 'Civilian History User',
        ]);
        $responder = User::factory()->create([
            'role' => 'responder',
            'name' => 'Responder Owner',
        ]);

        $ownReport = IncidentReport::query()->create([
            'report_code' => 'INC-CIV-HISTORY-1',
            'reported_by' => $civilian->id,
            'reporter_name' => $civilian->name,
            'incident_type' => 'Vehicle Skid',
            'severity' => 'Serious',
            'status' => 'received',
            'channel' => 'Internet',
            'transmission_type' => 'online',
            'location_text' => 'Mountain Curve',
            'description' => 'Vehicle lost control on wet pavement.',
            'transmitted_at' => now(),
            'status_updated_at' => now(),
        ]);

        $otherReport = IncidentReport::query()->create([
            'report_code' => 'INC-OTHER-HISTORY-2',
            'reported_by' => $responder->id,
            'reporter_name' => $responder->name,
            'incident_type' => 'Flood Alert',
            'severity' => 'Serious',
            'status' => 'received',
            'channel' => 'Internet',
            'transmission_type' => 'online',
            'location_text' => 'Riverbank',
            'description' => 'Water level rising quickly.',
            'transmitted_at' => now(),
            'status_updated_at' => now(),
        ]);

        $this->actingAs($civilian)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('My Submitted Reports')
            ->assertSee('Recent status updates')
            ->assertSee($ownReport->report_code)
            ->assertDontSee($otherReport->report_code)
            ->assertSee('Track this report')
            ->assertSee('Status timeline')
            ->assertSee('Progress tracker')
            ->assertSee('Estimated response time')
            ->assertSee('My Report Actions')
            ->assertSee('Delete Report')
            ->assertDontSee('Save Assign')
            ->assertDontSee('Set Open')
            ->assertDontSee('Set Done')
            ->assertDontSee('Set Reject');

        $this->actingAs($civilian)
            ->get(route('reports.show', $otherReport))
            ->assertNotFound();
    }

    public function test_civilian_report_history_empty_state_is_polished_and_actionable(): void
    {
        $civilian = User::factory()->create([
            'role' => 'civilian',
            'name' => 'Empty History User',
        ]);

        $this->actingAs($civilian)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('No Report History Yet')
            ->assertSee('Recent status updates')
            ->assertSee('Status notifications will appear here after you submit your first report.')
            ->assertSee('Send New Report')
            ->assertSee('What you will see here')
            ->assertDontSee('No reports matched the current filters.');
    }

    public function test_civilian_profile_uses_civilian_profile_and_history_labels(): void
    {
        $civilian = User::factory()->create([
            'name' => 'Civilian Profile User',
            'email' => 'civilian.profile@example.com',
            'role' => 'civilian',
        ]);
        $civilian->responderProfile()->create([
            'phone' => '09175557777',
            'assigned_station' => 'Civilian Mobile',
            'connectivity_mode' => 'auto_select',
            'notification_profile' => 'critical_alerts',
        ]);

        $this->actingAs($civilian)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('Civilian Profile')
            ->assertSee('History of Report')
            ->assertSee('Update your information')
            ->assertSee('Change profile picture')
            ->assertSee('Auto-crop and resize')
            ->assertSee('Auto-crop target: 512 x 512 pixels.')
            ->assertSee('Capture from camera')
            ->assertSee('Change password')
            ->assertSee('data-profile-photo-input', false)
            ->assertSee('data-profile-photo-crop-status', false)
            ->assertSee('data-profile-photo-camera-input', false)
            ->assertSee('Reset preview')
            ->assertSee('Show passwords')
            ->assertSee('civilian.profile@example.com')
            ->assertSee('Civilian');
    }

    public function test_civilian_can_update_profile_information_and_profile_photo(): void
    {
        Storage::fake();

        $civilian = User::factory()->create([
            'name' => 'Original Civilian',
            'email' => 'original.civilian@example.com',
            'role' => 'civilian',
        ]);
        $civilian->responderProfile()->create([
            'phone' => '09170000001',
            'assigned_station' => 'Civilian Mobile',
            'emergency_contact_name' => 'Old Contact',
            'emergency_contact_phone' => '09170000002',
            'connectivity_mode' => 'auto_select',
            'notification_profile' => 'critical_alerts',
        ]);

        $photo = UploadedFile::fake()->image('civilian-avatar.jpg');

        $this->actingAs($civilian)
            ->put(route('profile.update'), [
                'name' => 'Updated Civilian',
                'email' => 'updated.civilian@example.com',
                'phone' => '09178889999',
                'assigned_station' => 'Civilian Mobile Unit A',
                'emergency_contact_name' => 'Jane Contact',
                'emergency_contact_phone' => '09175554444',
                'password' => 'UpdatedCivilianPass123!',
                'password_confirmation' => 'UpdatedCivilianPass123!',
                'profile_photo' => $photo,
            ])->assertRedirect(route('profile.show'));

        $civilian->refresh();

        $this->assertSame('Updated Civilian', $civilian->name);
        $this->assertSame('updated.civilian@example.com', $civilian->email);
        $this->assertNotNull($civilian->profile_photo_path);
        $this->assertTrue(Hash::check('UpdatedCivilianPass123!', $civilian->password));
        $this->assertSame('09178889999', $civilian->responderProfile?->phone);
        $this->assertSame('Civilian Mobile Unit A', $civilian->responderProfile?->assigned_station);
        $this->assertSame('Jane Contact', $civilian->responderProfile?->emergency_contact_name);
        $this->assertSame('09175554444', $civilian->responderProfile?->emergency_contact_phone);

        Storage::assertExists($civilian->profile_photo_path);

        $this->actingAs($civilian)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('Updated Civilian')
            ->assertSee('updated.civilian@example.com')
            ->assertSee('Civilian Mobile Unit A')
            ->assertSee('Auto-crop and resize')
            ->assertSee('Capture from camera')
            ->assertSee('Change password')
            ->assertSee('Remove current photo')
            ->assertSee('Show passwords')
            ->assertSee('Save Profile Changes');

        $this->actingAs($civilian)
            ->get(route('profile.photo'))
            ->assertOk();
    }

    public function test_civilian_can_remove_existing_profile_photo(): void
    {
        Storage::fake();

        $civilian = User::factory()->create([
            'name' => 'Photo Removal User',
            'email' => 'photo.removal@example.com',
            'role' => 'civilian',
        ]);
        $civilian->responderProfile()->create([
            'phone' => '09170000111',
            'assigned_station' => 'Civilian Mobile',
            'connectivity_mode' => 'auto_select',
            'notification_profile' => 'critical_alerts',
        ]);

        $existingPhoto = UploadedFile::fake()->image('existing-photo.jpg')->store('profile-photos');
        $civilian->forceFill(['profile_photo_path' => $existingPhoto])->save();

        $this->actingAs($civilian)
            ->put(route('profile.update'), [
                'name' => 'Photo Removal User',
                'email' => 'photo.removal@example.com',
                'phone' => '09170000111',
                'assigned_station' => 'Civilian Mobile',
                'remove_profile_photo' => '1',
            ])->assertRedirect(route('profile.show'));

        $civilian->refresh();

        $this->assertNull($civilian->profile_photo_path);
        Storage::assertMissing($existingPhoto);
    }

    public function test_civilian_create_report_page_uses_civilian_reporting_design(): void
    {
        $civilian = User::factory()->create([
            'role' => 'civilian',
        ]);

        $this->actingAs($civilian)
            ->get(route('reports.create'))
            ->assertOk()
            ->assertSee('Civilian Reporting Flow')
            ->assertSee('Civilian Emergency Report')
            ->assertSee('Send Emergency Report')
            ->assertSee('Open Report History')
            ->assertSee('Check Device Readiness')
            ->assertSee('Camera-first emergency capture')
            ->assertSee('Capture Photo')
            ->assertSee('Record Video')
            ->assertSee('Capture Selfie')
            ->assertSee('Lock GPS')
            ->assertSee('Verification selfie')
            ->assertSee('Required before sending')
            ->assertSee('The Send Emergency Report button will stay locked')
            ->assertSee('Offline draft queue')
            ->assertSee('Save Draft Now')
            ->assertSee('Restore Latest Draft')
            ->assertSee('data-report-draft-form', false)
            ->assertSee('data-draft-save', false)
            ->assertSee('data-draft-restore', false)
            ->assertSee('data-capture-trigger="photo"', false)
            ->assertSee('data-capture-trigger="video"', false)
            ->assertSee('data-capture-trigger="selfie"', false)
            ->assertSee('Get Current Latitude and Longitude')
            ->assertSee('data-geo-fill-button', false)
            ->assertSee('data-geo-latitude', false)
            ->assertSee('data-geo-longitude', false)
            ->assertDontSee('Manual Report Entry');
    }

    public function test_responder_create_report_page_keeps_manual_dashboard_entry_design(): void
    {
        $responder = User::factory()->create([
            'role' => 'responder',
        ]);

        $this->actingAs($responder)
            ->get(route('reports.create'))
            ->assertOk()
            ->assertSee('Manual Report Entry')
            ->assertSee('Create an incident from the web dashboard.')
            ->assertSee('Open Incident Form')
            ->assertSee('Check Device Readiness')
            ->assertSee('Submit Report')
            ->assertSee('Get Current Latitude and Longitude')
            ->assertDontSee('Civilian Reporting Flow');
    }

    public function test_civilian_web_report_submission_accepts_scene_media_and_verification_selfie(): void
    {
        Storage::fake();
        Event::fake([IncidentFeedUpdated::class]);

        $civilian = User::factory()->create([
            'role' => 'civilian',
            'name' => 'Camera First User',
        ]);

        $this->actingAs($civilian)
            ->post(route('reports.store'), [
                'incident_type' => 'Motorcycle Collision',
                'severity' => '',
                'transmission_type' => 'online',
                'location_text' => 'Barangay Poblacion, Bontoc',
                'latitude' => '10.354270',
                'longitude' => '124.970400',
                'description' => 'Motorcycle and tricycle collision near the main road.',
                'evidence' => UploadedFile::fake()->image('scene-photo.jpg'),
                'selfie' => UploadedFile::fake()->image('verification-selfie.jpg'),
            ])
            ->assertRedirect();

        $report = IncidentReport::query()->latest('id')->firstOrFail();

        $this->assertSame($civilian->id, $report->reported_by);
        $this->assertSame('photo', $report->evidence_type);
        $this->assertNotNull($report->evidence_path);
        $this->assertNotNull($report->reporter_selfie_path);
        $this->assertNotNull($report->reporter_selfie_captured_at);
        Storage::assertExists($report->evidence_path);
        Storage::assertExists($report->reporter_selfie_path);
        Event::assertDispatched(IncidentFeedUpdated::class);
    }

    public function test_civilian_report_submission_requires_photo_selfie_gps_and_description(): void
    {
        Storage::fake();

        $civilian = User::factory()->create([
            'role' => 'civilian',
            'name' => 'Locked Submission User',
        ]);

        $this->actingAs($civilian)
            ->from(route('reports.create'))
            ->post(route('reports.store'), [
                'incident_type' => 'Road Incident',
                'severity' => '',
                'transmission_type' => 'online',
                'location_text' => 'Barangay Poblacion, Bontoc',
                'description' => 'Blocked lane after collision.',
            ])
            ->assertRedirect(route('reports.create'))
            ->assertSessionHasErrors(['latitude', 'longitude', 'evidence', 'selfie']);
    }

    public function test_permission_readiness_page_checks_device_access_before_reporting(): void
    {
        $civilian = User::factory()->create([
            'role' => 'civilian',
            'name' => 'Readiness User',
        ]);

        $this->actingAs($civilian)
            ->get(route('settings.readiness'))
            ->assertOk()
            ->assertSee('Permission Readiness Check')
            ->assertSee('Run Full Check')
            ->assertSee('Camera access')
            ->assertSee('Location access')
            ->assertSee('Notifications')
            ->assertSee('Online and offline state')
            ->assertSee('App install mode')
            ->assertSee('Open Report Form')
            ->assertSee('data-permission-readiness-root', false)
            ->assertSee('data-readiness-action="camera"', false)
            ->assertSee('data-readiness-action="location"', false)
            ->assertSee('data-readiness-action="notifications"', false);

        $this->actingAs($civilian)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('Permission Readiness Check');
    }

    public function test_civilian_success_and_ai_pages_use_civilian_friendly_copy(): void
    {
        $civilian = User::factory()->create([
            'role' => 'civilian',
            'name' => 'Civilian Severity User',
        ]);

        $report = IncidentReport::query()->create([
            'report_code' => 'INC-CIV-FLOW-1',
            'reported_by' => $civilian->id,
            'reporter_name' => $civilian->name,
            'incident_type' => 'Fallen Tree',
            'severity' => 'Serious',
            'status' => 'received',
            'channel' => 'Internet',
            'transmission_type' => 'online',
            'location_text' => 'Mountain Slope',
            'description' => 'A tree fell and blocked the road after heavy rain.',
            'ai_summary' => 'AI triage marked this report as Serious.',
            'ai_confidence' => 84,
            'transmitted_at' => now(),
            'status_updated_at' => now(),
        ]);

        $this->actingAs($civilian)
            ->get(route('reports.success', $report))
            ->assertOk()
            ->assertSee('Emergency Report Sent')
            ->assertSee('Your emergency report was sent successfully.')
            ->assertSee('Open My Report')
            ->assertSee('Return to report history')
            ->assertDontSee('Incident submitted successfully.');

        $this->actingAs($civilian)
            ->get(route('reports.severity', $report))
            ->assertOk()
            ->assertSee('Your AI Severity Result')
            ->assertSee('The system reviewed your report')
            ->assertSee('Back To My Report')
            ->assertDontSee('Back To Incident Details');
    }

    public function test_ai_pages_show_friendly_fallback_note_without_raw_connection_error(): void
    {
        $civilian = User::factory()->create([
            'role' => 'civilian',
            'name' => 'Civilian AI Fallback User',
        ]);

        $report = IncidentReport::query()->create([
            'report_code' => 'INC-CIV-AI-FAIL-1',
            'reported_by' => $civilian->id,
            'reporter_name' => $civilian->name,
            'incident_type' => 'Vehicular Collision',
            'severity' => 'Minor',
            'status' => 'received',
            'channel' => 'Internet',
            'transmission_type' => 'online',
            'location_text' => 'Bontoc, Southern Leyte',
            'description' => 'A civilian submitted a report while the AI photo service was down.',
            'ai_summary' => 'AI triage marked this report as Minor based on limited immediate threat indicators in the submitted description.',
            'ai_confidence' => 72,
            'ai_source' => 'description_fallback',
            'ai_status' => 'fallback',
            'ai_model_name' => 'description_rules',
            'ai_model_version' => 'legacy',
            'ai_error_message' => 'cURL error 7: Failed to connect to 127.0.0.1 port 8100 after 2027 ms: Couldn\'t connect to server',
            'ai_processed_at' => now(),
            'transmitted_at' => now(),
            'status_updated_at' => now(),
        ]);

        $friendlyFallback = 'The AI image analysis service was temporarily unavailable, so the system used description-based severity fallback for this report.';

        $this->actingAs($civilian)
            ->get(route('reports.show', $report))
            ->assertOk()
            ->assertSee('Fallback note')
            ->assertSee($friendlyFallback)
            ->assertSee('Confidence 72%')
            ->assertSee('Description fallback')
            ->assertSee('Fallback mode')
            ->assertDontSee('cURL error 7');

        $this->actingAs($civilian)
            ->get(route('reports.severity', $report))
            ->assertOk()
            ->assertSee('AI quick read')
            ->assertSee('Fallback note')
            ->assertSee($friendlyFallback)
            ->assertSee('Confidence 72%')
            ->assertSee('Description fallback')
            ->assertSee('Fallback mode')
            ->assertDontSee('cURL error 7');
    }

    public function test_report_details_page_renders_clear_ai_summary_badges_for_responders(): void
    {
        $responder = User::factory()->create([
            'role' => 'responder',
        ]);

        $report = IncidentReport::query()->create([
            'report_code' => 'INC-AI-BADGES-0001',
            'reported_by' => $responder->id,
            'reporter_name' => 'Civilian Reporter',
            'reporter_contact' => '09171230000',
            'incident_type' => 'Vehicular Collision',
            'severity' => 'Fatal',
            'status' => 'received',
            'channel' => 'Internet',
            'transmission_type' => 'online',
            'location_text' => 'San Vicente, Bontoc, Southern Leyte',
            'description' => 'Responder needs a clearer AI summary area for quick triage review.',
            'ai_summary' => 'AI image analysis predicted Fatal severity from the uploaded photo evidence.',
            'ai_confidence' => 100,
            'ai_source' => 'python_model',
            'ai_status' => 'complete',
            'ai_model_name' => 'bontoc_southern_leyte_production_candidate_external',
            'ai_model_version' => '0.2.0',
            'ai_processed_at' => now(),
            'transmitted_at' => now(),
            'status_updated_at' => now(),
        ]);

        $this->actingAs($responder)
            ->get(route('reports.show', $report))
            ->assertOk()
            ->assertSee('Fatal output')
            ->assertSee('Confidence 100%')
            ->assertSee('Photo AI model')
            ->assertSee('Ready for triage')
            ->assertSee('AI model')
            ->assertSee('bontoc_southern_leyte_production_candidate_external v0.2.0');
    }

    public function test_civilian_transmission_page_uses_civilian_friendly_copy(): void
    {
        $civilian = User::factory()->create([
            'role' => 'civilian',
            'name' => 'Civilian Transmission User',
        ]);

        $report = IncidentReport::query()->create([
            'report_code' => 'INC-CIV-TRANSMIT-1',
            'reported_by' => $civilian->id,
            'reporter_name' => $civilian->name,
            'incident_type' => 'Road Collision',
            'severity' => 'Serious',
            'status' => 'received',
            'channel' => 'Internet',
            'transmission_type' => 'online',
            'location_text' => 'Bontoc town proper',
            'latitude' => 17.089400,
            'longitude' => 120.977000,
            'description' => 'The vehicle collision happened near the public market.',
            'ai_summary' => 'AI triage marked this report as Serious.',
            'ai_confidence' => 84,
            'transmitted_at' => now(),
            'status_updated_at' => now(),
        ]);

        $this->actingAs($civilian)
            ->get(route('reports.transmissions', $report))
            ->assertOk()
            ->assertSee('Your Transmission Status')
            ->assertSee('Check how your report was delivered through online or LoRa transport')
            ->assertSee('Back To My Report')
            ->assertSee('Return to report history')
            ->assertDontSee('Switching to LoRa Transmission...');
    }

    public function test_civilian_can_delete_own_report_and_attached_files(): void
    {
        Storage::fake();

        $civilian = User::factory()->create([
            'role' => 'civilian',
            'name' => 'Deleting Civilian',
        ]);

        $evidencePath = UploadedFile::fake()->image('delete-photo.jpg')->store('incident-evidence');
        $selfiePath = UploadedFile::fake()->image('delete-selfie.jpg')->store('incident-selfies');

        $report = IncidentReport::query()->create([
            'report_code' => 'INC-CIV-DELETE-1',
            'reported_by' => $civilian->id,
            'reporter_name' => $civilian->name,
            'incident_type' => 'Minor Crash',
            'severity' => 'Minor',
            'status' => 'received',
            'channel' => 'Internet',
            'transmission_type' => 'online',
            'location_text' => 'Bontoc Crossing',
            'description' => 'Civilian wants to remove this test report.',
            'evidence_type' => 'photo',
            'evidence_path' => $evidencePath,
            'evidence_original_name' => 'delete-photo.jpg',
            'reporter_selfie_path' => $selfiePath,
            'reporter_selfie_original_name' => 'delete-selfie.jpg',
            'reporter_selfie_captured_at' => now(),
            'transmitted_at' => now(),
            'status_updated_at' => now(),
        ]);

        $this->actingAs($civilian)
            ->delete(route('reports.destroy', $report))
            ->assertRedirect(route('reports.index'));

        $this->assertDatabaseMissing('incident_reports', ['id' => $report->id]);
        Storage::assertMissing($evidencePath);
        Storage::assertMissing($selfiePath);
    }

    public function test_civilian_cannot_delete_another_users_report(): void
    {
        $civilian = User::factory()->create([
            'role' => 'civilian',
        ]);
        $otherCivilian = User::factory()->create([
            'role' => 'civilian',
        ]);

        $report = IncidentReport::query()->create([
            'report_code' => 'INC-CIV-DELETE-2',
            'reported_by' => $otherCivilian->id,
            'reporter_name' => $otherCivilian->name,
            'incident_type' => 'Road Hazard',
            'severity' => 'Minor',
            'status' => 'received',
            'channel' => 'Internet',
            'transmission_type' => 'online',
            'location_text' => 'Town Proper',
            'description' => 'This report belongs to another civilian.',
            'transmitted_at' => now(),
            'status_updated_at' => now(),
        ]);

        $this->actingAs($civilian)
            ->delete(route('reports.destroy', $report))
            ->assertNotFound();

        $this->assertDatabaseHas('incident_reports', ['id' => $report->id]);
    }

    public function test_responder_can_delete_report_from_current_queue(): void
    {
        $responder = User::factory()->create([
            'role' => 'responder',
        ]);

        $report = IncidentReport::query()->create([
            'report_code' => 'INC-RESP-DELETE-1',
            'reported_by' => $responder->id,
            'reporter_name' => 'Civilian Reporter',
            'incident_type' => 'Vehicular Collision',
            'severity' => 'Serious',
            'status' => 'received',
            'channel' => 'Internet',
            'transmission_type' => 'online',
            'location_text' => 'Main Highway',
            'description' => 'Responder queue report that will be deleted.',
            'transmitted_at' => now(),
            'status_updated_at' => now(),
        ]);

        $this->actingAs($responder)
            ->get(route('reports.show', $report))
            ->assertOk()
            ->assertSee('Delete Report');

        $this->actingAs($responder)
            ->delete(route('reports.destroy', $report))
            ->assertRedirect(route('reports.index'));

        $this->assertDatabaseMissing('incident_reports', ['id' => $report->id]);
    }

    public function test_user_can_register_submit_a_report_and_open_operational_pages(): void
    {
        Event::fake([IncidentFeedUpdated::class]);

        $this->post('/register', [
            'name' => 'Christian Rescue',
            'phone' => '09171234567',
            'email' => 'christian@example.com',
            'password' => 'securepass123',
            'password_confirmation' => 'securepass123',
            'role' => 'responder',
        ])->assertRedirect('/dashboard');

        $user = User::query()->where('email', 'christian@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('responder', $user->role);

        $this->post('/reports', [
            'incident_type' => 'Vehicular Collision',
            'severity' => 'Serious',
            'transmission_type' => 'online',
            'location_text' => 'Bontoc Public Market',
            'latitude' => '17.0894',
            'longitude' => '120.9770',
            'description' => 'Two vehicles collided near the market entrance with injuries reported.',
        ])->assertRedirect();

        $report = IncidentReport::query()->firstOrFail();

        $this->assertSame('received', $report->status);
        $this->assertSame('Serious', $report->severity);
        $this->assertSame('online', $report->transmission_type);
        $this->assertNotNull($report->transmitted_at);
        Event::assertDispatched(IncidentFeedUpdated::class);

        $this->get(route('reports.success', $report))
            ->assertOk()
            ->assertSee($report->report_code);

        $this->get(route('reports.show', $report))
            ->assertOk()
            ->assertSee($report->location_text)
            ->assertSee('Assign responder and update status');

        $this->get(route('reports.severity', $report))
            ->assertOk()
            ->assertSee('AI Severity Analysis');

        $this->get(route('reports.transmissions', $report))
            ->assertOk()
            ->assertSee('Transmission Status');
    }

    public function test_report_details_page_renders_photo_evidence_preview_for_responders(): void
    {
        Storage::fake();

        $responder = User::factory()->create([
            'role' => 'responder',
        ]);
        $photoPath = UploadedFile::fake()->image('scene-photo.jpg')->store('incident-evidence');
        $selfiePath = UploadedFile::fake()->image('verification-selfie.jpg')->store('incident-selfies');
        $report = IncidentReport::query()->create([
            'report_code' => 'INC-PHOTO-0001',
            'reported_by' => $responder->id,
            'reporter_name' => 'Civilian Reporter',
            'reporter_contact' => '09171234567',
            'incident_type' => 'Vehicular Collision',
            'severity' => 'Serious',
            'status' => 'received',
            'channel' => 'Internet',
            'transmission_type' => 'online',
            'location_text' => 'Bontoc Public Market',
            'description' => 'Responder review requires the uploaded scene image.',
            'evidence_type' => 'photo',
            'evidence_path' => $photoPath,
            'evidence_original_name' => 'scene-photo.jpg',
            'reporter_selfie_path' => $selfiePath,
            'reporter_selfie_original_name' => 'verification-selfie.jpg',
            'reporter_selfie_captured_at' => now(),
            'transmitted_at' => now(),
            'status_updated_at' => now(),
        ]);

        $this->actingAs($responder)
            ->get(route('reports.show', $report))
            ->assertOk()
            ->assertSee('<img', false)
            ->assertSee('data-media-viewer-trigger', false)
            ->assertSee('Click to enlarge photo')
            ->assertSee('scene-photo.jpg')
            ->assertSee('Download File')
            ->assertSee('Realtime selfie capture')
            ->assertSee('Click to enlarge verification selfie')
            ->assertSee('Download Selfie')
            ->assertSee(route('reports.evidence', $report), false);

        $this->actingAs($responder)
            ->get(route('reports.evidence', $report))
            ->assertOk();

        $this->actingAs($responder)
            ->get(route('reports.selfie', $report))
            ->assertOk();
    }

    public function test_report_details_page_renders_video_evidence_preview_for_responders(): void
    {
        Storage::fake();

        $responder = User::factory()->create([
            'role' => 'responder',
        ]);
        $videoPath = UploadedFile::fake()->create('scene-video.mp4', 2048, 'video/mp4')->store('incident-evidence');
        $report = IncidentReport::query()->create([
            'report_code' => 'INC-VIDEO-0001',
            'reported_by' => $responder->id,
            'reporter_name' => 'Civilian Reporter',
            'reporter_contact' => '09171234567',
            'incident_type' => 'Road Crash',
            'severity' => 'Fatal',
            'status' => 'received',
            'channel' => 'Internet',
            'transmission_type' => 'online',
            'location_text' => 'Bontoc Highway',
            'description' => 'Responder review requires the uploaded scene video.',
            'evidence_type' => 'video',
            'evidence_path' => $videoPath,
            'evidence_original_name' => 'scene-video.mp4',
            'transmitted_at' => now(),
            'status_updated_at' => now(),
        ]);

        $this->actingAs($responder)
            ->get(route('reports.show', $report))
            ->assertOk()
            ->assertSee('<video', false)
            ->assertSee('data-media-viewer-trigger', false)
            ->assertSee('Click to enlarge video')
            ->assertSee('scene-video.mp4')
            ->assertSee('Download File')
            ->assertSee(route('reports.evidence', $report), false);

        $this->actingAs($responder)
            ->get(route('reports.evidence', ['incidentReport' => $report, 'download' => 1]))
            ->assertOk();
    }

    public function test_report_details_page_shows_distance_readable_location_and_google_maps_actions(): void
    {
        $responder = User::factory()->create([
            'role' => 'responder',
        ]);

        $report = IncidentReport::query()->create([
            'report_code' => 'INC-LOCATION-0001',
            'reported_by' => $responder->id,
            'reporter_name' => 'Civilian Reporter',
            'reporter_contact' => '09171234567',
            'incident_type' => 'Vehicular Collision',
            'severity' => 'Serious',
            'status' => 'received',
            'channel' => 'Internet',
            'transmission_type' => 'online',
            'location_text' => 'Poblacion, Bontoc | GPS 17.08629, 120.97695',
            'latitude' => 17.086290,
            'longitude' => 120.976948,
            'description' => 'Responder location review needs readable address details and map routing actions.',
            'transmitted_at' => now(),
            'status_updated_at' => now(),
        ]);

        $this->actingAs($responder)
            ->get(route('reports.show', $report))
            ->assertOk()
            ->assertSee('Incident location')
            ->assertSee('Poblacion, Bontoc')
            ->assertSee('Barangay / town:')
            ->assertSee('Poblacion, Bontoc')
            ->assertSee('Command center route')
            ->assertSee('Travel time:')
            ->assertSee('Open in Google Maps')
            ->assertSee('Open Route Directions')
            ->assertSee('Open Command Center')
            ->assertSee('Red pin:')
            ->assertSee('Sender mobile location')
            ->assertSee('Bontoc Command Center')
            ->assertSee('map_role', false);
    }

    public function test_report_details_page_renders_live_route_and_turn_by_turn_guidance_when_router_is_available(): void
    {
        Http::fake([
            'https://router.project-osrm.org/route/v1/driving/*' => Http::response([
                'code' => 'Ok',
                'routes' => [[
                    'distance' => 12480.4,
                    'duration' => 1380.0,
                    'geometry' => [
                        'type' => 'LineString',
                        'coordinates' => [
                            [124.97039989612004, 10.354270414923162],
                            [124.9738, 10.3492],
                            [124.9811, 10.3416],
                            [124.9885, 10.3334],
                        ],
                    ],
                    'legs' => [[
                        'steps' => [
                            [
                                'distance' => 850.0,
                                'duration' => 120.0,
                                'name' => 'Bontoc Main Road',
                                'mode' => 'driving',
                                'maneuver' => [
                                    'type' => 'depart',
                                    'modifier' => 'straight',
                                ],
                            ],
                            [
                                'distance' => 6200.0,
                                'duration' => 720.0,
                                'name' => 'Southern Leyte National Road',
                                'mode' => 'driving',
                                'maneuver' => [
                                    'type' => 'turn',
                                    'modifier' => 'right',
                                ],
                            ],
                            [
                                'distance' => 0.0,
                                'duration' => 0.0,
                                'name' => '',
                                'mode' => 'driving',
                                'maneuver' => [
                                    'type' => 'arrive',
                                ],
                            ],
                        ],
                    ]],
                ]],
            ], 200),
        ]);

        config()->set('services.routing.enabled', true);
        config()->set('services.routing.url', 'https://router.project-osrm.org');
        config()->set('services.routing.profile', 'driving');
        config()->set('services.routing.provider', 'OSRM Public Routing');

        $responder = User::factory()->create([
            'role' => 'responder',
        ]);

        $report = IncidentReport::query()->create([
            'report_code' => 'INC-ROUTE-LIVE-0001',
            'reported_by' => $responder->id,
            'reporter_name' => 'Civilian Reporter',
            'reporter_contact' => '09176667777',
            'incident_type' => 'Road Crash',
            'severity' => 'Serious',
            'status' => 'received',
            'channel' => 'Internet',
            'transmission_type' => 'online',
            'location_text' => 'Poblacion, Bontoc, Southern Leyte | GPS 10.3334, 124.9885',
            'latitude' => 10.3334,
            'longitude' => 124.9885,
            'description' => 'Responder needs a live road route and turn-by-turn dispatch guidance.',
            'transmitted_at' => now(),
            'status_updated_at' => now(),
        ]);

        $this->actingAs($responder)
            ->get(route('reports.show', $report))
            ->assertOk()
            ->assertSee('Command center route')
            ->assertSee('Live road route is ready from Bontoc Command Center to the sender mobile location.')
            ->assertSee('Travel time:')
            ->assertSee('data-route', false)
            ->assertSee('Open Route Directions');

        Http::assertSentCount(1);
    }

    public function test_report_feed_renders_inline_evidence_previews_for_responders(): void
    {
        Storage::fake();

        $responder = User::factory()->create([
            'role' => 'responder',
        ]);
        $photoPath = UploadedFile::fake()->image('feed-photo.jpg')->store('incident-evidence');
        $videoPath = UploadedFile::fake()->create('feed-video.mp4', 2048, 'video/mp4')->store('incident-evidence');
        $selfiePath = UploadedFile::fake()->image('feed-selfie.jpg')->store('incident-selfies');

        IncidentReport::query()->create([
            'report_code' => 'INC-FEED-PHOTO',
            'reported_by' => $responder->id,
            'reporter_name' => 'Civilian Reporter',
            'incident_type' => 'Road Crash',
            'severity' => 'Serious',
            'status' => 'received',
            'channel' => 'Internet',
            'transmission_type' => 'online',
            'location_text' => 'Main Highway',
            'description' => 'Photo preview should appear in the incident feed.',
            'evidence_type' => 'photo',
            'evidence_path' => $photoPath,
            'evidence_original_name' => 'feed-photo.jpg',
            'reporter_selfie_path' => $selfiePath,
            'reporter_selfie_original_name' => 'feed-selfie.jpg',
            'reporter_selfie_captured_at' => now(),
            'transmitted_at' => now(),
            'status_updated_at' => now(),
        ]);

        IncidentReport::query()->create([
            'report_code' => 'INC-FEED-VIDEO',
            'reported_by' => $responder->id,
            'reporter_name' => 'Civilian Reporter',
            'incident_type' => 'Vehicular Accident',
            'severity' => 'Fatal',
            'status' => 'received',
            'channel' => 'Internet',
            'transmission_type' => 'online',
            'location_text' => 'Bridge Approach',
            'description' => 'Video preview should appear in the incident feed.',
            'evidence_type' => 'video',
            'evidence_path' => $videoPath,
            'evidence_original_name' => 'feed-video.mp4',
            'transmitted_at' => now(),
            'status_updated_at' => now(),
        ]);

        $this->actingAs($responder)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Evidence')
            ->assertSee('Verification Selfie')
            ->assertSee('<img', false)
            ->assertSee('<video', false)
            ->assertSee('data-media-viewer-trigger', false)
            ->assertSee('Click to enlarge photo')
            ->assertSee('Click to enlarge video')
            ->assertSee('Selfie Verified')
            ->assertSee('Click to enlarge selfie')
            ->assertSee('feed-selfie.jpg')
            ->assertSee('feed-photo.jpg')
            ->assertSee('feed-video.mp4');
    }

    public function test_report_feed_renders_ai_quick_read_badges_for_responders(): void
    {
        $responder = User::factory()->create([
            'role' => 'responder',
        ]);

        IncidentReport::query()->create([
            'report_code' => 'INC-FEED-AI-0001',
            'reported_by' => $responder->id,
            'reporter_name' => 'Civilian Reporter',
            'incident_type' => 'Major Collision',
            'severity' => 'Fatal',
            'status' => 'received',
            'channel' => 'Internet',
            'transmission_type' => 'online',
            'location_text' => 'Bontoc Town Proper',
            'description' => 'Responder incident feed should show AI confidence and review state without opening details.',
            'ai_summary' => 'AI image analysis predicted Fatal severity from the uploaded photo evidence.',
            'ai_confidence' => 100,
            'ai_source' => 'python_model',
            'ai_status' => 'complete',
            'ai_model_name' => 'bontoc_southern_leyte_production_candidate_external',
            'ai_model_version' => '0.2.0',
            'transmitted_at' => now(),
            'status_updated_at' => now(),
        ]);

        $this->actingAs($responder)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('AI quick read')
            ->assertSee('Fatal output')
            ->assertSee('Confidence 100%')
            ->assertSee('Photo AI model')
            ->assertSee('Ready for triage')
            ->assertSee('bontoc_southern_leyte_production_candidate_external v0.2.0');
    }

    public function test_report_feed_can_set_assigned_unassigned_open_and_done(): void
    {
        $coordinator = User::factory()->create([
            'role' => 'responder',
        ]);
        $assignee = User::factory()->create([
            'role' => 'responder',
            'name' => 'Mountain Responder',
        ]);
        $report = IncidentReport::query()->create([
            'report_code' => 'INC-FEED-QUEUE-1',
            'reported_by' => $coordinator->id,
            'reporter_name' => 'Civilian Reporter',
            'incident_type' => 'Flooded Road',
            'severity' => 'Serious',
            'status' => 'received',
            'channel' => 'Internet',
            'transmission_type' => 'online',
            'location_text' => 'Riverside Crossing',
            'description' => 'Quick queue actions should update assignment and status from the feed.',
            'transmitted_at' => now(),
            'status_updated_at' => now(),
        ]);
        $civilian = User::factory()->create([
            'role' => 'civilian',
            'name' => 'Civilian Dropdown User',
        ]);

        $this->actingAs($coordinator)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Save Assign')
            ->assertSee('Set Open')
            ->assertSee('Set Done')
            ->assertSee('Set Reject')
            ->assertSee('Unassigned')
            ->assertSee($assignee->name.' - Responder')
            ->assertDontSee($civilian->name);

        $this->actingAs($coordinator)
            ->post(route('reports.coordination', $report), [
                'assigned_responder_id' => $assignee->id,
                'status' => 'open',
            ])
            ->assertRedirect();

        $report->refresh();
        $this->assertSame($assignee->id, $report->assigned_responder_id);
        $this->assertSame('responding', $report->status);

        $this->actingAs($coordinator)
            ->post(route('reports.coordination', $report), [
                'assigned_responder_id' => '',
                'status' => 'done',
            ])
            ->assertRedirect();

        $report->refresh();
        $this->assertNull($report->assigned_responder_id);
        $this->assertSame('resolved', $report->status);

        $this->actingAs($coordinator)
            ->post(route('reports.coordination', $report), [
                'assigned_responder_id' => '',
                'status' => 'reject',
            ])
            ->assertRedirect();

        $report->refresh();
        $this->assertSame('rejected', $report->status);

        $this->actingAs($coordinator)
            ->post(route('reports.coordination', $report), [
                'assigned_responder_id' => '',
                'status' => 'open',
            ])
            ->assertRedirect();

        $report->refresh();
        $this->assertNull($report->assigned_responder_id);
        $this->assertSame('received', $report->status);
    }

    public function test_report_can_be_assigned_and_status_updated_from_web_dashboard(): void
    {
        $coordinator = User::factory()->create([
            'role' => 'responder',
        ]);
        $assignee = User::factory()->create([
            'role' => 'responder',
            'name' => 'Field Responder',
        ]);
        $report = IncidentReport::query()->create([
            'report_code' => 'INC-TEST-0001',
            'reported_by' => $coordinator->id,
            'reporter_name' => 'Civilian Reporter',
            'incident_type' => 'Motorcycle Crash',
            'severity' => 'Serious',
            'status' => 'received',
            'channel' => 'Internet',
            'transmission_type' => 'online',
            'location_text' => 'Bontoc Highway',
            'description' => 'Crash near the highway shoulder.',
            'transmitted_at' => now(),
            'status_updated_at' => now(),
        ]);

        $this->actingAs($coordinator)
            ->post(route('reports.coordination', $report), [
                'assigned_responder_id' => $assignee->id,
                'status' => 'responding',
                'response_notes' => 'Unit dispatched and en route.',
            ])
            ->assertRedirect();

        $report->refresh();

        $this->assertSame($assignee->id, $report->assigned_responder_id);
        $this->assertSame('responding', $report->status);
        $this->assertSame('Unit dispatched and en route.', $report->response_notes);
        $this->assertNotNull($report->status_updated_at);
    }

    public function test_authenticated_user_can_save_settings_preferences(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/settings/preferences', [
                'critical_alerts' => '1',
                'push_notifications' => '1',
                'sms_backup' => '0',
                'connectivity_mode' => 'lora_fallback',
            ], [
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->assertOk()
            ->assertJson([
                'saved' => true,
                'connectivity_mode' => 'lora_fallback',
            ]);

        $profile = $user->fresh()->responderProfile;

        $this->assertNotNull($profile);
        $this->assertSame('lora_fallback', $profile->connectivity_mode);
        $this->assertSame('critical_alerts,push_notifications', $profile->notification_profile);
    }

    public function test_database_seeder_creates_admin_responder_account(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin.responder@stitch.local')->first();

        $this->assertNotNull($admin);
        $this->assertTrue((bool) $admin->is_admin);
        $this->assertSame('responder', $admin->role);
        $this->assertTrue(Hash::check('AdminResponder123!', $admin->password));
        $this->assertNotNull($admin->responderProfile);
        $this->assertSame('Bontoc Command Center', $admin->responderProfile->assigned_station);
    }

    public function test_admin_user_is_redirected_to_admin_dashboard_and_sees_admin_ui(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin Responder',
            'email' => 'admin.dashboard@example.com',
            'is_admin' => true,
            'role' => 'responder',
        ]);
        $admin->responderProfile()->create([
            'phone' => '09175550123',
            'assigned_station' => 'Bontoc Command Center',
            'connectivity_mode' => 'auto_select',
            'notification_profile' => 'critical_alerts,push_notifications,sms_backup',
        ]);

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Admin Responder')
            ->assertSee('Administrative Oversight')
            ->assertSee('Admin Control Center')
            ->assertSee('Manage Civilian Accounts')
            ->assertSee('Responder Workload')
            ->assertSee('Civilian Registry')
            ->assertSee('Audit Trail')
            ->assertSee('Open Monitoring')
            ->assertDontSee('Map and feed monitoring')
            ->assertDontSee('Split View');
    }

    public function test_admin_can_open_separate_monitoring_page_distinct_from_admin_board(): void
    {
        $admin = User::factory()->create([
            'name' => 'Separate Admin',
            'email' => 'separate.admin@example.com',
            'is_admin' => true,
            'role' => 'responder',
        ]);

        IncidentReport::query()->create([
            'report_code' => 'INC-ADMIN-MONITOR-1',
            'reported_by' => $admin->id,
            'reporter_name' => 'Civilian Reporter',
            'incident_type' => 'Slope Collapse',
            'severity' => 'Serious',
            'status' => 'received',
            'channel' => 'Internet',
            'transmission_type' => 'online',
            'location_text' => 'Mountain road',
            'description' => 'Monitoring page should still show tactical incident content.',
            'transmitted_at' => now(),
            'status_updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('monitoring'))
            ->assertOk()
            ->assertSee('Responder Command Deck')
            ->assertSee('Live incident monitoring')
            ->assertSee('Split View')
            ->assertSee('Alert Queue')
            ->assertDontSee('Admin Control Center')
            ->assertDontSee('Manage Civilian Accounts');
    }

    public function test_admin_board_renders_ai_quick_read_badges_in_moderation_cards(): void
    {
        $admin = User::factory()->create([
            'role' => 'responder',
            'is_admin' => true,
        ]);

        IncidentReport::query()->create([
            'report_code' => 'INC-ADMIN-AI-0001',
            'reported_by' => $admin->id,
            'reporter_name' => 'Civilian Reporter',
            'incident_type' => 'Moderation Collision',
            'severity' => 'Fatal',
            'status' => 'received',
            'channel' => 'Internet',
            'transmission_type' => 'online',
            'location_text' => 'Bontoc Center',
            'description' => 'Admin moderation cards should surface AI confidence and review state.',
            'ai_summary' => 'AI image analysis predicted Fatal severity from the uploaded photo evidence.',
            'ai_confidence' => 100,
            'ai_source' => 'python_model',
            'ai_status' => 'complete',
            'ai_model_name' => 'bontoc_southern_leyte_production_candidate_external',
            'ai_model_version' => '0.2.0',
            'transmitted_at' => now(),
            'status_updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Confidence 100%')
            ->assertSee('Photo AI model')
            ->assertSee('Ready for triage')
            ->assertSee('AI image analysis predicted Fatal severity');
    }

    public function test_responder_monitoring_page_renders_ai_quick_read_badges(): void
    {
        $responder = User::factory()->create([
            'role' => 'responder',
        ]);

        IncidentReport::query()->create([
            'report_code' => 'INC-MON-AI-0001',
            'reported_by' => $responder->id,
            'reporter_name' => 'Civilian Reporter',
            'incident_type' => 'Road Traffic Collision',
            'severity' => 'Fatal',
            'status' => 'received',
            'channel' => 'Internet',
            'transmission_type' => 'online',
            'location_text' => 'Poblacion, Bontoc, Southern Leyte',
            'description' => 'Monitoring view should show AI quick read without opening the full incident feed.',
            'ai_summary' => 'AI image analysis predicted Fatal severity from the uploaded photo evidence.',
            'ai_confidence' => 100,
            'ai_source' => 'python_model',
            'ai_status' => 'complete',
            'ai_model_name' => 'bontoc_southern_leyte_production_candidate_external',
            'ai_model_version' => '0.2.0',
            'transmitted_at' => now(),
            'status_updated_at' => now(),
        ]);

        $this->actingAs($responder)
            ->get(route('monitoring'))
            ->assertOk()
            ->assertSee('AI quick read')
            ->assertSee('Fatal output')
            ->assertSee('Confidence 100%')
            ->assertSee('Photo AI model')
            ->assertSee('Ready for triage')
            ->assertSee('bontoc_southern_leyte_production_candidate_external v0.2.0');
    }

    public function test_non_admin_user_cannot_open_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
        ]);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_responder_can_open_civilian_account_management_and_update_email_and_password(): void
    {
        $responder = User::factory()->create([
            'role' => 'responder',
        ]);
        $civilian = User::factory()->create([
            'name' => 'Civilian Account',
            'email' => 'civilian.support@example.com',
            'password' => 'OldCivilianPass123!',
            'role' => 'civilian',
        ]);
        $civilian->responderProfile()->create([
            'phone' => '09171239999',
            'assigned_station' => 'Civilian Mobile',
            'connectivity_mode' => 'auto_select',
            'notification_profile' => 'critical_alerts',
        ]);

        $this->actingAs($responder)
            ->get(route('civilian-accounts.index'))
            ->assertOk()
            ->assertSee('Civilian Accounts')
            ->assertSee('Civilian Account')
            ->assertSee('civilian.support@example.com')
            ->assertSee('Save Civilian Access')
            ->assertSee(route('civilian-accounts.update', $civilian), false);

        $this->actingAs($responder)
            ->put(route('civilian-accounts.update', $civilian), [
                'email' => 'civilian.updated@example.com',
                'password' => 'UpdatedCivilianPass123!',
                'password_confirmation' => 'UpdatedCivilianPass123!',
            ])
            ->assertRedirect(route('civilian-accounts.index').'#civilian-account-'.$civilian->id);

        $civilian->refresh();

        $this->assertSame('civilian.updated@example.com', $civilian->email);
        $this->assertTrue(Hash::check('UpdatedCivilianPass123!', $civilian->password));
    }

    public function test_civilian_cannot_see_or_open_responder_only_civilian_account_management(): void
    {
        $civilian = User::factory()->create([
            'role' => 'civilian',
        ]);

        $this->actingAs($civilian)
            ->get(route('reports.create'))
            ->assertOk()
            ->assertDontSee('Civilian Accounts');

        $this->actingAs($civilian)
            ->get(route('civilian-accounts.index'))
            ->assertRedirect(route('dashboard'));
    }
}
