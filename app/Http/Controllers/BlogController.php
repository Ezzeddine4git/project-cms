<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(): View
    {
        return view('blog.index', [
            'posts' => BlogPost::query()
                ->published()
                ->latest('published_at')
                ->paginate(6),
        ]);
    }

    public function show(BlogPost $post): View
    {
        abort_unless($post->is_published && (blank($post->published_at) || $post->published_at->isPast()), 404);

        return view('blog.show', [
            'post' => $post,
            'morePosts' => BlogPost::query()
                ->published()
                ->whereKeyNot($post->id)
                ->latest('published_at')
                ->take(3)
                ->get(),
        ]);
    }
}
