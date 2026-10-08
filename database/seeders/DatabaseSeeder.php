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

        $admin = User::factory()->role(Role::Admin)->create([
            'name' => 'Demo Admin',
            'email' => 'admin@example.com',
            'employee_id' => 'ADMIN-0001',
            'division' => 'IT',
            'password' => Hash::make('password'),
        ]);

        $article = KnowledgeBaseArticle::factory()->create([
            'updated_by' => $admin->id,
        ]);

        KnowledgeBaseArticle::factory()->create([
            'title' => 'Windows update restart issue',
            'category' => Category::Windows,
            'symptoms' => 'Windows restarts unexpectedly after a system update.',
            'keywords' => 'windows, system, update, restart, boot',
            'problem_description' => 'The computer does not complete a Windows update normally.',
            'steps' => ['Save your work.', 'Restart the computer once.', 'Contact IT if Windows still cannot boot.'],
            'expected_result' => 'Windows starts normally after the update.',
            'updated_by' => $admin->id,
        ]);

        KnowledgeBaseArticle::factory()->create([
            'title' => 'Laptop keyboard not responding',
            'category' => Category::Computer,
            'symptoms' => 'Laptop keyboard keys do not respond.',
            'keywords' => 'laptop, keyboard, hardware, keys',
            'problem_description' => 'The built-in keyboard does not accept input.',
            'steps' => ['Reconnect any external keyboard.', 'Restart the laptop.', 'Contact IT if the keyboard remains unresponsive.'],
            'expected_result' => 'Keyboard input works normally.',
            'updated_by' => $admin->id,
        ]);

        KnowledgeBaseArticle::factory()->create([
            'title' => 'Printer queue stuck',
            'category' => Category::Printer,
            'symptoms' => 'Print jobs remain queued.',
            'keywords' => 'printer, print, queue, paper',
            'problem_description' => 'The printer does not process queued documents.',
            'steps' => ['Cancel the stuck print job.', 'Check that paper is loaded.', 'Restart the printer.'],
            'expected_result' => 'The document prints successfully.',
            'updated_by' => $admin->id,
        ]);

        KnowledgeBaseArticle::factory()->create([
            'title' => 'Office application stops responding',
            'category' => Category::Software,
            'symptoms' => 'An Office application stops responding.',
            'keywords' => 'office, application, software, crash, program',
            'problem_description' => 'A desktop application closes or stops responding during use.',
            'steps' => ['Save work in other applications.', 'Close and reopen the application.', 'Contact IT if the issue repeats.'],
            'expected_result' => 'The application opens and responds normally.',
            'updated_by' => $admin->id,
        ]);

        KnowledgeBaseArticle::factory()->draft()->create([
            'title' => 'Unpublished printer troubleshooting draft',
            'category' => Category::Printer,
            'status' => ArticleStatus::Draft,
            'updated_by' => $admin->id,
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
            'troubleshooting_history' => ['Check Wi-Fi connection.', 'Disconnect and reconnect.'],
        ]);
    }
}
