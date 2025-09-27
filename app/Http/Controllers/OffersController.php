<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OffersController extends Controller
{
    // Public endpoints - anyone can view
    public function index(Request $request)
    {
        try {
            $offers = Offer::active()
                ->orderBy('created_at', 'desc')
                ->get();
            
            if ($offers->isEmpty()) {
                return response()->json([
                    'message' => 'No active offers found',
                    'data' => []
                ]);
            }
            
            return response()->json([
                'data' => $offers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Offers table not found. Run migration first.',
                'data' => []
            ]);
        }
    }

    public function show($id)
    {
        $offer = Offer::active()->find($id);
        
        if (!$offer) {
            return response()->json(['message' => 'Offer not found'], 404);
        }
        
        return response()->json($offer);
    }

    // Admin endpoints - admin only
    public function adminIndex(Request $request)
    {
        $offers = Offer::orderBy('created_at', 'desc')->get();
        
        if ($offers->isEmpty()) {
            return response()->json([
                'message' => 'No offers found',
                'data' => []
            ]);
        }
        
        return response()->json([
            'data' => $offers
        ]);
    }

    public function adminStore(Request $request)
    {
        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        // Upload image
        $imagePath = $request->file('image')->store('offers', 'public');
        $imageUrl = asset('storage/' . $imagePath);

        $offer = Offer::create([
            'image_url' => $imageUrl,
            'is_active' => $validated['is_active'] ?? true,
        ]);
        
        return response()->json($offer, 201);
    }

    public function adminShow($id)
    {
        $offer = Offer::find($id);
        
        if (!$offer) {
            return response()->json(['message' => 'Offer not found'], 404);
        }
        
        return response()->json($offer);
    }

    public function adminUpdate(Request $request, $id)
    {
        $offer = Offer::find($id);
        
        if (!$offer) {
            return response()->json(['message' => 'Offer not found'], 404);
        }

        $validated = $request->validate([
            'image' => ['sometimes', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $updateData = [];
        
        if ($request->hasFile('image')) {
            // Delete old image
            $oldImagePath = str_replace(asset('storage/'), '', $offer->image_url);
            if ($oldImagePath && Storage::disk('public')->exists($oldImagePath)) {
                Storage::disk('public')->delete($oldImagePath);
            }
            
            // Upload new image
            $imagePath = $request->file('image')->store('offers', 'public');
            $updateData['image_url'] = asset('storage/' . $imagePath);
        }
        
        if (isset($validated['is_active'])) {
            $updateData['is_active'] = $validated['is_active'];
        }

        $offer->update($updateData);
        
        return response()->json($offer);
    }

    public function adminDestroy($id)
    {
        $offer = Offer::find($id);
        
        if (!$offer) {
            return response()->json(['message' => 'Offer not found'], 404);
        }
        
        // Delete image file
        $imagePath = str_replace(asset('storage/'), '', $offer->image_url);
        if ($imagePath && Storage::disk('public')->exists($imagePath)) {
            Storage::disk('public')->delete($imagePath);
        }
        
        $offer->delete();
        
        return response()->json(['deleted' => true]);
    }

    public function adminToggleActive($id)
    {
        $offer = Offer::find($id);
        
        if (!$offer) {
            return response()->json(['message' => 'Offer not found'], 404);
        }
        
        $offer->is_active = !$offer->is_active;
        $offer->save();
        
        return response()->json([
            'id' => $offer->id,
            'is_active' => $offer->is_active,
            'message' => $offer->is_active ? 'Offer activated' : 'Offer deactivated'
        ]);
    }
}
