<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'is_admin', 'role'])]
#[Hidden(['password', 'remember_token', 'api_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_admin' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function responderProfile(): HasOne
    {
        return $this->hasOne(ResponderProfile::class);
    }

    public function submittedReports(): HasMany
    {
        return $this->hasMany(IncidentReport::class, 'reported_by');
    }

    public function assignedReports(): HasMany
    {
        return $this->hasMany(IncidentReport::class, 'assigned_responder_id');
    }

    public function isResponder(): bool
    {
        return $this->role === 'responder';
    }

    public function isCivilian(): bool
    {
        return $this->role === 'civilian';
    }
}