<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Inquiry;
use App\Models\Post;
use App\Models\Service;
use App\Models\Testimonial;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __invoke()
    {
        $services = Service::query()
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->with('cover')
            ->get(['id', 'cover_media_id', 'title', 'slug', 'short_description', 'price_from']);

        $featuredWorks = Album::query()
            ->where('type', 'portfolio')
            ->where('is_featured', true)
            ->where('is_published', true)
            ->with('cover')
            ->get(['id', 'cover_media_id', 'title', 'slug']);

        $testimonials = Testimonial::query()
            ->where('is_published', true)
            ->with('photo')
            ->get(['id', 'media_id', 'client_name', 'content']);

        $latestPosts = Post::query()
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->latest('published_at')
            ->take(3)
            ->with('cover')
            ->get(['id', 'cover_media_id', 'title', 'slug', 'excerpt', 'published_at']);

        $serviceList = Service::query()
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->get(['id', 'title']);

        return view('home', compact(
            'services',
            'featuredWorks',
            'testimonials',
            'latestPosts',
            'serviceList',
        ));
    }

    public function storeInquiry(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'service_id' => 'nullable|exists:services,id',
            'message' => 'nullable|string',
        ]);

        Inquiry::create($validated);

        return redirect('/#inquiry-form')
            ->with('success', 'Заявка отправлена! Мы свяжемся с вами в ближайшее время.');
    }
}
