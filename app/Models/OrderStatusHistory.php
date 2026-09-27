<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id',
    'changed_by_user_id',
    'from_status',
    'to_status',
    'note',
])]
class OrderStatusHistory extends Model
{
    use HasFactory;

    /**
     * Tabel ini hanya memiliki created_at, tanpa updated_at.
     */
    public const UPDATED_AT = null;

    /**
     * Order yang statusnya berubah.
     *
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Admin/Owner yang mengubah status (NULL jika perubahan oleh sistem).
     *
     * @return BelongsTo<User, $this>
     */
    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
