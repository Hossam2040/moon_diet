<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Http\Request;
use App\Services\MadaService;

class PaymentsController extends Controller
{
    public function createIntent(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:order,subscription'],
            'id' => ['required', 'integer'],
            'provider' => ['required', 'string', 'in:stripe,mada'],
        ]);

        $user = $request->user();
        $payable = $validated['type'] === 'order'
            ? Order::where('user_id', $user->id)->findOrFail($validated['id'])
            : Subscription::where('user_id', $user->id)->findOrFail($validated['id']);

        $amount = $validated['type'] === 'order' ? $payable->total : $payable->total_paid;

        $payment = Payment::create([
            'user_id' => $user->id,
            'provider' => $validated['provider'],
            'status' => 'pending',
            'amount' => $amount,
            'currency' => 'SAR',
        ]);

        if ($validated['provider'] === 'stripe') {
            // Here you'd call Stripe to create PaymentIntent and store id
            $payment->provider_intent_id = 'pi_dummy_'.uniqid();
            $payment->payable()->associate($payable);
            $payment->save();

            return response()->json([
                'payment_id' => $payment->id,
                'client_secret' => 'dummy_client_secret',
            ], 201);
        }

        // Mada provider
        $mada = new MadaService();
        $reference = 'md_' . $payment->id . '_' . uniqid();
        $description = $validated['type'] === 'order' ? ('Order #' . $payable->id) : ('Subscription #' . $payable->id);
        $madaInit = $mada->createPaymentIntent($reference, $description, (string) $amount, [
            'user_id' => $user->id,
            'payable_type' => $validated['type'],
            'payable_id' => $payable->id,
            'payment_id' => $payment->id,
        ]);

        $payment->provider_intent_id = $madaInit['intent_id'];
        $payment->payable()->associate($payable);
        $payment->save();

        // In local/testing optionally auto-complete payment without manual callback
        $autoCompletedInvoice = null;
        if (config('services.mada.auto_complete_local') && app()->environment(['local', 'testing'])) {
            $requestForCallback = new Request([
                'id' => $payment->provider_intent_id,
                'status' => 'succeeded',
                'amount' => (string) $payment->amount,
                'currency' => $payment->currency,
            ]);
            // Bypass signature in local by directly marking succeeded
            $payment->status = 'succeeded';
            if ($payment->payable instanceof \App\Models\Order) {
                $order = $payment->payable;
                $order->status = 'paid';
                $order->payment_method = 'mada';
                $order->payment_ref = $payment->provider_intent_id;
                $order->save();

                // We will not inline invoice here anymore; it will be returned from redirect/auto-callback
                $autoCompletedInvoice = null;
            }
            $payment->save();
        }

        return response()->json([
            'payment_id' => $payment->id,
            'redirect_url' => $madaInit['redirect_url'],
            // No inline invoice; use dev redirect or invoice endpoint after completion
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

    public function callbackMada(Request $request)
    {
        $signature = (string) $request->header('X-Signature', '');
        $data = $request->all();
        $mada = new MadaService();
        if (!$mada->verifySignature($data, $signature)) {
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        $intentId = (string) ($data['id'] ?? '');
        $status = (string) ($data['status'] ?? '');
        $payment = Payment::where('provider', 'mada')->where('provider_intent_id', $intentId)->first();
        if ($payment) {
            if ($status === 'succeeded') {
                $payment->status = 'succeeded';
                // If linked to an order/subscription, mark appropriately
                if ($payment->payable) {
                    $payable = $payment->payable;
                    if ($payable instanceof \App\Models\Order) {
                        $payable->status = 'paid';
                        $payable->payment_method = 'mada';
                        $payable->payment_ref = $payment->provider_intent_id;
                        $payable->save();
                    }
                    // Add more logic for subscriptions if needed
                }
            } elseif ($status === 'failed' || $status === 'canceled') {
                $payment->status = 'failed';
            }
            $payment->meta = array_merge($payment->meta ?? [], ['mada_payload' => $data]);
            $payment->save();
        }

        return response()->json(['received' => true]);
    }

    public function invoice(Request $request, int $id)
    {
        $user = $request->user();
        $payment = Payment::with(['payable' => function ($q) {
            // no-op, just eager load
        }])->where('user_id', $user->id)->findOrFail($id);

        $response = [
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
            $order = \App\Models\Order::with('items')->find($payment->payable->id);
            if ($order) {
                $response['order'] = [
                    'id' => $order->id,
                    'status' => $order->status,
                    'subtotal' => $order->subtotal,
                    'delivery_fee' => $order->delivery_fee,
                    'tax' => $order->tax ?? null,
                    'discount' => $order->discount,
                    'total' => $order->total,
                    'items' => $order->items->map(function ($it) {
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

        return response()->json($response);
    }

    public function invoicePdf(Request $request, int $id)
    {
        $user = $request->user();
        $payment = Payment::with('payable')->where('user_id', $user->id)->findOrFail($id);

        $order = null;
        if ($payment->payable instanceof \App\Models\Order) {
            $order = \App\Models\Order::with('items')->find($payment->payable->id);
        }

        $html = view('invoices.payment', [
            'payment' => $payment,
            'order' => $order,
            'user' => $user,
        ])->render();

        $filename = 'invoice-payment-' . $payment->id . '.pdf';

        // If DomPDF (barryvdh/laravel-dompdf) is installed, return a PDF. Otherwise, fallback to HTML.
        if (class_exists('Barry\\vdh\\DomPDF\\Facade\\Pdf')) {
            /** @var \Barry\vdh\DomPDF\Facade\Pdf $pdfFacade */
            $pdfFacade = app('Barry\\vdh\\DomPDF\\Facade\\Pdf');
            $pdf = $pdfFacade::loadHTML($html);
            return $request->boolean('download', false)
                ? $pdf->download($filename)
                : $pdf->stream($filename);
        }

        return response($html)->header('Content-Type', 'text/html');
    }
}


