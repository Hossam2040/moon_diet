<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Setting;

class OrdersController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $orders = Order::with('items')
            ->where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->get();
        
        if ($orders->isEmpty()) {
            return response()->json([
                'message' => 'No orders found',
                'data' => []
            ]);
        }
        
        return response()->json([
            'data' => $orders
        ]);
    }

    public function show(Request $request, int $id)
    {
        $user = $request->user();
        $order = Order::with(['items', 'address'])
            ->where('user_id', $user->id)
            ->findOrFail($id);
        return response()->json($order);
    }

    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'address_id' => ['nullable', 'integer', 'exists:addresses,id'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'coupon_code' => ['nullable', 'string'],
        ]);
        $user = $request->user();
        $cart = Cart::with(['items.menuItem'])->firstOrCreate(['user_id' => $user->id]);
        
        // Debug: Check cart contents
        if ($cart->items->isEmpty()) {
            return response()->json([
                'message' => 'Cart is empty',
                'debug' => [
                    'cart_id' => $cart->id,
                    'user_id' => $user->id,
                    'items_count' => $cart->items->count()
                ]
            ], 400);
        }

        $deliveryFee = (float) ($validated['delivery_fee'] ?? Setting::get('delivery_fee', 0));

        return DB::transaction(function () use ($user, $cart, $validated, $deliveryFee) {
            $subtotal = 0;
            foreach ($cart->items as $ci) {
                $subtotal += (float) $ci->unit_price * (int) $ci->quantity;
            }
            $discount = 0;
            if (!empty($validated['coupon_code'])) {
                $now = now();
                $coupon = \App\Models\Coupon::where('code', $validated['coupon_code'])
                    ->where('active', true)
                    ->where(function ($q) use ($now) { $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now); })
                    ->where(function ($q) use ($now) { $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now); })
                    ->first();
                if ($coupon && (!$coupon->max_uses || $coupon->uses_count < $coupon->max_uses) && $subtotal >= (float)$coupon->min_subtotal) {
                    $discount = $coupon->type === 'percent'
                        ? round($subtotal * ((float)$coupon->amount) / 100, 2)
                        : (float)$coupon->amount;
                    $coupon->increment('uses_count');
                }
            }
            $taxPercent = (float) Setting::get('tax_percent', 0);
            $tax = round($subtotal * $taxPercent / 100, 2);
            $total = $subtotal + $tax + $deliveryFee - $discount;

            $order = Order::create([
                'user_id' => $user->id,
                'address_id' => $validated['address_id'] ?? null,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'tax' => $tax,
                'discount' => $discount,
                'total' => $total,
            ]);

            foreach ($cart->items as $ci) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $ci->menu_item_id,
                    'quantity' => $ci->quantity,
                    'unit_price' => $ci->unit_price,
                    'line_total' => (float) $ci->unit_price * (int) $ci->quantity,
                ]);
            }

            // clear cart
            $cart->items()->delete();
            $cart->delete();

            return response()->json($order, 201);
        });
    }
}


