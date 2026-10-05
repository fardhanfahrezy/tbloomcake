<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Midtrans\Config;
use Midtrans\Snap;

class PaymentController extends Controller
{
    public function __construct()
    {
        \Midtrans\Config::$serverKey = config('midtrans.server_key');
        \Midtrans\Config::$isProduction = config('midtrans.is_production');
        \Midtrans\Config::$isSanitized = config('midtrans.is_sanitized');
        \Midtrans\Config::$is3ds = config('midtrans.is_3ds');
    }

    public function createTransaction()
    {
        $params = [
            'transaction_details' => [
                'order_id'     => 'ORDER-' . time() . '-' . rand(100, 999),
                'gross_amount' => 50000, // Jumlah tagihan (Rupiah)
            ],
            'customer_details' => [
                'first_name' => 'John',
                'last_name'  => 'Doe',
                'email'      => 'john@example.com',
                'phone'      => '081234567890',
            ],
        ];

        // Dapatkan Snap Token dari Midtrans
        $snapToken = Snap::getSnapToken($params);

        return view('payment', compact('snapToken'));
    }
}
