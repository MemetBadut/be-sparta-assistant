<?php

namespace App\Models;

use App\Enums\Category;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TroubleshootingResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'category', 'description', 'source',
        'selected_article_id', 'result_payload', 'helpful',
    ];

    protected function casts(): array
    {
        return [
            'category' => Category::class,
            'result_payload' => 'array',
            'helpful' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<KnowledgeBaseArticle, $this> */
    public function article(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBaseArticle::class, 'selected_article_id');
    }
}
