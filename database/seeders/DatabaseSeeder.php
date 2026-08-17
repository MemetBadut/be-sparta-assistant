<?php

namespace Database\Seeders;

use App\Enums\ArticleStatus;
use App\Enums\Category;
use App\Enums\Role;
use App\Models\KnowledgeBaseArticle;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $employee = User::factory()->create([
            'name' => 'Demo Employee',
            'email' => 'employee@example.com',
            'employee_id' => 'EMP-0001',
            'division' => 'Operations',
            'password' => Hash::make('password'),
        ]);

        $technician = User::factory()->role(Role::Technician)->create([
            'name' => 'Demo Technician',
            'email' => 'technician@example.com',
            'employee_id' => 'TECH-0001',
            'division' => 'IT',
            'password' => Hash::make('password'),
        ]);

        User::factory()->role(Role::Admin)->create([
            'name' => 'Demo Admin',
            'email' => 'admin@example.com',
            'employee_id' => 'ADMIN-0001',
            'division' => 'IT',
            'password' => Hash::make('password'),
        ]);

        $article = KnowledgeBaseArticle::factory()->create([
            'updated_by' => $technician->id,
        ]);

        KnowledgeBaseArticle::factory()->draft()->create([
            'title' => 'Unpublished printer troubleshooting draft',
            'category' => Category::Printer,
            'status' => ArticleStatus::Draft,
            'updated_by' => $technician->id,
        ]);

        $employee->tickets()->create([
            'ticket_number' => 'IT-'.now()->year.'-00001',
            'name' => $employee->name,
            'division' => $employee->division,
            'issue_title' => 'Demo Wi-Fi issue',
            'description' => 'Demo ticket for local development.',
            'category' => $article->category,
            'priority' => 'Medium',
            'status' => 'Open',
            'kb_article_id' => $article->id,
            'troubleshooting_history' => $article->solution_steps,
        ]);
    }
}
