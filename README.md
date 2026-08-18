# IT Helpdesk Assistant — Laravel Backend

Laravel 13 REST API for the employee and admin React applications.

> **Current state (2026-08-17):** MVP implemented on branch `feature/backend-api-mvp`,
> 34 tests passing. See `docs/STATUS.md` for the handoff notes, commit map, and the
> list of known issues to bugfix next.

## Requirements

- PHP 8.3+
- Composer (or the project-local `composer.phar`)
- SQLite/MySQL/PostgreSQL

## Local setup

```bash
php composer.phar install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Run checks:

```bash
php artisan test
php artisan route:list --path=api
```

Seeded local accounts all use `password`:

```text
employee@example.com / password
admin@example.com / password
```

Do not use seeded credentials outside local development.

## Authentication

The API uses same-domain Sanctum session cookies. A React client should call `/sanctum/csrf-cookie` before login, then use credentials on requests:

```text
POST /api/auth/register
POST /api/auth/login
POST /api/auth/logout
GET  /api/profile
```

Public registration always creates an employee. Admin accounts are seeded or created by authorized administrators.

## Main routes

```text
GET  /api/categories
POST /api/troubleshooting
POST /api/troubleshooting/{id}/feedback
GET  /api/tickets
POST /api/tickets
GET  /api/tickets/{ticketNumber}
POST /api/tickets/{ticketNumber}/attachments

GET   /api/admin/dashboard
GET   /api/admin/tickets
GET   /api/admin/tickets/{ticket}
PATCH /api/admin/tickets/{ticket}
GET   /api/admin/tickets/{ticket}/activities

GET    /api/admin/articles
POST   /api/admin/articles
GET    /api/admin/articles/{article}
PATCH  /api/admin/articles/{article}
DELETE /api/admin/articles/{article}?confirm=1
```

`{ticketNumber}` is the public ticket number, such as `IT-2026-00124`. Employee ticket queries are ownership-scoped.

## Troubleshooting and RAG

MVP retrieval is deterministic keyword matching over published articles. The backend passes selected verified article content to the result response. Draft articles never enter employee retrieval.

AI is only an optional fallback for general guidance when no strong article match exists. AI output is untrusted and clearly labeled; it is never company policy. If the provider is unavailable, the API recommends creating a ticket.

Retrieval lives behind `KnowledgeBaseRetriever`, so a future vector implementation can replace keyword retrieval without changing the API contract. MVP intentionally has no embeddings or vector database.

## AI provider configuration

Credentials stay in Laravel `.env`; React never receives them.

### 9Router / OpenAI-compatible proxy

```env
AI_PROVIDER=proxy
LOCAL_AI_URL=http://localhost:20128/v1
LOCAL_AI_API_KEY=your-server-side-token
LOCAL_AI_MODEL=your-9router-model-alias
```

`localhost` works only when Laravel and 9Router run on the same machine. Otherwise use a reachable private network address. Do not expose 9Router publicly without authentication and network controls.

### Gemini

```env
AI_PROVIDER=gemini
GEMINI_API_KEY=your-server-side-key
```

Use synthetic/non-sensitive issue data with free-tier providers until company privacy approval. Never commit API keys.

## Security boundaries

- Backend authorization protects employee data; frontend route hiding is not security.
- Attachments are type/size validated and stored through Laravel's filesystem abstraction.
- API keys, passwords, uploaded contents, and unnecessary sensitive issue text must not be logged.
- AI output is treated as untrusted text by the frontend.
