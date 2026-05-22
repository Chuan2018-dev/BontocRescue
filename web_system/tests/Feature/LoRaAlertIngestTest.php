<?php

namespace Tests\Feature;

use App\Events\IncidentFeedUpdated;
use App\Models\IncidentReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class LoRaAlertIngestTest extends TestCase
{
    use RefreshDatabase;

    public function test_lora_hardware_alert_creates_responder_visible_report(): void
    {
        Event::fake([IncidentFeedUpdated::class]);
        config(['services.lora_ingest.token' => 'test-lora-token']);

        $this->postJson('/api/v1/lora/alerts', $this->validPayload(), [
            'X-STITCH-LORA-TOKEN' => 'test-lora-token',
        ])
            ->assertCreated()
            ->assertJsonPath('duplicate', false)
            ->assertJsonPath('data.transmission_type', 'lora')
            ->assertJsonPath('data.lora_sender_id', 'S01')
            ->assertJsonPath('data.lora_sequence', '42');

        $report = IncidentReport::query()->firstOrFail();

        $this->assertSame('LoRa Sender S01', $report->reporter_name);
        $this->assertSame('LoRa Emergency Alert', $report->incident_type);
        $this->assertSame('Serious', $report->severity);
        $this->assertSame('received', $report->status);
        $this->assertSame('LoRa Mesh', $report->channel);
        $this->assertSame('lora', $report->transmission_type);
        $this->assertSame('arduino_lora_receiver', $report->hardware_source);
        $this->assertSame('G1', $report->lora_gateway_id);
        $this->assertSame(-70, $report->lora_gateway_rssi);
        $this->assertSame(-65, $report->lora_receiver_rssi);
        $this->assertSame(7, $report->lora_satellites);
        $this->assertStringContainsString('GPS 10.307156, 124.980718', $report->location_text);

        Event::assertDispatched(IncidentFeedUpdated::class);
    }

    public function test_lora_hardware_alert_rejects_invalid_token(): void
    {
        config(['services.lora_ingest.token' => 'test-lora-token']);

        $this->postJson('/api/v1/lora/alerts', $this->validPayload(), [
            'X-STITCH-LORA-TOKEN' => 'wrong-token',
        ])->assertUnauthorized();

        $this->assertDatabaseCount('incident_reports', 0);
    }

    public function test_lora_hardware_alert_is_idempotent_by_sender_and_sequence(): void
    {
        config(['services.lora_ingest.token' => 'test-lora-token']);

        $this->postJson('/api/v1/lora/alerts', $this->validPayload(), [
            'X-STITCH-LORA-TOKEN' => 'test-lora-token',
        ])->assertCreated();

        $this->postJson('/api/v1/lora/alerts', $this->validPayload(), [
            'X-STITCH-LORA-TOKEN' => 'test-lora-token',
        ])
            ->assertOk()
            ->assertJsonPath('duplicate', true)
            ->assertJsonPath('data.lora_sender_id', 'S01')
            ->assertJsonPath('data.lora_sequence', '42');

        $this->assertDatabaseCount('incident_reports', 1);
    }

    private function validPayload(): array
    {
        return [
            'sender_id' => 'S01',
            'sequence' => '42',
            'gateway_id' => 'G1',
            'latitude' => 10.307156,
            'longitude' => 124.980718,
            'satellites' => 7,
            'gateway_rssi' => -70,
            'gateway_snr' => 9.5,
            'receiver_rssi' => -65,
            'receiver_snr' => 8.1,
            'severity' => 'Serious',
            'incident_type' => 'LoRa Emergency Alert',
        ];
    }
}
