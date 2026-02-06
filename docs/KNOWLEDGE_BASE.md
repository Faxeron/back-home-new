# Knowledge Base (База знаний)

Overview
- Внутренняя база знаний для менеджеров (статьи, теги, темы, вложения).
- Доступ: авторизованные пользователи в tenant/company.
- UI: `/sales/knowledge`.

Data model (legacy_new)
- `knowledge_articles`, `knowledge_topics`, `knowledge_tags`.
- Пивоты: `knowledge_article_topics`, `knowledge_article_tags`.
- `knowledge_attachments` — файлы/ссылки.

Topics (types)
- `brand`, `product`, `category`, `process`, `installation`, `warranty`, `claim`, `algorithm`, `other`.

Attachments
- Types: `file`, `link`, `video`.
- Files: `storage/app/public/knowledge-base/tenant_{id}/company_{id}/article_{id}/...`.
- Download: `/api/knowledge/attachments/{id}/download`.

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
- `POST /api/knowledge/articles/{id}/attachments`
- `DELETE /api/knowledge/attachments/{id}`
- `GET /api/knowledge/attachments/{id}/download`

Frontend module
- Module root: `resources/ts/modules/knowledge`
- Wrapper: `resources/ts/pages/sales/knowledge/index.vue`

## REALITY STATUS
- Реально реализовано: статьи/темы/теги/вложения, upload в public disk.
- Легаси: отсутствует публичный доступ, только внутренний контур.
- Не сделано: расширенный поиск по связям и метаданным вложений.
