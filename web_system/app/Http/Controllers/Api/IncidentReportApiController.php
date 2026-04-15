<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\AnalyzeIncidentEvidence;
use App\Models\IncidentReport;
use App\Models\User;
use App\Services\AiSeverityClient;
use App\Support\AiSeverityMapper;
use App\Support\IncidentFeedBroadcaster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class IncidentReportApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $reports = IncidentReport::query()
            ->with(['assignedResponder'])
            ->when($user->isCivilian(), fn ($query) => $query->where('reported_by', $user->id))
            ->when($request->filled('severity'), fn ($query) => $query->where('severity', $this->normalizeSeverity((string) $request->string('severity'))))
            ->when($request->filled('status'), fn ($query) => $query->where('status', (string) $request->string('status')))
            ->when($request->string('sort')->toString() === 'oldest', fn ($query) => $query->oldest(), fn ($query) => $query->latest())
            ->get();

        return response()->json([
            'data' => $reports->map(fn (IncidentReport $report): array => $this->reportPayload($report))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'incident_type' => ['required', 'string', 'max:80'],
            'severity' => ['nullable', 'string', 'max:20'],
            'location_text' => ['required', 'string', 'max:180'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'description' => ['required', 'string', 'max:1000'],
            'transmission_type' => ['nullable', 'in:auto,online,lora'],
            'evidence_type' => ['nullable', 'in:none,photo,video'],
            'evidence' => ['nullable', 'file', 'max:20480', 'mimes:jpg,jpeg,png,webp,mp4,mov,avi,3gp'],
            'selfie' => [
                $user->isCivilian() ? 'required' : 'nullable',
                'file',
                'max:10240',
                'mimes:jpg,jpeg,png,webp',
            ],
        ]);

        $fallbackAnalysis = AiSeverityMapper::fallbackFromDescription(
            description: $validated['description'],
            preferredSeverity: $validated['severity'] ?? null,
        );
        $severity = $fallbackAnalysis['severity'];
        $transmissionType = $this->normalizeTransmissionType($validated['transmission_type'] ?? 'auto');
        $channel = $transmissionType === 'lora' ? 'LoRa Mesh' : 'Internet';
        $evidenceType = $validated['evidence_type'] ?? 'none';
        $evidencePath = null;
        $evidenceOriginalName = null;
        $selfiePath = null;
        $selfieOriginalName = null;

        if ($request->hasFile('selfie')) {
            $selfiePath = $request->file('selfie')->store('incident-selfies');
            $selfieOriginalName = $request->file('selfie')->getClientOriginalName();
        }

        if ($transmissionType === 'online' && $request->hasFile('evidence') && $evidenceType !== 'none') {
            $storedFile = $request->file('evidence')->store('incident-evidence');
            $evidencePath = $storedFile;
            $evidenceOriginalName = $request->file('evidence')->getClientOriginalName();
        } else {
            $evidenceType = 'none';
        }

        $shouldAnalyzeImage = $transmissionType === 'online'
            && $evidenceType === 'photo'
            && $evidencePath !== null
            && (bool) config('services.ai_severity.enabled', true);

        $report = IncidentReport::create([
            'report_code' => 'INC-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4)),
            'reported_by' => $user->id,
            'reporter_name' => $user->name,
            'reporter_contact' => $user->responderProfile?->phone,
            'incident_type' => $validated['incident_type'],
            'severity' => $severity,
            'status' => 'received',
            'channel' => $channel,
            'transmission_type' => $transmissionType,
            'location_text' => $validated['location_text'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'description' => $validated['description'],
            'ai_summary' => $fallbackAnalysis['summary'],
            'ai_confidence' => $fallbackAnalysis['confidence'],
            'ai_source' => $fallbackAnalysis['source'],
            'ai_status' => $shouldAnalyzeImage ? 'pending' : $fallbackAnalysis['status'],
            'ai_model_name' => $shouldAnalyzeImage
                ? config('services.ai_severity.model_name', 'bontoc_southern_leyte_severity_baseline')
                : $fallbackAnalysis['model_name'],
            'ai_model_version' => $shouldAnalyzeImage
                ? config('services.ai_severity.model_version', '0.1.0')
                : $fallbackAnalysis['model_version'],
            'ai_review_required' => $fallbackAnalysis['review_required'],
            'ai_probabilities' => $fallbackAnalysis['probabilities'],
            'ai_processed_at' => $shouldAnalyzeImage ? null : now(),
            'ai_error_message' => null,
            'evidence_type' => $evidenceType,
            'evidence_path' => $evidencePath,
            'evidence_original_name' => $evidenceOriginalName,
            'reporter_selfie_path' => $selfiePath,
            'reporter_selfie_original_name' => $selfieOriginalName,
            'reporter_selfie_captured_at' => $selfiePath ? now() : null,
            'transmitted_at' => now(),
            'status_updated_at' => now(),
        ]);

        $report = $this->dispatchAiAnalysisIfNeeded($report);

        IncidentFeedBroadcaster::dispatch($report, 'created');

        return response()->json([
            'message' => 'Incident report submitted successfully.',
            'data' => $this->reportPayload($report->fresh('assignedResponder')),
        ], 201);
    }

    public function show(IncidentReport $incidentReport): JsonResponse
    {
        return response()->json([
            'data' => $this->reportPayload($incidentReport->load('assignedResponder')),
        ]);
    }

    public function evidence(Request $request, IncidentReport $incidentReport): Response
    {
        $this->authorizeReportAccess($request->user(), $incidentReport);

        abort_unless($incidentReport->evidence_path, 404);

        return $this->streamStoredMedia(
            path: $incidentReport->evidence_path,
            filename: $incidentReport->evidence_original_name ?: basename($incidentReport->evidence_path),
            download: $request->boolean('download'),
        );
    }

    public function selfie(Request $request, IncidentReport $incidentReport): Response
    {
        $this->authorizeReportAccess($request->user(), $incidentReport);

        abort_unless($incidentReport->reporter_selfie_path, 404);

        return $this->streamStoredMedia(
            path: $incidentReport->reporter_selfie_path,
            filename: $incidentReport->reporter_selfie_original_name ?: basename($incidentReport->reporter_selfie_path),
            download: $request->boolean('download'),
        );
    }

    private function reportPayload(IncidentReport $report): array
    {
        return [
            'id' => $report->id,
            'report_code' => $report->report_code,
            'reporter_name' => $report->reporter_name,
            'reporter_contact' => $report->reporter_contact,
            'incident_type' => $report->incident_type,
            'severity' => $report->severity,
            'status' => $report->status,
            'channel' => $report->channel,
            'transmission_type' => $report->transmission_type,
            'location_text' => $report->location_text,
            'latitude' => $report->latitude,
            'longitude' => $report->longitude,
            'description' => $report->description,
            'ai_summary' => $report->ai_summary,
            'ai_confidence' => $report->ai_confidence,
            'ai_source' => $report->ai_source,
            'ai_status' => $report->ai_status,
            'ai_model_name' => $report->ai_model_name,
            'ai_model_version' => $report->ai_model_version,
            'ai_review_required' => (bool) $report->ai_review_required,
            'ai_probabilities' => $report->ai_probabilities,
            'ai_processed_at' => optional($report->ai_processed_at)->toIso8601String(),
            'ai_error_message' => $report->ai_error_message,
            'evidence_type' => $report->evidence_type,
            'evidence_original_name' => $report->evidence_original_name,
            'evidence_available' => $report->evidence_path !== null,
            'evidence_url' => $report->evidence_path ? route('api.reports.evidence', $report) : null,
            'selfie_original_name' => $report->reporter_selfie_original_name,
            'selfie_available' => $report->reporter_selfie_path !== null,
            'selfie_url' => $report->reporter_selfie_path ? route('api.reports.selfie', $report) : null,
            'selfie_captured_at' => optional($report->reporter_selfie_captured_at)->toIso8601String(),
            'assigned_responder_name' => $report->assignedResponder?->name,
            'response_notes' => $report->response_notes,
            'transmitted_at' => optional($report->transmitted_at)->toIso8601String(),
            'created_at' => optional($report->created_at)->toIso8601String(),
        ];
    }

    private function normalizeTransmissionType(string $transmissionType): string
    {
        return match (Str::lower(trim($transmissionType))) {
            'lora' => 'lora',
            default => 'online',
        };
    }

    private function normalizeSeverity(string $severity): string
    {
        return AiSeverityMapper::normalizeSeverity($severity);
    }

    private function dispatchAiAnalysisIfNeeded(IncidentReport $report): IncidentReport
    {
        $client = app(AiSeverityClient::class);

        if (! $client->shouldAnalyze($report)) {
            return $report->fresh('assignedResponder');
        }

        if ($client->dispatchMode() === 'queue') {
            AnalyzeIncidentEvidence::dispatch($report->id);

            return $report->fresh('assignedResponder');
        }

        AnalyzeIncidentEvidence::dispatchSync($report->id);

        return $report->fresh('assignedResponder');
    }

    private function authorizeReportAccess(?User $user, IncidentReport $report): void
    {
        abort_unless($user, 401);

        if ($user->isCivilian() && $report->reported_by !== $user->id) {
            abort(404);
        }
    }

    private function streamStoredMedia(string $path, string $filename, bool $download = false): Response
    {
        abort_unless(Storage::exists($path), 404);

        if ($download) {
            return Storage::download($path, $filename);
        }

        return response()->file(Storage::path($path));
    }
}
