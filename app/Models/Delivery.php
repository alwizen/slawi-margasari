<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delivery extends Model
{
    //

    // add fillable
    protected $fillable = [
        'delivery_number',
        'delivery_date',
        'school_id',
        'status',
        'qty_delivery',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function isDelivered(): bool
    {
        return $this->status === 'terkirim';
    }
    public function isInTransit(): bool
    {
        return $this->status === 'dalam_perjalanan';
    }
    public function markAsDelivered(): void
    {
        $this->update(['status' => 'terkirim']);
        $this->save();
    }

    // add guaded
    // protected $guarded = ['id'];
    // add hidden
    protected $hidden = ['created_at', 'updated_at'];
}
