<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CouponsController extends Controller
{
    public function validateCode(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
            'subtotal' => ['required', 'numeric', 'min:0'],
        ]);
        $now = now();
        $coupon = Coupon::where('code', $validated['code'])
            ->where('active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->first();

        if (!$coupon) {
            return response()->json(['valid' => false, 'reason' => 'invalid_or_expired'], 404);
        }
        if ($coupon->max_uses && $coupon->uses_count >= $coupon->max_uses) {
            return response()->json(['valid' => false, 'reason' => 'exhausted'], 400);
        }
        if ($validated['subtotal'] < (float)$coupon->min_subtotal) {
            return response()->json(['valid' => false, 'reason' => 'min_subtotal'], 400);
        }

        $discount = $coupon->type === 'percent'
            ? round($validated['subtotal'] * ((float)$coupon->amount) / 100, 2)
            : (float)$coupon->amount;

        return response()->json([
            'valid' => true,
            'code' => $coupon->code,
            'type' => $coupon->type,
            'amount' => (float)$coupon->amount,
            'discount' => min($discount, $validated['subtotal']),
        ]);
    }
}


