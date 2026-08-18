<?php

namespace App\Models;

use App\Enums\Category;
use App\Enums\Priority;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number', 'user_id', 'name', 'division', 'issue_title', 'description',
        'category', 'device_code', 'priority', 'status', 'assigned_technician', 'repair_required',
        'kb_article_id', 'troubleshooting_result_id', 'troubleshooting_history', 'resolution_notes',
    ];

    public function getRouteKeyName(): string { return 'ticket_number'; }

    protected function casts(): array
    {
        return [
            'category' => Category::class,
            'priority' => Priority::class,
            'status' => TicketStatus::class,
            'repair_required' => 'boolean',
            'troubleshooting_history' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    /** @return BelongsTo<KnowledgeBaseArticle, $this> */
    public function article(): BelongsTo { return $this->belongsTo(KnowledgeBaseArticle::class, 'kb_article_id'); }

    /** @return BelongsTo<TroubleshootingResult, $this> */
    public function troubleshootingResult(): BelongsTo { return $this->belongsTo(TroubleshootingResult::class); }

    /** @return HasMany<TicketActivity, $this> */
    public function activities(): HasMany { return $this->hasMany(TicketActivity::class)->latest('id'); }

    /** @return HasMany<TicketAttachment, $this> */
    public function attachments(): HasMany { return $this->hasMany(TicketAttachment::class)->latest('id'); }
}
