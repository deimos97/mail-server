<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Páginas que aún no existen pero a las que ya enlaza la web: los textos legales (Fase 5).
 */
class PlaceholderController extends Controller
{
    public function legal(string $page): View
    {
        $title = config("landing.legal.$page") ?? abort(404);

        return view('placeholder', [
            'title' => $title,
            'text' => 'Este documento está en preparación.',
        ]);
    }
}
