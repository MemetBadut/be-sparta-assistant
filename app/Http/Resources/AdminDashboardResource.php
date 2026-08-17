<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminDashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'summary' => $this->resource['summary'],
            'tickets_by_category' => $this->resource['tickets_by_category'],
            'recent_tickets' => TicketResource::collection($this->resource['recent_tickets']),
            'recent_articles' => KnowledgeBaseArticleResource::collection($this->resource['recent_articles']),
        ];
    }
}
