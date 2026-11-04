<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketCallRecord extends Model
{
    protected $fillable = [
        'ticket_id', 'user_id', 'support_executive_id', 'customer_name', 'customer_mobile',
        'call_datetime', 'call_purpose', 'call_status', 'notes', 'resolution',
        'follow_up_required', 'follow_up_date', 'recording_url',
    ];

    protected $casts = [
        'call_datetime' => 'datetime',
        'follow_up_date' => 'date',
        'follow_up_required' => 'boolean',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function supportExecutive(): BelongsTo
    {
        return $this->belongsTo(User::class, 'support_executive_id');
    }
}
