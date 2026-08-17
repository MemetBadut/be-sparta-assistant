# Backend API MVP Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the Laravel 13 REST API for employee troubleshooting, ticket management, and admin knowledge-base operations.

**Architecture:** Use Laravel's standard controllers, form requests, API resources, Eloquent models, migrations, policies, and feature tests. Keep troubleshooting orchestration in `TroubleshootingService`, retrieval behind `KnowledgeBaseRetriever`, and optional AI generation behind `AiGuidanceGenerator`; keyword retrieval is the MVP implementation and remains replaceable by vector retrieval later.

**Tech Stack:** Laravel 13, PHP 8.3+, Laravel Sanctum, Laravel AI SDK, configured database/filesystem, PHPUnit/Pest as provided by the Laravel skeleton.

**Spec:** `docs/backend-api-prd.md`

## Global Constraints

- Employee self-registration creates the `employee` role only.
- Admin/technician accounts are seeded or admin-created; never publicly registered.
- Same-domain React client uses Sanctum session cookies and CSRF protection.
- Fixed categories: `Wi-Fi / Network`, `Windows`, `Laptop / PC`, `Printer`, `Basic Software Issues`.
- Only published articles enter employee retrieval.
- AI is optional general guidance; verified article steps remain the source of truth.
- No vectors, embeddings, chatbot, streaming, WebSockets, notifications, or live messaging in MVP.
- Employees can access only their own tickets and troubleshooting results.
- Provider credentials stay in Laravel environment configuration; React never receives them.
- Public ticket route parameters use `ticket_number`, e.g. `IT-2026-00124`, not internal numeric IDs.
- Attachments require server-side type, extension, size, and storage-path validation.
- Every non-trivial behavior gets a focused automated test.

---

## File Map

The repository currently contains only `README.md` and the approved PRD; Laravel must be scaffolded before feature tasks.

### Create during Laravel setup

- `composer.json`, `artisan`, `bootstrap/`, `config/`, `database/`, `public/`, `resources/`, `routes/`, `storage/`, `tests/` — Laravel application skeleton.

### Create or modify for the feature

- `app/Enums/Role.php`, `Category.php`, `Priority.php`, `TicketStatus.php`, `ArticleStatus.php` — backed enums/constants.
- `app/Models/User.php`, `KnowledgeBaseArticle.php`, `TroubleshootingResult.php`, `Ticket.php`, `TicketActivity.php`, `TicketAttachment.php` — persistence and relationships.
- `app/Http/Requests/Auth/*`, `Troubleshooting/*`, `Ticket/*`, `Admin/*` — boundary validation.
- `app/Http/Resources/*` — stable JSON response shapes.
- `app/Http/Controllers/AuthController.php`, `ProfileController.php`, `CategoryController.php`, `TroubleshootingController.php`, `TicketController.php`, `AttachmentController.php`, `AdminTicketController.php`, `KnowledgeBaseArticleController.php`, `AdminDashboardController.php` — HTTP entry points.
- `app/Policies/TicketPolicy.php`, `TroubleshootingResultPolicy.php`, `KnowledgeBaseArticlePolicy.php` — server-side authorization.
- `app/Services/TroubleshootingService.php`, `app/Services/KnowledgeBase/KnowledgeBaseRetriever.php`, `KeywordKnowledgeBaseRetriever.php`, `app/Services/AI/AiGuidanceGenerator.php`, `LaravelAiGuidanceGenerator.php`, `app/Services/Tickets/TicketNumberGenerator.php`, `TicketActivityService.php` — domain logic.
- `app/Providers/AppServiceProvider.php`, `config/ai.php`, `.env.example`, `routes/api.php`, `routes/web.php` — bindings, provider settings, routes, CSRF/session support.
- `database/migrations/*`, `database/factories/*`, `database/seeders/*` — schema and safe demo data.
- `tests/Feature/*`, `tests/Unit/*` — acceptance and focused unit checks.

---

## Task 1: Scaffold Laravel and baseline configuration

**Files:**
- Create: Laravel 13 application skeleton in repository root.
- Modify: `.env.example`, `README.md`.
- Test: Laravel default test command.

**Interfaces:**
- Produces a runnable Laravel 13 app with `php artisan`, `composer`, database configuration, and test runner.

- [ ] **Step 1: Write the failing environment check**

Create a smoke test that asserts the application responds to `/up` and uses the expected framework boot path.

- [ ] **Step 2: Run the check**

Run: `php artisan test --filter=ApplicationHealthTest`
Expected: FAIL because the Laravel application does not yet exist.

- [ ] **Step 3: Scaffold the minimum Laravel 13 app**

