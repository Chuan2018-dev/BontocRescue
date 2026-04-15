<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_code',
        'reported_by',
        'assigned_responder_id',
        'reporter_name',
        'reporter_contact',
        'incident_type',
        'severity',
        'status',
        'channel',
        'transmission_type',
        'location_text',
        'latitude',
        'longitude',
        'description',
        'ai_summary',
        'ai_confidence',
        'ai_source',
        'ai_status',
        'ai_model_name',
        'ai_model_version',
        'ai_review_required',
        'ai_probabilities',
        'ai_processed_at',
        'ai_error_message',
        'evidence_type',
        'evidence_path',
        'evidence_original_name',
        'reporter_selfie_path',
        'reporter_selfie_original_name',
        'reporter_selfie_captured_at',
        'coordination_log',
        'response_notes',
        'transmitted_at',
        'resolved_at',
        'status_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:6',
            'longitude' => 'decimal:6',
            'ai_confidence' => 'integer',
            'ai_review_required' => 'boolean',
            'ai_probabilities' => 'array',
            'ai_processed_at' => 'datetime',
            'transmitted_at' => 'datetime',
            'resolved_at' => 'datetime',
            'reporter_selfie_captured_at' => 'datetime',
            'coordination_log' => 'array',
            'status_updated_at' => 'datetime',
        ];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function assignedResponder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_responder_id');
    }
}

