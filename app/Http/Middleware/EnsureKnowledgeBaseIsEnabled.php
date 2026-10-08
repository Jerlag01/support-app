<?php

namespace App\Http\Middleware;

use App\Support\HelpdeskSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureKnowledgeBaseIsEnabled
{
    public function __construct(private HelpdeskSettings $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($this->settings->get('show_knowledge_base'), 404);

        return $next($request);
    }
}
