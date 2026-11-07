<?php

namespace App\Http\Controllers;

use App\Services\PageContentService;

class PrivacyPolicyController extends Controller
{
    public function __invoke(PageContentService $pageContent)
    {
        $page = $pageContent->get('privacy');

        abort_if($page === null, 404);

        return view('privacy', ['page' => $page]);
    }
}
