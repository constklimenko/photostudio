<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use App\Services\HomeContentService;
use App\Services\PageContentService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __invoke(PageContentService $pageContent, HomeContentService $homeContent)
    {
        $page = $pageContent->get('home');

        $heroImages = $homeContent->heroImages();
        $heroButtons = $homeContent->heroButtons();

        $socialLinks = $homeContent->socialLinks();

        $featuredWorks = $homeContent->featuredWorks();
        $featuredWorksBlock = $homeContent->blockText('portfolio', 'Избранные работы', 'Наши лучшие проекты');

        return view('home', compact(
            'page',
            'heroImages',
            'heroButtons',
            'socialLinks',
            'featuredWorks',
            'featuredWorksBlock',
        ));
    }

    public function storeInquiry(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'required|email|max:255',
            'service_id' => 'nullable|exists:services,id',
            'shooting_date' => 'nullable|date|after_or_equal:today',
            'message' => 'nullable|string',
            'agreed_to_terms' => 'required|accepted',
        ]);

        $validated['agreed_to_terms'] = true;
        $validated['status'] = 'new';

        Inquiry::create($validated);

        return redirect(request()->headers->get('referer', '/'))
            ->with('success', 'Заявка отправлена! Мы свяжемся с вами в ближайшее время.');
    }
}
