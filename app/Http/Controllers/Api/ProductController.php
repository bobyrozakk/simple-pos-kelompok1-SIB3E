<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'q' => ['sometimes', 'required', 'string', 'min:2'],
            'sort' => ['sometimes', 'required', 'in:name,-name,price,-price'],
            'per_page' => ['sometimes', 'required', 'integer', 'min:1', 'max:100'],
        ]);

        $sort = $filters['sort'] ?? 'name';
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';

        $products = Product::with('category')
            ->when(isset($filters['category_id']), function ($query) use ($filters) {
                $query->where('category_id', $filters['category_id']);
            })
            ->when(isset($filters['q']), function ($query) use ($filters) {
                $query->where('name', 'like', '%'.$filters['q'].'%');
            })
            ->orderBy(ltrim($sort, '-'), $direction)
            ->orderBy('id')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return ProductResource::collection($products);
    }
}