<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;

class SetLocale
{
    public function handle($request, Closure $next)
    {
        App::setLocale('id'); // Tetap Bahasa Indonesia
        return $next($request);
    }
} 