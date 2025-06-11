<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\SubscriptionPricing;
use App\Models\SubscriptionInvoice;
use App\Models\Subscription;
use App\Models\Admin;
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

        // Validasi input plan
        $request->validate([
            'plan' => 'required|string'
        ]);

        $company = Company::findOrFail($admin->company_id);

        // Ambil data pricing berdasarkan nama plan
        $pricing = SubscriptionPricing::where('id', $request->plan)->first();
        $pricing = SubscriptionPricing::where('name', $request->plan)->first();
        if (!$pricing) {
            return response()->json(['message' => 'Paket subscription tidak ditemukan.'], 404);
        }

        // Nonaktifkan semua subscription sebelumnya agar tidak ganda
        Subscription::where('company_id', $company->id)->update(['is_active' => false]);

        // Buat subscription baru - FIXED: Added missing company_id
        $subscription = Subscription::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'company_id' => $company->id,  // ← This was missing!
            'admin_id' => $admin->id,
            'subscription_pricing_id' => $pricing->id,
            'start_date' => now(),
            'end_date' => now()->addDays($pricing->duration_in_days),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // Simpan ke tabel company
        $company->subscription_active = true;
        $company->subscription_expires_at = $subscription->end_date;
        $company->save();

        return response()->json([
            'message' => 'Subscription berhasil diaktifkan.',
            'data' => $subscription,
        ]);
    }

    // public function status(Request $request)
    // {
    //     $admin = Auth::user()->admin;

    //     if (!$admin || !$admin->company_id) {
    //         return response()->json(['message' => 'Admin belum memiliki perusahaan.'], 400);
    //     }

    //     $company = Company::findOrFail($admin->company_id);

    //     return response()->json([
    //         'subscription_active' => $company->subscription_active,
    //         'expires_at' => $company->subscription_expires_at,
    //     ]);
    // }
    public function status(Request $request)
    {
        $admin = Auth::user()->admin;

        $subscription = Subscription::where('company_id', $admin->company_id)
            ->where('is_active', true)
            ->orderByDesc('end_date')
            ->first();

        return response()->json([
            'subscription_active' => $subscription !== null,
            'expires_at' => optional($subscription)->end_date,
        ]);
    }

    public function __construct()
    {
        Xendit::setApiKey(env('XENDIT_SECRET_API_KEY')); // ← ini benar
    }

    public function createInvoice(Request $request)
    {
        // Set API Key secara langsung — sesuai kode yang berhasil kamu buat sebelumnya
        Xendit::setApiKey("xnd_development_BlVqJXRLe3bKwcjpBVrczC90VCo4g78apHnSIFYyTOYPu7YDGp9YxiVEfIL3cnj0");

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
                'description' => $pricing->description ?? 'Subscription Payment',
                'amount' => $pricing->price,
                'invoice_duration' => 3600,
                'redirect_url' => url('/subscription/success'), // atau bisa juga 'https://google.com' untuk testing
            ]);

            $subscriptionInvoice = SubscriptionInvoice::create([
                'company_id'        => $company->id,
                'pricing_id'        => $pricing->id,
                'xendit_invoice_id' => $invoice['id'],
                'status'            => $invoice['status'],
                'amount'            => $invoice['amount'],
                'invoice_url'       => $invoice['invoice_url'],
                'expires_at'        => now()->addSeconds(3600), // kamu bisa gunakan expiry_date jika ada
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

        // Update status invoice
        $invoice->update([
            'status' => $payload['status'] ?? $invoice->status,
        ]);

        // Jika invoice sudah dibayar, buat subscription baru
        if (($payload['status'] ?? null) === 'PAID') {
            $company = Company::find($invoice->company_id);

            if ($company && $company->admin) {
                $admin = $company->admin;

                // Cek apakah admin sudah punya subscription aktif
                $existing = Subscription::where('admin_id', $admin->id)
                    ->where('is_active', true)
                    ->first();

                // Deaktivasi yang lama jika ada
                if ($existing) {
                    $existing->is_active = false;
                    $existing->save();
                }

                // Buat subscription baru
                Subscription::create([
                    'id' => Str::uuid(),
                    'admin_id' => $admin->id,
                    'company_id' => $company->id,
                    'subscription_pricing_id' => $invoice->pricing_id,
                    'start_date' => now()->toDateString(),
                    'end_date' => now()->addMonth()->toDateString(),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return response()->json(['message' => 'Status diperbarui'], 200);
    }

    // public function billing()
    // {
    //     $admin = Auth::user()->admin;

    //     if (!$admin || !$admin->company_id) {
    //         return response()->json(['message' => 'Admin belum memiliki perusahaan.'], 400);
    //     }

    //     $invoices = SubscriptionInvoice::where('company_id', $admin->company_id)->latest()->get();

    //     return response()->json([
    //         'message' => 'Data tagihan berhasil diambil.',
    //         'data' => $invoices
    //     ]);
    // }
    // public function billing()
    // {
    //     $admin = Auth::user()->admin;

    //     if (!$admin || !$admin->company_id) {
    //         return response()->json(['message' => 'Admin belum memiliki perusahaan.'], 400);
    //     }

    //     // Load relasi 'pricing'
    //     // $invoices = SubscriptionInvoice::with('pricing')
    //     //     ->where('company_id', $admin->company_id)
    //     //     ->latest()
    //     //     ->get()
    //     //     ->map(function ($invoice) {
    //     //         return [
    //     //             'id' => $invoice->id,
    //     //             'invoiceDate' => $invoice->created_at->toDateString(),
    //     //             'plan' => $invoice->pricing?->name ?? '-', // Relasi akan berhasil diakses
    //     //             'amount' => $invoice->amount,
    //     //             'status' => $invoice->status,
    //     //         ];
    //     //     });

    //     // return response()->json([
    //     //     'message' => 'Data tagihan berhasil diambil.',
    //     //     'data' => $invoices
    //     // ]);
    //     $invoices = SubscriptionInvoice::with('pricing')
    //     ->where('company_id', $admin->company_id)
    //     ->latest()
    //     ->get()
    //     ->map(function ($invoice) {
    //         return [
    //             'id' => $invoice->id,
    //             'invoiceDate' => optional($invoice->created_at)->toDateString(),
    //             'plan' => optional($invoice->pricing)->name ?? '-',
    //             'amount' => $invoice->amount,
    //             'status' => $invoice->status,
    //         ];
    //     });

    // return response()->json([
    //     'message' => 'Data tagihan berhasil diambil.',
    //     'data' => $invoices,
    // ]);
    // }

    // public function invoices()
    // {
    //     return $this->hasMany(SubscriptionInvoice::class, 'pricing_id');
    // }

    public function billing()
    {
        $admin = Auth::user()->admin;

        if (!$admin || !$admin->company_id) {
            return response()->json(['message' => 'Admin belum memiliki perusahaan.'], 400);
        }

        $invoices = SubscriptionInvoice::with('pricing')
            ->where('company_id', $admin->company_id)
            ->latest()
            ->get();

        // Tambahkan ini untuk mengecek apakah relasi pricing berhasil dimuat
        Log::info($invoices->pluck('pricing'));

        $invoices = $invoices->map(function ($invoice) {
            return [
                'id' => $invoice->id,
                'invoiceDate' => $invoice->created_at ? $invoice->created_at->toDateString() : '-',
                'plan' => $invoice->pricing ? $invoice->pricing->name : '-',
                'amount' => $invoice->amount,
                'status' => $invoice->status,
            ];
        });

        return response()->json([
            'message' => 'Data tagihan berhasil diambil.',
            'data' => $invoices,
        ]);
    }


    // public function __construct()
    // {
    //     Xendit::setApiKey(env('XENDIT_SECRET_API_KEY')); // ← ini benar
    // }

    // public function createInvoice(Request $request)
    // {
    //     // Set API Key secara langsung — sesuai kode yang berhasil kamu buat sebelumnya
    //     Xendit::setApiKey("xnd_development_BlVqJXRLe3bKwcjpBVrczC90VCo4g78apHnSIFYyTOYPu7YDGp9YxiVEfIL3cnj0");

    //     $request->validate([
    //         'company_id'  => 'required|exists:company,id',
    //         'pricing_id'  => 'required|exists:subscription_pricings,id',
    //         'payer_email' => 'required|email',
    //     ]);

    //     $company = Company::findOrFail($request->company_id);
    //     $pricing = SubscriptionPricing::findOrFail($request->pricing_id);
    //     $externalId = 'invoice-' . Str::uuid();

    //     try {
    //         $invoice = \Xendit\Invoice::create([
    //             'external_id' => $externalId,
    //             'payer_email' => $request->payer_email,
    //             'description' => $pricing->description ?? 'Subscription Payment',
    //             'amount' => $pricing->price,
    //             'invoice_duration' => 3600,
    //             'redirect_url' => url('/subscription/success'), // atau bisa juga 'https://google.com' untuk testing
    //         ]);

    //         $subscriptionInvoice = SubscriptionInvoice::create([
    //             'company_id'        => $company->id,
    //             'pricing_id'        => $pricing->id,
    //             'xendit_invoice_id' => $invoice['id'],
    //             'status'            => $invoice['status'],
    //             'amount'            => $invoice['amount'],
    //             'invoice_url'       => $invoice['invoice_url'],
    //             'expires_at'        => now()->addSeconds(3600), // kamu bisa gunakan expiry_date jika ada
    //         ]);

    //         return response()->json([
    //             'message' => 'Invoice berhasil dibuat',
    //             'invoice_url' => $invoice['invoice_url'],
    //             'invoice' => $subscriptionInvoice,
    //         ], 201);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'Gagal membuat invoice',
    //             'error'   => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    // public function callback(Request $request)
    // {
    //     $payload = $request->all();

    //     $invoice = SubscriptionInvoice::where('xendit_invoice_id', $payload['id'] ?? null)->first();

    //     if (!$invoice) {
    //         return response()->json(['message' => 'Invoice tidak ditemukan'], 404);
    //     }

    //     // Update status invoice
    //     $invoice->update([
    //         'status' => $payload['status'] ?? $invoice->status,
    //     ]);

    //     // Jika invoice sudah dibayar, buat subscription baru
    //     if (($payload['status'] ?? null) === 'PAID') {
    //         $company = Company::find($invoice->company_id);

    //         if ($company && $company->admin) {
    //             $admin = $company->admin;

    //             // Cek apakah admin sudah punya subscription aktif
    //             $existing = Subscription::where('admin_id', $admin->id)
    //                 ->where('is_active', true)
    //                 ->first();

    //             // Deaktivasi yang lama jika ada
    //             if ($existing) {
    //                 $existing->is_active = false;
    //                 $existing->save();
    //             }

    //             // Buat subscription baru
    //             Subscription::create([
    //                 'id' => Str::uuid(),
    //                 'admin_id' => $admin->id,
    //                 'company_id' => $company->id,
    //                 'subscription_pricing_id' => $invoice->pricing_id,
    //                 'start_date' => now()->toDateString(),
    //                 'end_date' => now()->addMonth()->toDateString(),
    //                 'is_active' => true,
    //                 'created_at' => now(),
    //                 'updated_at' => now(),
    //             ]);
    //         }
    //     }

    //     return response()->json(['message' => 'Status diperbarui'], 200);
    // }
}
