<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function get(Request $request)
    {
        return response()->json([
            'tax_percent' => (float) Setting::get('tax_percent', '0'),
            'delivery_fee' => (float) Setting::get('delivery_fee', '0'),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'tax_percent' => ['required', 'numeric', 'min:0'],
            'delivery_fee' => ['required', 'numeric', 'min:0'],
        ]);
        Setting::set('tax_percent', $validated['tax_percent']);
        Setting::set('delivery_fee', $validated['delivery_fee']);
        return response()->json(['updated' => true] + $validated);
    }
}


