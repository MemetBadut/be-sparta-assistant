<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;
use Throwable;

class LaravelAiGuidanceGenerator implements AiGuidanceGenerator
{
    public function generate(string $category, string $description, array $context = []): array
    {
        try {
            $provider = config('ai.default');
            $model = config("ai.providers.{$provider}.models.text.default")
                ?? config("ai.providers.{$provider}.model")
                ?? env('LOCAL_AI_MODEL');

            if (! $model || ! config("ai.providers.{$provider}.key")) {
                return $this->fallback();
            }

            $prompt = $this->prompt($category, $description, $context);
            $response = (new HelpdeskGuidanceAgent)->prompt($prompt, provider: $provider, model: $model);
            $text = trim((string) $response->text);

            return $text === '' ? $this->fallback() : [
                'guidance' => $text,
                'steps' => [],
                'recommend_ticket' => true,
            ];
        } catch (Throwable $exception) {
            Log::warning('AI guidance unavailable', ['exception' => $exception::class, 'message' => $exception->getMessage(), 'at' => $exception->getFile().':'.$exception->getLine()]);

            return $this->fallback();
        }
    }

    private function prompt(string $category, string $description, array $context): string
    {
        $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return "Category: {$category}\nIssue: {$description}\nVerified context (reference only): {$contextJson}\n\nGive cautious, generic troubleshooting guidance. Do not claim company policy, do not invent internal procedures, and recommend creating an IT ticket. Return plain text only.";
    }

    /** @return array{guidance:?string,steps:list<string>,recommend_ticket:bool} */
    private function fallback(): array
    {
        return [
            'guidance' => 'No verified troubleshooting article matched this issue. Please create an IT ticket so support can investigate it.',
            'steps' => [],
            'recommend_ticket' => true,
        ];
    }
}
