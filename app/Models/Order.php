<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'order_code',
    'customer_name',
    'customer_whatsapp',
    'customer_email',
    'status',
    'payment_status',
    'pickup_date',
    'pickup_time',
    'delivery_type',
    'delivery_address',
    'delivery_recipient_phone',
    'shipping_cost',
    'subtotal_amount',
    'total_amount',
    'refund_amount',
    'cancellation_reason',
])]
class Order extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pickup_date' => 'date',
            'shipping_cost' => 'decimal:2',
            'subtotal_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'refund_amount' => 'decimal:2',
        ];
    }

    /**
     * Item-item dalam order ini.
     *
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Riwayat perubahan status order ini.
     *
     * @return HasMany<OrderStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    /**
     * Log transaksi pembayaran untuk order ini.
     *
     * @return HasMany<PaymentTransaction, $this>
     */
    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }
}