Use the Laravel 13 installer/create-project command compatible with the local PHP/Composer environment. Install Sanctum through the official Laravel package path. Do not add an API framework or repository abstraction beyond Laravel defaults.

- [ ] **Step 4: Configure same-domain API defaults**

Configure `.env.example` with database, session, Sanctum stateful domain, filesystem disk, and placeholder AI variables:

```env
APP_URL=http://localhost
SESSION_DRIVER=database
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
FILESYSTEM_DISK=local
AI_PROVIDER=proxy
GEMINI_API_KEY=
LOCAL_AI_URL=http://localhost:20128/v1
LOCAL_AI_API_KEY=
LOCAL_AI_MODEL=
```

- [ ] **Step 5: Run the health test**

Run: `php artisan test --filter=ApplicationHealthTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add .
git commit -m "chore: scaffold Laravel backend"
```

---

## Task 2: Add enums, schema, models, factories, and seed data

**Files:**
- Create: `app/Enums/*.php`, `app/Models/KnowledgeBaseArticle.php`, `TroubleshootingResult.php`, `Ticket.php`, `TicketActivity.php`, `TicketAttachment.php`.
- Modify: `app/Models/User.php`.
- Create: `database/migrations/*`, `database/factories/*`, `database/seeders/DatabaseSeeder.php`.
- Test: `tests/Feature/DatabaseSchemaTest.php`.

**Interfaces:**
- Produces Eloquent models and relationships used by all controllers/services.
- `Ticket::findByTicketNumber(string $ticketNumber): Ticket` or equivalent route-binding support.
- Models expose JSON arrays for `solution_steps`, `troubleshooting_history`, and `result_payload`.

- [ ] **Step 1: Write schema tests**

Test unique email/employee ID, ticket number uniqueness, article status, JSON casts, nullable article references, and relationships. Include a test that seeded privileged users cannot be created through registration code later.

- [ ] **Step 2: Run schema tests**

Run: `php artisan test --filter=DatabaseSchemaTest`
Expected: FAIL because migrations/models are missing.

- [ ] **Step 3: Implement enums and migrations**

Create tables with foreign keys and indexes for:

```text
users
knowledge_base_articles
troubleshooting_results
tickets
ticket_activities
ticket_attachments
```

Use nullable foreign keys for deleted article references. Add indexes on article `status/category`, ticket `ticket_number/user_id/status/category/priority`, and activity `ticket_id`.

- [ ] **Step 4: Implement models and casts**

Add relationships, guarded/fillable fields, enum casts, and JSON casts. Add Sanctum's `HasApiTokens` to `User` if the installed Laravel 13 Sanctum setup requires it.

- [ ] **Step 5: Add factories and deterministic seed data**

Seed one employee, one technician, one admin, one published article, one draft article, and a small ticket set. Use non-sensitive demo credentials documented only for local development.

- [ ] **Step 6: Run schema tests**

Run: `php artisan migrate:fresh --seed && php artisan test --filter=DatabaseSchemaTest`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app database tests
 git commit -m "feat: add helpdesk data model"
