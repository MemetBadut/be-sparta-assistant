<?php

namespace Database\Factories;

use App\Enums\ArticleStatus;
use App\Enums\Category;
use App\Models\KnowledgeBaseArticle;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<KnowledgeBaseArticle> */
class KnowledgeBaseArticleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => 'Laptop connected to Wi-Fi but no internet',
            'category' => Category::Network,
            'symptoms' => 'Wi-Fi shows connected but websites cannot be accessed.',
            'keywords' => 'wifi, internet, connected, no internet, network',
            'problem_description' => 'The device associates with the company Wi-Fi but has no working internet access.',
            'solution_steps' => [
                'Check Wi-Fi connection.',
                'Disconnect and reconnect.',
                'Restart network connection.',
                'Run Windows Network Troubleshooter.',
                'Restart device.',
            ],
            'expected_result' => 'Internet access is restored.',
            'status' => ArticleStatus::Published,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['status' => ArticleStatus::Draft]);
    }
}
