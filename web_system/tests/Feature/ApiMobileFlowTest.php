<?php

namespace Tests\Feature;

use App\Models\IncidentReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApiMobileFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_client_can_register_authenticate_submit_report_and_update_settings(): void
    {
        $registerResponse = $this->postJson('/api/v1/auth/register', [
            'name' => 'Field Operator',
            'phone' => '09170001111',
            'email' => 'operator@example.com',
            'password' => 'password12345',
            'password_confirmation' => 'password12345',
            'role' => 'responder',
        ]);

        $registerResponse->assertCreated();
        $token = $registerResponse->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.email', 'operator@example.com')
            ->assertJsonPath('user.is_admin', false);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/summary')
            ->assertOk()
            ->assertJsonStructure([
                'application',
                'user' => ['id', 'name', 'email', 'role', 'is_admin'],
                'summary' => ['active_alerts', 'my_reports', 'transmitted_today', 'assigned_to_me'],
                'recent_reports',
                'map_points',
                'notifications',
            ]);

        $reportResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/reports', [
                'incident_type' => 'Landslide',
                'severity' => 'High',
                'location_text' => 'Mountain Road Section 4',
                'latitude' => 17.1021,
                'longitude' => 120.9855,
                'description' => 'Road blocked by landslide and one vehicle is trapped.',
                'transmission_type' => 'online',
                'evidence_type' => 'none',
            ]);

        $reportResponse->assertCreated()
            ->assertJsonPath('data.location_text', 'Mountain Road Section 4')
            ->assertJsonPath('data.status', 'received')
            ->assertJsonPath('data.severity', 'Serious')
            ->assertJsonPath('data.transmission_type', 'online');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/reports')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/settings', [
                'critical_alerts' => true,
                'push_notifications' => true,
                'sms_backup' => false,
                'connectivity_mode' => 'lora_fallback',
            ])
            ->assertOk()
            ->assertJsonPath('data.connectivity_mode', 'lora_fallback');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }

    public function test_mobile_client_can_upload_photo_evidence_with_online_report(): void
    {
        Storage::fake();
        config()->set('services.ai_severity.enabled', false);

        $registerResponse = $this->postJson('/api/v1/auth/register', [
            'name' => 'Civilian Reporter',
            'phone' => '09179991111',
            'email' => 'civilian.evidence@example.com',
            'password' => 'CivilianPass123!',
            'password_confirmation' => 'CivilianPass123!',
            'role' => 'civilian',
        ]);

        $registerResponse->assertCreated();
        $token = $registerResponse->json('token');
        $evidence = UploadedFile::fake()->image('crash-scene.jpg');
        $selfie = UploadedFile::fake()->image('verification-selfie.jpg');

        $reportResponse = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->post('/api/v1/reports', [
            'incident_type' => 'Vehicular Collision',
            'location_text' => 'National Road Junction',
            'description' => 'Two vehicles collided and responders need the attached evidence for scene review.',
            'transmission_type' => 'online',
            'evidence_type' => 'photo',
            'evidence' => $evidence,
            'selfie' => $selfie,
        ]);

        $reportResponse->assertCreated()
            ->assertJsonPath('data.evidence_available', true)
            ->assertJsonPath('data.evidence_type', 'photo')
            ->assertJsonPath('data.evidence_original_name', 'crash-scene.jpg')
            ->assertJsonPath('data.evidence_url', url('/api/v1/reports/'.$reportResponse->json('data.id').'/evidence'))
            ->assertJsonPath('data.selfie_available', true)
            ->assertJsonPath('data.selfie_original_name', 'verification-selfie.jpg')
            ->assertJsonPath('data.selfie_url', url('/api/v1/reports/'.$reportResponse->json('data.id').'/selfie'));

        $report = IncidentReport::query()->latest('id')->firstOrFail();

        $this->assertSame('photo', $report->evidence_type);
        $this->assertNotNull($report->evidence_path);
        $this->assertNotNull($report->reporter_selfie_path);
        Storage::assertExists($report->evidence_path);
        Storage::assertExists($report->reporter_selfie_path);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->get('/api/v1/reports/'.$report->id.'/evidence')->assertOk();

        $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->get('/api/v1/reports/'.$report->id.'/selfie')->assertOk();
    }

    public function test_mobile_client_rejects_civilian_report_without_verification_selfie(): void
    {
        Storage::fake();

        $registerResponse = $this->postJson('/api/v1/auth/register', [
            'name' => 'Civilian Reporter',
            'phone' => '09179992222',
            'email' => 'civilian.selfie@example.com',
            'password' => 'CivilianPass123!',
            'password_confirmation' => 'CivilianPass123!',
            'role' => 'civilian',
        ]);

        $registerResponse->assertCreated();
        $token = $registerResponse->json('token');

        $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->post('/api/v1/reports', [
            'incident_type' => 'Vehicular Collision',
            'location_text' => 'National Road Junction',
            'description' => 'Attempted emergency submission without the required live verification selfie.',
            'transmission_type' => 'online',
            'evidence_type' => 'none',
        ])->assertInvalid(['selfie']);
    }

    public function test_mobile_client_uses_ai_service_for_online_photo_reports(): void
    {
        Storage::fake();
        Http::fake([
            'http://127.0.0.1:8100/predict' => Http::response([
                'filename' => 'crash-scene.jpg',
                'severity' => 'fatal',
                'confidence' => 0.91,
                'probabilities' => [
                    'minor' => 0.02,
                    'serious' => 0.07,
                    'fatal' => 0.91,
                ],
                'responder_review_required' => false,
                'responder_review_action' => 'needs_responder_review',
            ], 200),
        ]);

        config()->set('services.ai_severity.enabled', true);
        config()->set('services.ai_severity.dispatch', 'sync');
        config()->set('services.ai_severity.url', 'http://127.0.0.1:8100');
        config()->set('services.ai_severity.model_name', 'bontoc_southern_leyte_severity_baseline');
        config()->set('services.ai_severity.model_version', '0.1.0');

        $registerResponse = $this->postJson('/api/v1/auth/register', [
            'name' => 'Civilian Reporter',
            'phone' => '09179993333',
            'email' => 'civilian.ai.photo@example.com',
            'password' => 'CivilianPass123!',
            'password_confirmation' => 'CivilianPass123!',
            'role' => 'civilian',
        ]);

        $token = $registerResponse->json('token');

        $reportResponse = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->post('/api/v1/reports', [
            'incident_type' => 'Vehicular Collision',
            'location_text' => 'National Road Junction',
            'description' => 'Vehicle collision with visible severe injuries in the uploaded photo.',
            'transmission_type' => 'online',
            'evidence_type' => 'photo',
            'evidence' => UploadedFile::fake()->image('crash-scene.jpg'),
            'selfie' => UploadedFile::fake()->image('verification-selfie.jpg'),
        ]);

        $reportResponse->assertCreated()
            ->assertJsonPath('data.severity', 'Fatal')
            ->assertJsonPath('data.ai_source', 'python_model')
            ->assertJsonPath('data.ai_status', 'complete')
            ->assertJsonPath('data.ai_confidence', 91)
            ->assertJsonPath('data.ai_model_name', 'bontoc_southern_leyte_severity_baseline')
            ->assertJsonPath('data.ai_review_required', false);

        $report = IncidentReport::query()->latest('id')->firstOrFail();

        $this->assertSame('Fatal', $report->severity);
        $this->assertSame('python_model', $report->ai_source);
        $this->assertSame('complete', $report->ai_status);
        $this->assertSame(91, $report->ai_confidence);
        $this->assertSame('bontoc_southern_leyte_severity_baseline', $report->ai_model_name);
        $this->assertSame('0.1.0', $report->ai_model_version);
        $this->assertFalse((bool) $report->ai_review_required);
        $this->assertNotNull($report->ai_processed_at);

        Http::assertSentCount(1);
    }

    public function test_mobile_client_falls_back_when_ai_service_fails_for_online_photo_reports(): void
    {
        Storage::fake();
        Http::fake([
            'http://127.0.0.1:8100/predict' => Http::response(['message' => 'Service unavailable'], 503),
        ]);

        config()->set('services.ai_severity.enabled', true);
        config()->set('services.ai_severity.dispatch', 'sync');
        config()->set('services.ai_severity.url', 'http://127.0.0.1:8100');

        $registerResponse = $this->postJson('/api/v1/auth/register', [
            'name' => 'Fallback Civilian',
            'phone' => '09179994444',
            'email' => 'civilian.ai.fallback@example.com',
            'password' => 'CivilianPass123!',
            'password_confirmation' => 'CivilianPass123!',
            'role' => 'civilian',
        ]);

        $token = $registerResponse->json('token');

        $reportResponse = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->post('/api/v1/reports', [
            'incident_type' => 'Vehicular Collision',
            'location_text' => 'National Road Junction',
            'description' => 'Vehicle crash with bleeding and multiple injuries.',
            'transmission_type' => 'online',
            'evidence_type' => 'photo',
            'evidence' => UploadedFile::fake()->image('fallback-scene.jpg'),
            'selfie' => UploadedFile::fake()->image('verification-selfie.jpg'),
        ]);

        $reportResponse->assertCreated()
            ->assertJsonPath('data.severity', 'Serious')
            ->assertJsonPath('data.ai_source', 'description_fallback')
            ->assertJsonPath('data.ai_status', 'fallback')
            ->assertJsonPath('data.ai_review_required', false);

        $report = IncidentReport::query()->latest('id')->firstOrFail();

        $this->assertSame('Serious', $report->severity);
        $this->assertSame('description_fallback', $report->ai_source);
        $this->assertSame('fallback', $report->ai_status);
        $this->assertNotNull($report->ai_error_message);
        $this->assertStringContainsString(
            'description-based severity fallback',
            $report->ai_error_message
        );
        $this->assertNotNull($report->ai_processed_at);
        $this->assertStringContainsString('AI triage marked this report as Serious', $report->ai_summary);
    }
}
