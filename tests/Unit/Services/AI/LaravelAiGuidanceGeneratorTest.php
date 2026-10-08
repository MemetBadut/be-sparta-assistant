<?php

namespace Tests\Unit\Services\AI;

use App\Services\AI\HelpdeskGuidanceAgent;
use App\Services\AI\LaravelAiGuidanceGenerator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LaravelAiGuidanceGeneratorTest extends TestCase
{
    #[Test]
    public function guidance_prompt_restricts_response_language_to_indonesian_or_english(): void
    {
        $method = new \ReflectionMethod(LaravelAiGuidanceGenerator::class, 'prompt');
        $prompt = $method->invoke(new LaravelAiGuidanceGenerator(), 'laptop_pc', 'blue screen laptopnya', []);

        $this->assertStringContainsString('Use Indonesian or English only', $prompt);
        $this->assertStringContainsString('respond in the same language as the issue description', $prompt);
        $this->assertStringContainsString('respond in Indonesian', $prompt);
    }

    #[Test]
    public function agent_instructions_restrict_response_language(): void
    {
        $instructions = (new HelpdeskGuidanceAgent())->instructions();

        $this->assertStringContainsString('Use Indonesian or English only', $instructions);
        $this->assertStringContainsString('respond in Indonesian', $instructions);
    }
}
