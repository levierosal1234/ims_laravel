<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    protected $except = [
        '/fbmorders/fetch-work-history',
         'force-logout',
        'keep-alive',
        'csrf-token'
        
    ];
}