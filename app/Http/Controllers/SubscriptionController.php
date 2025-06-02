<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Company;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    // Aktifkan subscription selama 1 bulan untuk perusahaan admin.
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

    /**
     * Cek status subscription
     */
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
}
