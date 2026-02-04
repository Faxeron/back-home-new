# Knowledge Base (База знаний)

Overview
- Module purpose: internal knowledge base for managers (articles, attachments, links).
- Access: all authenticated users within tenant/company.
- UI entry: `Продажи → База знаний` (`/sales/knowledge`).

Data model (legacy_new)
- `knowledge_articles`: article header + HTML body.
- `knowledge_topics`: topic dictionary (type + name).
- `knowledge_tags`: tag dictionary.
- `knowledge_article_topics`: article ↔ topic pivot.
- `knowledge_article_tags`: article ↔ tag pivot.
- `knowledge_attachments`: files/links attached to articles.

Topics (types)
- `brand`, `product`, `category`, `process`, `installation`, `warranty`, `claim`, `algorithm`, `other`.
- Stored in `knowledge_topics.type`, grouped in UI.

Attachments
- Types: `file`, `link`, `video`.
- Files stored on disk: `storage/app/public/knowledge-base/tenant_{id}/company_{id}/article_{id}/...`
- Download endpoint: `/api/knowledge/attachments/{id}/download`.

API (auth:sanctum, tenant.company)
- `GET /api/knowledge/articles` (filters: `q`, `tag_ids`, `topic_ids`, `topic_type`, `published`)
- `POST /api/knowledge/articles`
- `GET /api/knowledge/articles/{id}`
- `PATCH /api/knowledge/articles/{id}`
- `DELETE /api/knowledge/articles/{id}`
- `GET /api/knowledge/tags`
- `POST /api/knowledge/tags`
- `GET /api/knowledge/topics`
- `POST /api/knowledge/topics`
- `POST /api/knowledge/articles/{id}/attachments` (multipart for files)
- `DELETE /api/knowledge/attachments/{id}`
- `GET /api/knowledge/attachments/{id}/download`

Search
- Server-side search runs over article title/body, tags, topics, and attachment metadata.

Frontend module
- Module root: `resources/ts/modules/knowledge`
- Main UI: `resources/ts/modules/knowledge/pages/knowledge/index.vue`
- Public wrapper: `resources/ts/pages/sales/knowledge/index.vue`
