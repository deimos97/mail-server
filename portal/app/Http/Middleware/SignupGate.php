<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Alta cerrada hasta el lanzamiento, con acceso de pruebas por token (config/signup.php). */
class SignupGate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('signup.open') || $request->session()->get('signup.preview') === true) {
            return $next($request);
        }

        $token = config('signup.preview_token');
        if ($token && is_string($request->query('acceso')) && hash_equals($token, $request->query('acceso'))) {
            $request->session()->put('signup.preview', true);

            return redirect()->to($request->fullUrlWithoutQuery('acceso'));
        }

        return response()->view('placeholder', [
            'title' => 'El alta abre muy pronto',
            'text' => 'Estamos terminando de prepararlo todo. Vuelve en unos días para conseguir tu cuenta.',
        ]);
    }
}
