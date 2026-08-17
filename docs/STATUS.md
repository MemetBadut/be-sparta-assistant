# Backend Status — Session Handoff

**Last updated:** 2026-08-17
**Branch:** `feature/backend-api-mvp` (not pushed, not merged)
**Suite:** 34 tests / 107 assertions — all passing (`php artisan test`)

## Where we are

The full backend MVP from `docs/backend-api-prd.md` is implemented and committed
in 8 chunks. All PRD endpoints exist (21 routes). The next phase is **bugfixing
against the real React frontend**, not new features.

### Commit map

| Commit | Contents |
|---|---|
| `a9423e7` | Laravel 13 scaffold, Sanctum, laravel/ai, composer.phar |
| `465ecd2` | Enums, models, migrations, factories, seeder |
| `3e945d2` | Auth (register/login/logout/profile), categories, role middleware |
| `d6a5d95` | Keyword retriever, AI guidance adapter, troubleshooting endpoints |
| `feddd26` | Employee tickets, ticket numbers, attachments |
| `be43fd0` | Admin tickets/activity, KB CRUD, dashboard, policies |
| `bfd80b9` | All feature/unit tests, README |
| `8e7fab2` + `a5bc5af` | Base controller, database ignore, PRD + plan docs |

## Key decisions locked earlier (don't re-litigate)

- Laravel 13 + Sanctum **session cookies**, same domain as React.
- Employee self-registration; admin/technician seeded only (`Role` enum).
- Keyword RAG now; `KnowledgeBaseRetriever` is the seam for vector RAG later.
- AI = optional general-guidance fallback only, behind `AiGuidanceGenerator`.
- Public ticket IDs are `IT-{year}-{00001}` strings, not numeric IDs.
- Fixed five categories (enum), no category CRUD.
- Status flow `Open → In Progress → Resolved → Closed` enforced in
  `TicketActivityService`; every change writes a `ticket_activities` row.

## Known issues / things to verify in a bugfix session

1. **AI fallback never exercised live.** Tests only hit the safe fallback path
   (no provider configured). Set real `LOCAL_AI_*` or `GEMINI_API_KEY` in
   `.env` and confirm `LaravelAiGuidanceGenerator` returns real text; its
   `agent()->prompt(...)` call signature against laravel/ai v0.10 is unverified.
2. **Manual browser Sanctum flow untested.** `php artisan serve` + same-origin
   client: `/sanctum/csrf-cookie` → login → authenticated GET. CSRF is
   enforced on `/api/auth/*` (they run through `web` middleware).
3. **Response envelope inconsistency.** `GET /api/profile` and
   `POST /api/tickets/{...}/attachments` return the object at top level;
   most other resources wrap in `data`. Pick one convention before the
   React client hardcodes paths.
4. **`troubleshooting_history` client override.** `CreateTicketRequest`
   accepts a `troubleshooting_history` array but `TicketController@store`
   ignores it and derives history from the stored `result_payload`. Either
   drop the field from the request or merge client additions intentionally.
5. **`UpdateArticleRequest` strips all `required` rules** via array filter —
   optional-partial PATCH semantics. If the frontend expects full-object
   PUT-like behavior, tighten this.
6. **Route binding duplication.** `Route::model('ticket', ...)` and
   `Route::bind('ticket', ...)` both exist in `routes/api.php`; only the bind
   is effective (ticket_number lookup). Harmless but delete the dead line.
7. **`TicketResource.resolution_notes` visibility rule**: employees see notes
   only when status is Resolved/Closed; admins always. Confirm PRD intent.
8. **Attachment download/serve endpoint missing.** Upload works, metadata
   returns, but there is no `GET` to download an attachment. PRD lists
   "View attachments" for admins — needs a decision (signed URL vs stream).
9. **`AdminTicketController::show`** loads `user` relation but
   `TicketResource` never exposes the employee's name/email beyond the
   denormalized `name`/`division` ticket columns — verify admin list shows
   enough.
10. **No pagination metadata contract check** — frontend should confirm
    Laravel's default `links`/`meta` shape is acceptable.

## How to run

```bash
php composer.phar install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan test          # 34 passing
php artisan serve
```

Seeded logins (local only, password `password`):
`employee@example.com`, `technician@example.com`, `admin@example.com`.

## Docs index

- `docs/backend-api-prd.md` — approved spec (contracts, rules)
- `docs/superpowers/plans/2026-08-17-backend-api-mvp.md` — task-by-task plan
- `docs/STATUS.md` — this file
- `README.md` — setup, routes, provider config
