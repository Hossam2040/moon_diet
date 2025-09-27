<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\MenuItem;
use Illuminate\Http\Request;

class CartController extends Controller
{
    protected function getOrCreateCart(int $userId): Cart
    {
        return Cart::firstOrCreate(['user_id' => $userId]);
    }

    public function view(Request $request)
    {
        $user = $request->user();
        $cart = $this->getOrCreateCart($user->id);
        $cart->load(['items.menuItem']);
        return response()->json($cart);
    }

    public function add(Request $request)
    {
        $validated = $request->validate([
            'menu_item_id' => ['required', 'integer', 'exists:menu_items,id'],
            'quantity' => ['sometimes', 'integer', 'min:1'],
        ]);
        $user = $request->user();
        $cart = $this->getOrCreateCart($user->id);
        $menuItem = MenuItem::findOrFail($validated['menu_item_id']);
        $quantity = (int)($validated['quantity'] ?? 1);

        $item = CartItem::firstOrNew([
            'cart_id' => $cart->id,
            'menu_item_id' => $menuItem->id,
        ]);
        $item->quantity = ($item->exists ? $item->quantity : 0) + $quantity;
        $item->unit_price = $menuItem->price;
        $item->save();

        return response()->json($item, 201);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'menu_item_id' => ['required', 'integer', 'exists:menu_items,id'],
            'quantity' => ['required', 'integer', 'min:0'],
        ]);
        $user = $request->user();
        $cart = $this->getOrCreateCart($user->id);

        $item = CartItem::where('cart_id', $cart->id)
            ->where('menu_item_id', $validated['menu_item_id'])
            ->first();
        if (!$item) {
            return response()->json(['message' => 'Item not in cart'], 404);
        }
        if ($validated['quantity'] === 0) {
            $item->delete();
            return response()->json(['deleted' => true]);
        }
        $item->quantity = $validated['quantity'];
        $item->save();
        return response()->json($item);
    }

    public function remove(Request $request)
    {
        $validated = $request->validate([
            'menu_item_id' => ['required', 'integer', 'exists:menu_items,id'],
        ]);
        $user = $request->user();
        $cart = $this->getOrCreateCart($user->id);
        $deleted = CartItem::where('cart_id', $cart->id)
            ->where('menu_item_id', $validated['menu_item_id'])
            ->delete();
        return response()->json(['deleted' => (bool)$deleted]);
    }

    public function clear(Request $request)
    {
        $user = $request->user();
        $cart = $this->getOrCreateCart($user->id);
        
        // Delete all cart items
        $deletedItems = CartItem::where('cart_id', $cart->id)->delete();
        
        // Delete the cart itself
        $cart->delete();
        
        return response()->json([
            'message' => 'Cart cleared and deleted successfully',
            'deleted_items' => $deletedItems
        ]);
    }
}


