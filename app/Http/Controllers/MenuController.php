<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MenuController extends Controller
{
    public function listCategories(Request $request)
    {
        $lang = $request->query('lang');
        $orderCol = $lang === 'ar' ? 'name_ar' : 'name_en';
        $categories = DB::table('menu_categories')
            ->select('id', 'name_en', 'name_ar', 'description_en', 'description_ar')
            ->orderBy($orderCol)
            ->get();
        return response()->json(['categories' => $categories]);
    }

    public function listItems(Request $request)
    {
        $lang = $request->query('lang');
        $orderCol = $lang === 'ar' ? 'name_ar' : 'name_en';
        $categoryId = $request->query('category_id');
        $query = DB::table('menu_items')
            ->select('id', 'category_id', 'name_en', 'name_ar', 'description_en', 'description_ar', 'price', 'calories', 'image_url')
            ->orderBy($orderCol);
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        $items = $query->get();
        return response()->json(['items' => $items]);
    }
}


