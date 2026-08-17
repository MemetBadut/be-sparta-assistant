<?php

namespace App\Http\Controllers;

use App\Enums\Category;
use App\Enums\TicketStatus;
use App\Http\Resources\AdminDashboardResource;
use App\Models\KnowledgeBaseArticle;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function __invoke(): AdminDashboardResource
    {
        $summary = [
            'open_tickets' => Ticket::where('status', TicketStatus::Open)->count(),
            'in_progress_tickets' => Ticket::where('status', TicketStatus::InProgress)->count(),
            'resolved_tickets' => Ticket::where('status', TicketStatus::Resolved)->count(),
            'knowledge_articles' => KnowledgeBaseArticle::count(),
        ];

        $counts = Ticket::query()
            ->select('category', DB::raw('count(*) as total'))
            ->groupBy('category')
            ->pluck('total', 'category');
        $ticketsByCategory = collect(Category::values())->mapWithKeys(fn (string $category): array => [$category => (int) ($counts[$category] ?? 0)]);

        return new AdminDashboardResource([
            'summary' => $summary,
            'tickets_by_category' => $ticketsByCategory,
            'recent_tickets' => Ticket::with('technician')->latest('id')->limit(10)->get(),
            'recent_articles' => KnowledgeBaseArticle::latest('updated_at')->limit(10)->get(),
        ]);
    }
}
