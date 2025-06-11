<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Xendit\Xendit;
use Illuminate\Support\Str;
use App\Models\Payment;

class PaymentController extends Controller
{
    public function create(Request $request)
    {
        $params = [
            'external_id' => (string) Str::uuid(),
            'payer_email' => $request->payer_email,
            'description' => $request->description,
            'amount' => $request->amount,
            'redirect_url' => 'youtube,com'
        ];

        $createInvoice = \Xendit\Invoice::create($params);

        $payment = new Payment;
        $payment->status = 'pending';
        $payment->checkout_link = $createInvoice['invoice_url'];
        $payment->external_id = $params['external_id'];
        $payment->save();

        return response()->json(['data' => $createInvoice['invoice_url']]);
    }
}
