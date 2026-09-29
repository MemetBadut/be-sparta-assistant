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
        return 'You provide concise, safe IT helpdesk general guidance. Always respond in the same language as the issue description. If the description mixes languages or is ambiguous, respond in Indonesian. Use Indonesian or English only.';
    }

    public function providerOptions(Lab|string $provider): array
    {
        return ['stream' => false];
    }
}
