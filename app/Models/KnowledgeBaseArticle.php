<?php

namespace App\Models;

use App\Enums\ArticleStatus;
use App\Enums\Category;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeBaseArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'category', 'symptoms', 'keywords', 'problem_description',
        'solution_steps', 'expected_result', 'status', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'category' => Category::class,
            'solution_steps' => 'array',
            'status' => ArticleStatus::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** @return HasMany<TroubleshootingResult, $this> */
    public function troubleshootingResults(): HasMany
    {
        return $this->hasMany(TroubleshootingResult::class, 'selected_article_id');
    }
}
