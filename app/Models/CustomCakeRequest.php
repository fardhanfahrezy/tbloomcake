<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'customer_name',
    'customer_whatsapp',
    'pickup_date',
    'size_label',
    'flavor',
    'filling',
    'color',
    'cake_text',
    'wants_fondant',
    'fondant_detail',
    'wants_print',
    'additional_notes',
    'reference_image_path',
    'status',
    'notified_at',
])]
class CustomCakeRequest extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pickup_date' => 'date',
            'wants_fondant' => 'boolean',
            'wants_print' => 'boolean',
            'notified_at' => 'datetime',
        ];
    }
}
