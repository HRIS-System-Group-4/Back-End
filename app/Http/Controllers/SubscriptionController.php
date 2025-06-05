<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\SubscriptionPricing;
use App\Models\SubscriptionInvoice;
use App\Models\Company;
use Carbon\Carbon;
use Xendit\Xendit;

class SubscriptionController extends Controller
{
    public function activate(Request $request)
    {
        $admin = Auth::user()->admin;

        if (!$admin || !$admin->company_id) {
            return response()->json(['message' => 'Admin belum memiliki perusahaan.'], 400);
        }

        $company = Company::findOrFail($admin->company_id);

        $company->subscription_active = true;
        $company->subscription_expires_at = Carbon::now()->addMonth();
        $company->save();

        return response()->json([
            'message' => 'Subscription diaktifkan selama 1 bulan.',
            'expires_at' => $company->subscription_expires_at
        ]);
    }

    public function status(Request $request)
    {
        $admin = Auth::user()->admin;

        if (!$admin || !$admin->company_id) {
            return response()->json(['message' => 'Admin belum memiliki perusahaan.'], 400);
        }

        $company = Company::findOrFail($admin->company_id);

        return response()->json([
            'subscription_active' => $company->subscription_active,
            'expires_at' => $company->subscription_expires_at,
        ]);
    }

    public function createInvoice(Request $request)
    {
        $request->validate([
            'company_id'  => 'required|exists:company,id',
            'pricing_id'  => 'required|exists:subscription_pricings,id',
            'payer_email' => 'required|email',
        ]);

        $company = Company::findOrFail($request->company_id);
        $pricing = SubscriptionPricing::findOrFail($request->pricing_id);
        $externalId = 'invoice-' . Str::uuid();

        try {
            $invoice = \Xendit\Invoice::create([
                'external_id' => $externalId,
                'payer_email' => $request->payer_email,
                'description' => $pricing->description,
                'amount' => $pricing->price,
                'invoice_duration' => 3600,
                'success_redirect_url' => url('/subscription/success'),
                'failure_redirect_url' => url('/subscription/failed'),
            ]);

            $subscriptionInvoice = SubscriptionInvoice::create([
                'company_id'        => $company->id,
                'pricing_id'        => $pricing->id,
                'xendit_invoice_id' => $invoice['id'],
                'status'            => $invoice['status'],
                'amount'            => $invoice['amount'],
                'invoice_url'       => $invoice['invoice_url'],
                'expires_at'        => now()->addSeconds($invoice['expiry_date'] ?? 3600),
            ]);

            return response()->json([
                'message' => 'Invoice berhasil dibuat',
                'invoice_url' => $invoice['invoice_url'],
                'invoice' => $subscriptionInvoice,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal membuat invoice',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function callback(Request $request)
    {
        $payload = $request->all();

        $invoice = SubscriptionInvoice::where('xendit_invoice_id', $payload['id'] ?? null)->first();

        if (!$invoice) {
            return response()->json(['message' => 'Invoice tidak ditemukan'], 404);
        }

        $invoice->update([
            'status' => $payload['status'] ?? $invoice->status,
        ]);

        return response()->json(['message' => 'Status diperbarui'], 200);
    }
}
