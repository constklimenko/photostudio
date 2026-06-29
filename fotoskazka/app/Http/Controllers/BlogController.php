<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Service;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $posts = Post::query()
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->when($request->q, fn ($q, $search) => $q->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%");
            }))
            ->when($request->category, fn ($q, $slug) => $q->whereHas('category', fn ($q) => $q->where('slug', $slug)))
            ->orderBy('published_at', 'desc')
            ->with('cover')
            ->paginate(6);

        $categories = Category::query()
            ->where('type', 'post')
            ->withCount('posts')
            ->get();

        $recentPosts = Post::query()
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->limit(5)
            ->get();

        return view('blog.index', compact('posts', 'categories', 'recentPosts'));
    }

    public function show(string $slug)
    {
        $post = Post::query()
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where('slug', $slug)
            ->with('cover', 'category', 'albums.cover', 'albums.photos.media')
            ->firstOrFail();

        $categories = Category::query()
            ->where('type', 'post')
            ->withCount('posts')
            ->get();

        $recentPosts = Post::query()
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereKeyNot($post->id)
            ->latest('published_at')
            ->limit(5)
            ->with('cover')
            ->get();

        $services = Service::query()
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->get(['id', 'title']);

        return view('blog.show', compact('post', 'categories', 'recentPosts', 'services'));
    }
}
