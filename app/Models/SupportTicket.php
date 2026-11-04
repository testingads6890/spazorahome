<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    protected $fillable = [
        'user_id', 'assigned_to', 'ticket_number', 'subject', 'description', 'category',
        'status', 'priority', 'resolution', 'resolved_at', 'closed_at', 'internal_remarks', 'follow_up_date',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'follow_up_date' => 'date',
        'internal_remarks' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class, 'ticket_id');
    }

    public function callRecords(): HasMany
    {
        return $this->hasMany(TicketCallRecord::class, 'ticket_id');
    }
}
