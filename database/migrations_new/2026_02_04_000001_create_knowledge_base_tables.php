<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('legacy_new')->create('knowledge_articles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('company_id');
            $table->string('title', 255);
            $table->longText('body');
            $table->boolean('is_published')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'company_id'], 'knowledge_articles_scope_idx');
            $table->index(['company_id', 'updated_at'], 'knowledge_articles_company_updated_idx');
        });

        Schema::connection('legacy_new')->create('knowledge_topics', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('company_id');
            $table->string('type', 40);
            $table->string('name', 255);
            $table->string('reference_type', 80)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'company_id', 'type', 'name'], 'knowledge_topics_unique');
            $table->index(['tenant_id', 'company_id', 'type'], 'knowledge_topics_scope_idx');
        });

        Schema::connection('legacy_new')->create('knowledge_tags', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('company_id');
            $table->string('name', 80);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'company_id', 'name'], 'knowledge_tags_unique');
            $table->index(['tenant_id', 'company_id'], 'knowledge_tags_scope_idx');
        });

        Schema::connection('legacy_new')->create('knowledge_article_topics', function (Blueprint $table): void {
            $table->unsignedBigInteger('article_id');
            $table->unsignedBigInteger('topic_id');

            $table->primary(['article_id', 'topic_id'], 'knowledge_article_topics_pk');
            $table->index(['topic_id'], 'knowledge_article_topics_topic_idx');

            $table->foreign('article_id', 'knowledge_article_topics_article_fk')
                ->references('id')
                ->on('knowledge_articles')
                ->onDelete('cascade');
            $table->foreign('topic_id', 'knowledge_article_topics_topic_fk')
                ->references('id')
                ->on('knowledge_topics')
                ->onDelete('cascade');
        });

        Schema::connection('legacy_new')->create('knowledge_article_tags', function (Blueprint $table): void {
            $table->unsignedBigInteger('article_id');
            $table->unsignedBigInteger('tag_id');

            $table->primary(['article_id', 'tag_id'], 'knowledge_article_tags_pk');
            $table->index(['tag_id'], 'knowledge_article_tags_tag_idx');

            $table->foreign('article_id', 'knowledge_article_tags_article_fk')
                ->references('id')
                ->on('knowledge_articles')
                ->onDelete('cascade');
            $table->foreign('tag_id', 'knowledge_article_tags_tag_fk')
                ->references('id')
                ->on('knowledge_tags')
                ->onDelete('cascade');
        });

        Schema::connection('legacy_new')->create('knowledge_attachments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('article_id');
            $table->string('type', 20)->default('file');
            $table->string('title', 255)->nullable();
            $table->text('url')->nullable();
            $table->string('file_path', 512)->nullable();
            $table->string('original_name', 255)->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->longText('search_text')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['article_id'], 'knowledge_attachments_article_idx');
            $table->index(['tenant_id', 'company_id'], 'knowledge_attachments_scope_idx');

            $table->foreign('article_id', 'knowledge_attachments_article_fk')
                ->references('id')
                ->on('knowledge_articles')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('legacy_new')->dropIfExists('knowledge_attachments');
        Schema::connection('legacy_new')->dropIfExists('knowledge_article_tags');
        Schema::connection('legacy_new')->dropIfExists('knowledge_article_topics');
        Schema::connection('legacy_new')->dropIfExists('knowledge_tags');
        Schema::connection('legacy_new')->dropIfExists('knowledge_topics');
        Schema::connection('legacy_new')->dropIfExists('knowledge_articles');
    }
};
