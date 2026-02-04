<?php

namespace App\Domain\Knowledge\Models;

use App\Domain\Common\Traits\BelongsToCompany;
use App\Domain\Common\Traits\BelongsToTenant;
use App\Domain\Common\Traits\HasCreator;
use App\Domain\Common\Traits\HasUpdater;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeArticle extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use BelongsToCompany;
    use HasCreator;
    use HasUpdater;

    protected $connection = 'legacy_new';

    protected $table = 'knowledge_articles';

    protected $guarded = [];

    protected $casts = [
        'is_published' => 'bool',
        'published_at' => 'datetime',
    ];

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            KnowledgeTag::class,
            'knowledge_article_tags',
            'article_id',
            'tag_id',
        );
    }

    public function topics(): BelongsToMany
    {
        return $this->belongsToMany(
            KnowledgeTopic::class,
            'knowledge_article_topics',
            'article_id',
            'topic_id',
        );
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(KnowledgeAttachment::class, 'article_id')
            ->orderByDesc('id');
    }
}
