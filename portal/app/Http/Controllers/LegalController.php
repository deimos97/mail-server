<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/** Textos legales (/legal/{página}). Los datos del titular, en config/legal.php. */
class LegalController extends Controller
{
    public function __invoke(string $page): View
    {
        $title = config("landing.legal.$page") ?? abort(404);
        abort_unless(view()->exists("legal.$page"), 404);

        return view("legal.$page", ['title' => $title, 'l' => config('legal')]);
    }
}
