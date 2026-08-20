<?php

namespace App\Services\AI;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

class HelpdeskGuidanceAgent implements Agent, HasProviderOptions
{
    use Promptable;

    public function instructions(): string
    {
        return 'You provide concise, safe IT helpdesk general guidance.';
    }

    public function providerOptions(Lab|string $provider): array
    {
        return ['stream' => false];
    }
}
