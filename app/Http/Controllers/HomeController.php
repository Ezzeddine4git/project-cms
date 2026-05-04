<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\HomePageSection;
use App\Models\Product;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('home', [
            'sections' => HomePageSection::active()->get()->keyBy('key'),
            'featuredProducts' => Product::query()
                ->with('category')
                ->active()
                ->featured()
                ->latest()
                ->take(4)
                ->get(),
            'blogPosts' => BlogPost::query()
                ->published()
                ->latest('published_at')
                ->take(3)
                ->get(),
        ]);
    }
}
