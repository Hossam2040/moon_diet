<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Http\Request;

class PaymentsController extends Controller
{
    public function createIntent(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:order,subscription'],
            'id' => ['required', 'integer'],
            'provider' => ['required', 'string', 'in:stripe'],
        ]);

        $user = $request->user();
        $payable = $validated['type'] === 'order'
            ? Order::where('user_id', $user->id)->findOrFail($validated['id'])
            : Subscription::where('user_id', $user->id)->findOrFail($validated['id']);

        $amount = $validated['type'] === 'order' ? $payable->total : $payable->total_paid;

        $payment = Payment::create([
            'user_id' => $user->id,
            'provider' => 'stripe',
            'status' => 'pending',
            'amount' => $amount,
            'currency' => 'SAR',
        ]);

        // Here you'd call Stripe to create PaymentIntent and store id
        $payment->provider_intent_id = 'pi_dummy_'.uniqid();
        $payment->payable()->associate($payable);
        $payment->save();

        return response()->json([
            'payment_id' => $payment->id,
            'client_secret' => 'dummy_client_secret',
        ], 201);
    }

    public function webhookStripe(Request $request)
    {
        // Verify signature in production
        $eventType = $request->input('type');
        $intentId = data_get($request->all(), 'data.object.id');
        $payment = Payment::where('provider', 'stripe')->where('provider_intent_id', $intentId)->first();
        if ($payment) {
            if ($eventType === 'payment_intent.succeeded') {
                $payment->status = 'succeeded';
            } elseif ($eventType === 'payment_intent.payment_failed') {
                $payment->status = 'failed';
            }
            $payment->save();
        }
        return response()->json(['received' => true]);
    }
}


