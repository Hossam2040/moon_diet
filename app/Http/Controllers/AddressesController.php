<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;

class AddressesController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $addresses = Address::where('user_id', $user->id)->orderByDesc('is_default')->orderBy('id', 'desc')->get();
        return response()->json($addresses);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'label' => ['nullable', 'string', 'max:255'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'line1' => ['nullable', 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'country' => ['nullable', 'string', 'max:2'],
            'is_default' => ['nullable', 'boolean'],
        ]);
        $validated['user_id'] = $user->id;
        $address = Address::create($validated);
        if (!empty($validated['is_default'])) {
            Address::where('user_id', $user->id)->where('id', '!=', $address->id)->update(['is_default' => false]);
        }
        return response()->json($address, 201);
    }

    public function update(Request $request, int $id)
    {
        $user = $request->user();
        $address = Address::where('user_id', $user->id)->findOrFail($id);
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'label' => ['nullable', 'string', 'max:255'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'line1' => ['sometimes', 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'country' => ['nullable', 'string', 'max:2'],
            'is_default' => ['nullable', 'boolean'],
        ]);
        $address->fill($validated)->save();
        if (array_key_exists('is_default', $validated) && $validated['is_default']) {
            Address::where('user_id', $user->id)->where('id', '!=', $address->id)->update(['is_default' => false]);
        }
        return response()->json($address);
    }

    public function destroy(Request $request, int $id)
    {
        $user = $request->user();
        $deleted = Address::where('user_id', $user->id)->where('id', $id)->delete();
        return response()->json(['deleted' => (bool)$deleted]);
    }
}


