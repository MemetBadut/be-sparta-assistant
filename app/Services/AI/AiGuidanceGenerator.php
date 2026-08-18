<?php

namespace App\Services\AI;

interface AiGuidanceGenerator
{
    /** @return array{guidance:?string,steps:list<string>,recommend_ticket:bool} */
    public function generate(string $category, string $description, array $context = []): array;
}