```

---

## Task 3: Implement authentication, profile, categories, and authorization foundations

**Files:**
- Create: `app/Http/Requests/Auth/RegisterRequest.php`, `LoginRequest.php`.
- Create: `app/Http/Controllers/AuthController.php`, `ProfileController.php`, `CategoryController.php`.
- Create: `app/Http/Resources/UserResource.php`, `CategoryResource.php`.
- Create: `app/Policies/*` and role middleware/enum helpers.
- Modify: `routes/api.php`, `routes/web.php`, `bootstrap/app.php` as required by Laravel 13.
- Test: `tests/Feature/AuthTest.php`, `AuthorizationTest.php`.

**Interfaces:**
- `POST /api/auth/register`, `/login`, `/logout`, `GET /api/profile`, `GET /api/categories`.
- Registration always assigns `Role::Employee`.
- Login accepts `login` as email or employee ID.

- [ ] **Step 1: Write auth and authorization tests**

Cover successful registration/login/logout, duplicate email/employee ID, failed login, profile response, unauthenticated rejection, fixed category response, and attempted privileged-role registration.

- [ ] **Step 2: Run tests**

Run: `php artisan test --filter="AuthTest|AuthorizationTest"`
Expected: FAIL.

- [ ] **Step 3: Implement validation and authentication**

Use Laravel password hashing and session authentication. Regenerate session on login, invalidate session and regenerate CSRF token on logout, and return safe user data only.

- [ ] **Step 4: Implement profile/categories**

Return `GET /api/profile` with `id,name,email,employee_id,division,role`. Return the five fixed categories as a stable array.

- [ ] **Step 5: Implement role policies/middleware**

Create a small role gate for technician/admin routes. Use policies for ownership checks rather than controller-only conditionals.

- [ ] **Step 6: Run tests**

Run: `php artisan test --filter="AuthTest|AuthorizationTest"`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app routes bootstrap tests
 git commit -m "feat: add Sanctum authentication and roles"
```

---

## Task 4: Implement keyword retrieval and troubleshooting result flow

**Files:**
- Create: `app/Services/KnowledgeBase/KnowledgeBaseRetriever.php`, `KeywordKnowledgeBaseRetriever.php`.
- Create: `app/Services/TroubleshootingService.php`.
- Create: `app/Services/AI/AiGuidanceGenerator.php`, `LaravelAiGuidanceGenerator.php`.
- Create: `app/Http/Requests/Troubleshooting/CreateTroubleshootingRequest.php`, `FeedbackRequest.php`.
- Create: `app/Http/Controllers/TroubleshootingController.php`.
- Create: `app/Http/Resources/TroubleshootingResultResource.php`.
- Modify: `app/Providers/AppServiceProvider.php`, `config/ai.php`, `.env.example`, `routes/api.php`.
- Test: `tests/Unit/KeywordKnowledgeBaseRetrieverTest.php`, `tests/Feature/TroubleshootingTest.php`.

**Interfaces:**

```php
interface KnowledgeBaseRetriever
{
    /** @return list<KnowledgeBaseArticle> */
    public function retrieve(string $category, string $description, int $limit = 3): array;
}

interface AiGuidanceGenerator
{
    /** @return array{guidance:string,recommend_ticket:bool} */
    public function generate(string $category, string $description, array $context): array;
}
```

`TroubleshootingService::create(User $user, string $category, string $description): TroubleshootingResult` persists and returns the stable result contract.

- [ ] **Step 1: Write retrieval unit tests**

Test category filtering, published-only filtering, keyword/title/symptom matching, deterministic ordering, limit of three, and no-match behavior. Assert draft articles never appear.

- [ ] **Step 2: Run retrieval tests**

Run: `php artisan test --filter=KeywordKnowledgeBaseRetrieverTest`
Expected: FAIL.

- [ ] **Step 3: Implement the keyword retriever**

Normalize case and punctuation, tokenize the employee description, score matches against title/symptoms/keywords/problem description, filter by selected category and `Published`, sort deterministically by score then ID, and return no more than three articles. Keep the algorithm local and boring; no embedding package.

- [ ] **Step 4: Write troubleshooting feature tests**

Cover verified article response, no-match AI fallback, AI failure safe response, persisted result ID, employee-only ownership, and feedback idempotency.

- [ ] **Step 5: Run feature tests**

Run: `php artisan test --filter=TroubleshootingTest`
Expected: FAIL.

- [ ] **Step 6: Configure Laravel AI SDK providers**

Install/configure `laravel/ai` only if not already included by the Laravel 13 skeleton. Map `AI_PROVIDER` to Gemini or OpenAI-compatible proxy. Keep provider calls inside `LaravelAiGuidanceGenerator`; return a controlled exception/result on provider failure. Do not pass the full KB—only selected article context or no context for general guidance.

- [ ] **Step 7: Implement service/controller/resource**

`POST /api/troubleshooting` validates input, invokes the service, persists `result_payload`, and returns `TroubleshootingResultResource`. `POST /api/troubleshooting/{id}/feedback` authorizes ownership and updates the single feedback field idempotently.

- [ ] **Step 8: Run tests**

Run: `php artisan test --filter="KeywordKnowledgeBaseRetrieverTest|TroubleshootingTest"`
Expected: PASS.

- [ ] **Step 9: Commit**

```bash
git add app config routes tests composer.json composer.lock
 git commit -m "feat: add keyword RAG troubleshooting"
