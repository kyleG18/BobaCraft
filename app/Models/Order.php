<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'Pending';
    public const STATUS_PREPARING = 'Preparing';
    public const STATUS_READY = 'Ready';
    public const STATUS_COMPLETED = 'Completed';

    public const VALID_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PREPARING,
        self::STATUS_READY,
        self::STATUS_COMPLETED,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_name',
        'status',
    ];

    /**
     * Get the badge CSS styling class based on order status.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'badge-pending',
            self::STATUS_PREPARING => 'badge-preparing',
            self::STATUS_READY => 'badge-ready',
            self::STATUS_COMPLETED => 'badge-completed',
            default => 'badge-neutral',
        };
    }
}
