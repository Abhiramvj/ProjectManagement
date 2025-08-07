<?php

namespace App\Models;

use Carbon\Carbon; // <-- ADDED: Import Carbon for date handling
use Illuminate\Database\Eloquent\Builder; // <-- ADDED: Import Builder for scopes
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class LeaveApplication extends Model
{
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
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Accessors
    public function getSupportingDocumentUrlAttribute()
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
     *
     * This allows for clean queries like ->overlapsWith($startDate, $endDate).
     *
     * @param  \Carbon\Carbon  $startDate  The start of the period to check against.
     * @param  \Carbon\Carbon  $endDate  The end of the period to check against.
     */
    public function scopeOverlapsWith(Builder $query, Carbon $startDate, Carbon $endDate): Builder
    {
        // The standard logic for checking if two date ranges overlap is:
        // Range A overlaps with Range B if A.start_date <= B.end_date AND A.end_date >= B.start_date.
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->where('start_date', '<=', $endDate->toDateString())
                ->where('end_date', '>=', $startDate->toDateString());
        });
    }
}