```

---

## Task 5: Implement employee tickets and secure attachments

**Files:**
- Create: `app/Http/Requests/Ticket/CreateTicketRequest.php`, `UploadTicketAttachmentRequest.php`.
- Create: `app/Http/Controllers/TicketController.php`, `AttachmentController.php`.
- Create: `app/Http/Resources/TicketResource.php`, `TicketAttachmentResource.php`.
- Create: `app/Services/Tickets/TicketNumberGenerator.php`.
- Modify: `app/Models/Ticket.php`, `routes/api.php`.
- Test: `tests/Feature/EmployeeTicketTest.php`, `AttachmentTest.php`.

**Interfaces:**
- `GET /api/tickets` returns current employee tickets only.
- `POST /api/tickets` creates `Open` ticket.
- `GET /api/tickets/{ticket}` binds by `ticket_number`.
- `POST /api/tickets/{ticket}/attachments` accepts multipart upload.

- [ ] **Step 1: Write ticket/attachment tests**

Cover ticket creation, generated unique public number, required fields, device-code rule, carried-forward history from owned troubleshooting result, rejection of forged history where appropriate, own-ticket listing/detail, cross-employee 404/authorization behavior, invalid extension/MIME/size, and safe attachment metadata.

- [ ] **Step 2: Run tests**

Run: `php artisan test --filter="EmployeeTicketTest|AttachmentTest"`
Expected: FAIL.

- [ ] **Step 3: Implement ticket number generation and binding**

Generate `IT-{year}-{sequence}` under a transaction with a uniqueness constraint. Configure `Ticket::getRouteKeyName()` to return `ticket_number`, or use explicit binding. Handle collisions by retrying the transaction.

- [ ] **Step 4: Implement ticket validation and creation**

Use enum rules. Require `device_code` for `Laptop / PC` and `Printer`. If `troubleshooting_result_id` is present, authorize ownership and derive history/result context server-side. Ignore client attempts to set status, assignee, resolution notes, or internal IDs.

- [ ] **Step 5: Implement ownership-scoped listing/detail**

Query tickets through the authenticated user relationship. Use policy checks on detail and attachment routes. Do not reveal whether another employee's ticket exists.

- [ ] **Step 6: Implement upload handling**

Accept configured safe image/document types and maximum size. Generate storage names; store via Laravel filesystem; persist only metadata. Never return raw disk paths. Use a private disk unless product explicitly requires public URLs.

- [ ] **Step 7: Run tests**

Run: `php artisan test --filter="EmployeeTicketTest|AttachmentTest"`
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add app routes tests
 git commit -m "feat: add employee ticket submission and attachments"
```

---

## Task 6: Implement admin ticket operations and activity history

**Files:**
- Create: `app/Http/Requests/Admin/UpdateTicketRequest.php`.
- Create: `app/Http/Controllers/AdminTicketController.php`.
- Create: `app/Http/Resources/TicketActivityResource.php`.
- Create: `app/Services/Tickets/TicketActivityService.php`.
- Modify: `app/Policies/TicketPolicy.php`, `routes/api.php`.
- Test: `tests/Feature/AdminTicketTest.php`.

**Interfaces:**
- `GET /api/admin/tickets` with `search,category,status,priority,page` filters.
- `GET /api/admin/tickets/{ticket}`.
- `PATCH /api/admin/tickets/{ticket}` for assignment/status/resolution notes.
- `GET /api/admin/tickets/{ticket}/activities`.

- [ ] **Step 1: Write admin ticket tests**

Cover employee rejection, technician/admin access, all filters, assignment, valid status transitions, invalid backward/skipped transitions, resolution notes, activity rows for every changed field, and cross-request visibility.

- [ ] **Step 2: Run tests**

Run: `php artisan test --filter=AdminTicketTest`
Expected: FAIL.

- [ ] **Step 3: Implement admin authorization and filters**

Use role middleware plus `TicketPolicy`. Build filters with conditional query clauses and pagination. Do not add a generic query-builder abstraction.

- [ ] **Step 4: Implement transition/activity service**

Validate `Open → In Progress → Resolved → Closed`. Save the ticket and activity record atomically. Record old/new status, actor, type, and note for assignment/status/resolution changes.

- [ ] **Step 5: Implement controllers/resources and routes**

Return stable ticket/activity JSON. Restrict update fields using a dedicated request and explicit mapping.

- [ ] **Step 6: Run tests**

Run: `php artisan test --filter=AdminTicketTest`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app routes tests
 git commit -m "feat: add admin ticket management"
```

---

## Task 7: Implement admin knowledge-base CRUD

**Files:**
- Create: `app/Http/Requests/Admin/CreateArticleRequest.php`, `UpdateArticleRequest.php`.
- Create: `app/Http/Controllers/KnowledgeBaseArticleController.php`.
- Create: `app/Http/Resources/KnowledgeBaseArticleResource.php`.
- Modify: `app/Policies/KnowledgeBaseArticlePolicy.php`, `routes/api.php`.
- Test: `tests/Feature/AdminKnowledgeBaseTest.php`.

**Interfaces:**
- `GET/POST/GET/PATCH/DELETE /api/admin/articles` and `/api/admin/articles/{article}`.
- Filters: `search,category,status,page`.

- [ ] **Step 1: Write CRUD tests**

Cover employee rejection, technician/admin access, create/update validation, draft/published states, search/filter behavior, updated-by tracking, delete confirmation as an API contract, and nullable ticket/article history after deletion.

- [ ] **Step 2: Run tests**

Run: `php artisan test --filter=AdminKnowledgeBaseTest`
Expected: FAIL.

- [ ] **Step 3: Implement requests, policy, controller, resource**

Validate ordered non-empty solution steps, fixed category, article state, and required fields. Use explicit field mapping. Ensure deletion does not cascade away ticket history.

- [ ] **Step 4: Run tests**

Run: `php artisan test --filter=AdminKnowledgeBaseTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app routes tests
 git commit -m "feat: add knowledge base management"
