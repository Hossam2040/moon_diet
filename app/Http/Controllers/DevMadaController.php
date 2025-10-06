<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DevMadaController extends Controller
{
    public function create(Request $request)
    {
        // Accepts the same payload our MadaService sends
        $id = 'dev_mada_' . uniqid();
        $redirectUrl = url('/api/dev/mada/redirect?intent_id=' . $id);

        return response()->json([
            'id' => $id,
            'redirect_url' => $redirectUrl,
        ]);
    }

    public function redirect(Request $request)
    {
        // Simulate PSP hosted page completing and asking developer to trigger callback manually
        $intentId = (string) $request->query('intent_id');
        return response()->json([
            'message' => 'Dev Mada redirect reached. Now POST a callback to /api/payments/mada/callback',
            'example_callback_payload' => [
                'id' => $intentId,
                'status' => 'succeeded',
                'amount' => '100.00',
                'currency' => 'SAR',
            ],
            'note' => 'Sign the payload with HMAC-SHA256 over sorted http_build_query(data) using MADA_SECRET_KEY in X-Signature header.',
            'auto_callback_url' => url('/api/dev/mada/auto-callback?intent_id=' . $intentId . '&status=succeeded'),
        ]);
    }

    public function sign(Request $request)
    {
        // Dev helper: compute the canonical string and signature exactly as server does
        $data = $request->all();
        $secret = (string) config('services.mada.secret_key');

        // Only consider limited keys we verify in local/testing
        $allowed = ['amount', 'currency', 'id', 'status'];
        $filtered = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $data)) {
                $filtered[$k] = (string) $data[$k];
            }
        }

        ksort($filtered);
        $serialized = http_build_query($filtered);
        $sig = hash_hmac('sha256', $serialized, $secret);

        return response()->json([
            'serialized' => $serialized,
            'signature' => $sig,
        ]);
    }

    public function autoCallback(Request $request)
    {
        // Dev-only: update payment status directly without HTTP callback
        $intentId = (string) $request->query('intent_id');
        $status = (string) $request->query('status', 'succeeded');

        $payment = \App\Models\Payment::where('provider', 'mada')
            ->where('provider_intent_id', $intentId)
            ->first();

        if (!$payment) {
            return response()->json(['message' => 'Payment not found for intent_id'], 404);
        }

        $payment->status = in_array($status, ['succeeded','failed','canceled']) ? ($status === 'canceled' ? 'failed' : $status) : 'succeeded';
        $meta = $payment->meta ?? [];
        $meta['dev_auto_callback'] = [
            'at' => now()->toISOString(),
            'intent_id' => $intentId,
            'status' => $payment->status,
        ];
        $payment->meta = $meta;
        $payment->save();

        // If linked order exists and payment succeeded, mark it paid
        if ($payment->status === 'succeeded' && $payment->payable instanceof \App\Models\Order) {
            $order = $payment->payable;
            $order->status = 'paid';
            $order->payment_method = 'mada';
            $order->payment_ref = $payment->provider_intent_id;
            $order->save();
        }

        // Build and return invoice payload
        $invoice = [
            'payment' => [
                'id' => $payment->id,
                'provider' => $payment->provider,
                'provider_intent_id' => $payment->provider_intent_id,
                'status' => $payment->status,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'created_at' => $payment->created_at,
            ],
        ];

        if ($payment->payable instanceof \App\Models\Order) {
            $freshOrder = \App\Models\Order::with('items')->find($payment->payable->id);
            if ($freshOrder) {
                $invoice['order'] = [
                    'id' => $freshOrder->id,
                    'status' => $freshOrder->status,
                    'subtotal' => $freshOrder->subtotal,
                    'delivery_fee' => $freshOrder->delivery_fee,
                    'tax' => $freshOrder->tax ?? null,
                    'discount' => $freshOrder->discount,
                    'total' => $freshOrder->total,
                    'items' => $freshOrder->items->map(function ($it) {
                        return [
                            'name' => optional($it->menuItem)->name,
                            'quantity' => $it->quantity,
                            'unit_price' => $it->unit_price,
                            'line_total' => $it->line_total,
                        ];
                    }),
                ];
            }
        }

        return response()->json([
            'payment_id' => $payment->id,
            'intent_id' => $intentId,
            'status' => $payment->status,
            'invoice' => $invoice,
        ]);
    }
}


