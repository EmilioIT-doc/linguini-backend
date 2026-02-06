<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index()
    {
        $categories = Category::query()
            ->select('id', 'name', 'slug', 'sort_order', 'is_active', 'created_at', 'updated_at')
            ->where('is_active', 1)
            ->with(['products' => function ($q) {
                $q->select('id', 'category_id', 'name', 'description', 'price', 'image_url', 'is_active', 'created_at', 'updated_at')
                  ->where('is_active', 1)
                  ->orderBy('name'); // si luego quieres orden custom, lo cambiamos
            }])
            ->orderBy('sort_order')
            ->get();

        return response()->json($categories);
    }
}
