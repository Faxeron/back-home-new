<?php

namespace App\Domain\Knowledge\Models;

use App\Domain\Common\Traits\BelongsToCompany;
use App\Domain\Common\Traits\BelongsToTenant;
use App\Domain\Common\Traits\HasCreator;
use App\Domain\Common\Traits\HasUpdater;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class KnowledgeTag extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use BelongsToCompany;
    use HasCreator;
    use HasUpdater;

    protected $connection = 'legacy_new';

    protected $table = 'knowledge_tags';

    protected $guarded = [];

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(
            KnowledgeArticle::class,
            'knowledge_article_tags',
            'tag_id',
            'article_id',
        );
    }
}