```

---

## Task 8: Implement admin dashboard and API error/rate-limit behavior

**Files:**
- Create: `app/Http/Controllers/AdminDashboardController.php`.
- Create: `app/Http/Resources/AdminDashboardResource.php`.
- Modify: `bootstrap/app.php`, `routes/api.php`, `app/Providers/AppServiceProvider.php`.
- Test: `tests/Feature/AdminDashboardTest.php`, `ErrorContractTest.php`.

**Interfaces:**
- `GET /api/admin/dashboard` returns summary counts, recent tickets, category counts, recent article updates.

- [ ] **Step 1: Write tests**

Cover dashboard authorization/data, 401/403/404/422 JSON shapes, upload 413 behavior if framework mapping is needed, and route throttles for login/registration/troubleshooting/uploads.

- [ ] **Step 2: Run tests**

Run: `php artisan test --filter="AdminDashboardTest|ErrorContractTest"`
Expected: FAIL.

- [ ] **Step 3: Implement dashboard queries**

Use direct aggregate queries and bounded recent lists. Avoid advanced analytics or chart-specific backend logic.

- [ ] **Step 4: Configure JSON exception rendering and rate limits**

Ensure API validation/auth/authorization/not-found errors match the PRD's JSON contract. Register named limiters with conservative local defaults and document production tuning in `.env.example`.

- [ ] **Step 5: Run tests**

Run: `php artisan test --filter="AdminDashboardTest|ErrorContractTest"`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app bootstrap routes tests
 git commit -m "feat: add admin dashboard and API contracts"
```

---

## Task 9: End-to-end verification and documentation

**Files:**
- Modify: `README.md`, `.env.example`.
- Test: `tests/Feature/ApiAcceptanceTest.php`.

**Interfaces:**
- Documents install, migrate/seed, local auth flow, AI provider selection, file storage, and complete API route list.

- [ ] **Step 1: Write the acceptance test**

Exercise one full flow: register → profile → create troubleshooting result from a published article → submit feedback → create ticket with carried-forward history → upload valid attachment → admin logs in/updates ticket → employee reads own ticket. Include negative checks for draft retrieval and cross-employee ticket access.

- [ ] **Step 2: Run the acceptance test**

Run: `php artisan test --filter=ApiAcceptanceTest`
Expected: FAIL until all prior tasks are integrated.

- [ ] **Step 3: Document setup and provider configuration**

Include exact commands:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
php artisan test
```

Document that free-tier AI testing uses synthetic data only until company privacy approval. Document that `LOCAL_AI_URL=http://localhost:20128/v1` requires Laravel and 9Router on the same host; otherwise use a reachable private network address.

- [ ] **Step 4: Run all checks**

Run:

```bash
php artisan migrate:fresh --seed
php artisan test
php artisan route:list --path=api
```

Expected: all tests PASS; route list contains every PRD endpoint and no chatbot/WebSocket route.

- [ ] **Step 5: Verify runtime manually**

Use a same-domain browser or HTTP client to confirm Sanctum CSRF/login, profile, troubleshooting, ticket creation, upload rejection, and admin update behavior. Confirm no provider key appears in response bodies or browser network requests.

- [ ] **Step 6: Commit**

```bash
git add README.md .env.example tests
 git commit -m "docs: document and verify backend MVP"
```

---

## Self-review checklist

- Authentication and `/api/profile`: Tasks 1–3.
- Fixed categories: Tasks 2–3.
- Keyword-only RAG and provider fallback: Task 4.
- Ticket ownership, public ticket numbers, and attachments: Task 5.
- Admin ticket lifecycle/activity: Task 6.
- Published/draft KB CRUD: Task 7.
- Dashboard/error/rate limits: Task 8.
- Security and end-to-end acceptance: Tasks 3, 5, 8, 9.
- Future vector-RAG seam: Task 4 interface; no vector infrastructure added.
- No placeholders or speculative features: checked; all steps name files, behavior, commands, and expected outcomes.
