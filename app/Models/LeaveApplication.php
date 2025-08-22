<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * == Properties for Database Columns ==
 * @property int $id
 * @property int $user_id
 * @property string $leave_type
 * @property string $day_type
 * @property Carbon $start_date
 * @property ?Carbon $end_date // <-- THE ONLY CHANGE IS THIS QUESTION MARK
 * @property float $leave_days
 * @property string $reason
 * @property string $status
 * @property ?string $start_half_session
 * @property ?string $end_half_session
 * @property ?int $approved_by
 * @property ?Carbon $approved_at
 * @property ?string $rejection_reason
 * @property ?string $supporting_document_path
 * @property ?float $comp_off_balance
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 *
 * == Properties for Eloquent Relationships ==
 * @property-read User $user
 * @property-read ?User $approvedBy
 *
 * == Properties from Accessors ==
 * @property-read ?string $supporting_document_url
 */
class LeaveApplication extends Model
{
    // ... all the rest of your model code remains the same
    use HasFactory;

    protected $fillable = [
        'user_id',
        'start_date',
        'end_date',
        'reason',
        'leave_type',
        'status',
        'day_type',
        'start_half_session',
        'end_half_session',
        'leave_days',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'supporting_document_path',
        'comp_off_balance',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'leave_days' => 'float',
    ];
    // Set default values for attributes
    protected $attributes = [
        'leave_type' => 'annual',
        'status' => 'pending',
    ];

    // Relationships
    public function user(): BelongsTo // <-- ADDED RETURN TYPE
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo // <-- ADDED RETURN TYPE
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Accessors
    public function getSupportingDocumentUrlAttribute(): ?string // <-- ADDED RETURN TYPE
    {
        return $this->supporting_document_path ? Storage::url($this->supporting_document_path) : null;
    }

    // --- QUERY SCOPES ---

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeCurrentYear(Builder $query): Builder
    {
        return $query->whereYear('start_date', now()->year);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOrderByStatusPriority(Builder $query): Builder
    {
        return $query->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')");
    }

    /**
     * Scope a query to only include leave applications that overlap with a given date range.
     */
    public function scopeOverlapsWith(Builder $query, Carbon $startDate, Carbon $endDate): Builder
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->where('start_date', '<=', $endDate->toDateString())
                ->where('end_date', '>=', $startDate->toDateString());
        });
    }
}
