<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'categorie' => ['nullable', 'string', 'max:120'],
            'prix_min' => ['nullable', 'numeric', 'min:0'],
            'prix_max' => ['nullable', 'numeric', 'min:0'],
        ]);

        $products = Product::query()
            ->with('category')
            ->active()
            ->when(filled($filters['categorie'] ?? null), function ($query) use ($filters): void {
                $query->whereHas('category', fn ($query) => $query->where('slug', $filters['categorie']));
            })
            ->when(filled($filters['q'] ?? null), function ($query) use ($filters): void {
                $search = '%'.$filters['q'].'%';
                $query->where(fn ($query) => $query->where('name', 'like', $search)->orWhere('description', 'like', $search));
            })
            ->when(filled($filters['prix_min'] ?? null), function ($query) use ($filters): void {
                $query->where('price', '>=', (float) $filters['prix_min']);
            })
            ->when(filled($filters['prix_max'] ?? null), function ($query) use ($filters): void {
                $query->where('price', '<=', (float) $filters['prix_max']);
            })
            ->latest()
            ->paginate(9)
            ->withQueryString();

        return view('products.index', [
            'products' => $products,
            'categories' => Category::active()->orderBy('name')->get(),
            'activeCategory' => $request->string('categorie')->toString(),
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        return view('products.show', [
            'product' => $product->load('category'),
            'relatedProducts' => Product::query()
                ->with('category')
                ->active()
                ->whereKeyNot($product->id)
                ->when($product->category_id, fn ($query) => $query->where('category_id', $product->category_id))
                ->take(3)
                ->get(),
        ]);
    }
}
