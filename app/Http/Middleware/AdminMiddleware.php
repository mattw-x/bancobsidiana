<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Verificamos si el usuario está logueado y si es admin
        if (Auth::check() && Auth::user()->is_admin) {
            return $next($request);
        }

        // Si no es admin, lo mandamos al dashboard normal o al inicio
        return redirect('/dashboard')->with('error', 'No tienes permisos de administrador.');
    }
}
