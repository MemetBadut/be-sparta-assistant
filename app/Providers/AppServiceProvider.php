<?php

namespace App\Providers;

use App\Models\Ticket;
use App\Models\TroubleshootingResult;
use App\Policies\TicketPolicy;
use App\Services\AI\AiGuidanceGenerator;
use App\Services\AI\LaravelAiGuidanceGenerator;
use App\Services\KnowledgeBase\KeywordKnowledgeBaseRetriever;
use App\Services\KnowledgeBase\KnowledgeBaseRetriever;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;
Request::macro('rateLimitKey', fn (): string => $this->ip().'|'.$this->user()?->id);

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(KnowledgeBaseRetriever::class, KeywordKnowledgeBaseRetriever::class);
        $this->app->bind(AiGuidanceGenerator::class, LaravelAiGuidanceGenerator::class);
        Gate::policy(Ticket::class, TicketPolicy::class);
        Gate::policy(TroubleshootingResult::class, \App\Policies\TroubleshootingResultPolicy::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
