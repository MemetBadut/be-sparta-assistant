# IT Helpdesk Assistant — Backend API PRD

**Status:** Approved design for review
**Scope:** Laravel 13 backend MVP
**Frontend:** React employee and admin surfaces, same domain

## 1. Decisions

- Laravel 13 REST API.
- Laravel Sanctum session-cookie authentication.
- Employee self-registration.
- Admin and technician accounts are seeded or created by an authorized admin; never public registration.
- Fixed categories:
  - Wi-Fi / Network
  - Windows
  - Laptop / PC
  - Printer
  - Basic Software Issues
- Laravel AI SDK for provider-neutral AI calls.
- AI provider is configuration-driven: Gemini or an OpenAI-compatible 9Router endpoint.
- Keyword-based knowledge-base retrieval for MVP.
- Only published articles can be retrieved for employee troubleshooting.
- No vector database, embeddings, chatbot, streaming, WebSockets, notifications, or live messaging in MVP.
- Retrieval is isolated behind a service so vector retrieval can replace it later.
- Employees can access only their own tickets.
- Admin/technician authorization is enforced server-side.

## 2. Runtime flow

```text
React
  → Laravel API
    → auth / authorization / validation
      → domain service
        → database
        → optional AI provider
```

The browser never calls Gemini, 9Router, or another model provider directly. Provider credentials stay in Laravel environment configuration.

## 3. Authentication

### Public routes

```text
POST /api/auth/register
POST /api/auth/login
```

Registration creates an `employee` role only. Required fields:

```json
{
  "name": "Alex Tan",
  "email": "alex@example.com",
  "employee_id": "EMP-001",
  "division": "Operations",
  "password": "...",
  "password_confirmation": "..."
}
```

Email and employee ID are unique. Successful registration may authenticate the new employee immediately.

Login accepts either email or employee ID plus password:

```json
{
  "login": "EMP-001",
  "password": "..."
}
```

### Authenticated routes

```text
POST /api/auth/logout
GET  /api/profile
```

`GET /api/profile` returns the authenticated user's safe profile fields. Never return the password hash or sensitive authentication data.

### Authorization

- `employee`: own profile, troubleshooting, own tickets.
- `technician` / `admin`: all ticket operations and knowledge-base operations.
- Public registration cannot assign a privileged role.
- Backend policies/middleware enforce access; hiding frontend routes is insufficient.

## 4. Categories

```text
GET /api/categories
```

Returns the fixed category list. No category CRUD exists in MVP.

## 5. Troubleshooting

### Create result

```text
POST /api/troubleshooting
```

Request:

```json
{
  "category": "Wi-Fi / Network",
  "description": "Wi-Fi is connected but there is no internet"
}
```

Validation:

- `category` required and one of the fixed values.
- `description` required, bounded length, trimmed.

Processing:

1. Validate the authenticated employee request.
2. Retrieve published articles in the selected category.
3. Score title, symptoms, keywords, problem description, and issue description with deterministic keyword matching.
4. Select the best one to three matches above the configured confidence threshold.
5. If a strong match exists, return verified article content. AI may format/explain it but must not alter the steps or present invented policy.
6. If no strong match exists, optionally call the configured AI provider for clearly labeled general guidance.
7. If the provider fails, return a safe ticket recommendation instead of failing the core flow.
8. Persist the troubleshooting request/result and selected article references needed for feedback and ticket carry-forward.

Response shape:

```json
{
  "id": 42,
  "category": "Wi-Fi / Network",
  "issue_summary": "Wi-Fi is connected but there is no internet",
  "source": "verified_knowledge_base",
  "article": {
    "id": 12,
    "title": "Laptop connected to Wi-Fi but no internet",
    "steps": [
      "Reconnect to Wi-Fi.",
      "Restart the network connection."
    ],
    "expected_result": "Internet access is restored."
  },
  "general_guidance": null,
  "recommend_ticket": false
}
```

Allowed `source` values:

- `verified_knowledge_base`
- `general_guidance`
- `no_guidance`

`general_guidance` is never presented as a verified company solution.

### Feedback

```text
POST /api/troubleshooting/{id}/feedback
```

`{id}` is the troubleshooting result ID, not the article ID.

Request:

```json
{
  "helpful": true
}
```

Only the employee who created the result may submit its feedback. Feedback is idempotent for that employee/result pair.

## 6. Tickets

Ticket numbers are public identifiers, for example `IT-2026-00124`. Route parameters named `{ticket}` represent this ticket number, not the internal numeric database ID.

### Employee routes

```text
GET  /api/tickets
POST /api/tickets
GET  /api/tickets/{ticket}
POST /api/tickets/{ticket}/attachments
```

`GET /api/tickets` returns only the authenticated employee's tickets.

Ticket creation accepts:

```json
{
  "name": "Alex Tan",
  "division": "Operations",
  "issue_title": "Wi-Fi connected but no internet",
  "description": "The laptop connects to Wi-Fi but websites do not load.",
  "category": "Wi-Fi / Network",
  "device_code": null,
  "priority": "Medium",
  "troubleshooting_result_id": 42,
  "troubleshooting_history": [
    "Reconnect to Wi-Fi.",
    "Restart the network connection."
  ]
}
```

Rules:

- Name, division, issue title, description, category, and priority are required.
- Device code is required for `Laptop / PC` and `Printer`; optional/hidden otherwise.
- `priority`: `Low`, `Medium`, or `High`.
- New tickets receive a unique ticket number and `Open` status.
- The server derives or verifies carried-forward troubleshooting history; the employee must not need to retype it.
- Employees cannot set status, assignee, resolution notes, or privileged fields.

### Attachments

```text
POST /api/tickets/{ticket}/attachments
```

Use multipart upload. Validate MIME type, extension, and maximum size at the backend boundary. Store files through Laravel's filesystem abstraction. Do not trust the client-provided filename or MIME type. Return attachment metadata, not filesystem internals.

### Admin ticket routes

```text
GET   /api/admin/tickets
GET   /api/admin/tickets/{ticket}
PATCH /api/admin/tickets/{ticket}
GET   /api/admin/tickets/{ticket}/activities
```

List filters:

```text
search, category, status, priority, page
```

Admin updates may change:

- `assigned_technician_id`
- `status`
- `resolution_notes`

Valid status flow:

```text
Open → In Progress → Resolved → Closed
```

Reject backward or skipped transitions unless explicitly added to the approved business rules. Every assignment, status change, and resolution-note change creates a ticket activity record.

Employees may view resolution notes only when returned by the employee ticket policy and only for their own ticket.

## 7. Knowledge base

### Admin routes

```text
GET    /api/admin/articles
POST   /api/admin/articles
GET    /api/admin/articles/{article}
PATCH  /api/admin/articles/{article}
DELETE /api/admin/articles/{article}
```

List filters:

```text
search, category, status, page
```

Article fields:

```text
title, category, symptoms, keywords, problem_description,
solution_steps, expected_result, status
```

`status` is `Draft` or `Published`.

Rules:

- Only authorized admin/technician users can manage articles.
- Only `Published` articles enter employee retrieval.
- Draft content must never reach employee troubleshooting responses.
- Delete requires a confirmed frontend action and backend authorization.
- Article changes record `updated_by`.
- Article deletion must preserve ticket history; nullable article references are preferred.

## 8. Admin dashboard

```text
GET /api/admin/dashboard
```

Returns summary counts, recent tickets, category counts, and recent article updates. MVP analytics are operational summaries only; no advanced reporting or trend system.

## 9. Data model

### users

```text
id, name, email, employee_id, password, role, division,
created_at, updated_at
```

Unique: `email`, `employee_id`.

### knowledge_base_articles

```text
id, title, category, symptoms, keywords, problem_description,
solution_steps, expected_result, status, updated_by,
created_at, updated_at
```

Use JSON for ordered `solution_steps` and troubleshooting history where supported by the selected database; otherwise use normalized child rows. Keep the API shape as ordered arrays.

### troubleshooting_results

```text
id, user_id, category, description, source,
selected_article_id, result_payload, helpful,
created_at, updated_at
```

`result_payload` stores the rendered result needed to preserve the employee's troubleshooting context. Do not store provider credentials or hidden model reasoning.

### tickets

```text
id, ticket_number, user_id, name, division, issue_title, description,
category, device_code, priority, status, assigned_technician_id,
screenshot_url, kb_article_id, troubleshooting_result_id,
troubleshooting_history, resolution_notes, created_at, updated_at
```

Unique: `ticket_number`.

### ticket_activities

```text
id, ticket_id, user_id, type, note, old_status, new_status, created_at
```

### ticket_attachments

```text
id, ticket_id, disk, path, original_name, mime_type, size,
created_by, created_at
```

Never expose storage paths directly.

## 10. AI and retrieval boundary

Use one application service:

```text
TroubleshootingService
  → KnowledgeBaseRetriever
  → AiGuidanceGenerator (only when needed)
```

The first retriever is `KeywordKnowledgeBaseRetriever`. It searches only published articles and returns a stable internal result contract. A future `VectorKnowledgeBaseRetriever` can replace it without changing controllers or frontend responses.

MVP deliberately excludes:

- embeddings
- vector storage
- vector similarity queries
- background embedding jobs
- whole-KB prompts

Provider configuration is environment-based:

```env
AI_PROVIDER=proxy
GEMINI_API_KEY=
LOCAL_AI_URL=http://router-host:20128/v1
LOCAL_AI_API_KEY=
LOCAL_AI_MODEL=
```

Do not expose these variables through the frontend or API responses. Use test/synthetic data with a free provider tier until company privacy approval exists.

## 11. Error contract

Use consistent JSON errors:

```json
{
  "message": "The description field is required.",
  "errors": {
    "description": ["The description field is required."]
  }
}
```

Expected statuses:

- `401`: unauthenticated.
- `403`: authenticated but unauthorized.
- `404`: resource not found or inaccessible.
- `422`: validation failure.
- `413`: attachment too large.
- `429`: rate limited.
- `500`: unexpected server failure.

Do not reveal whether another employee's ticket exists; return the same inaccessible-resource behavior as appropriate.

## 12. Security requirements

- Sanctum cookies, CSRF protection, and same-domain configuration.
- Authorization policies on every user-owned resource.
- Mass-assignment protection.
- Server-side enum validation for category, priority, status, and article state.
- Password hashing through Laravel defaults.
- Rate-limit login, registration, troubleshooting, and uploads.
- Validate upload type, size, and storage path.
- Keep provider API keys in environment/secrets storage.
- Do not log passwords, tokens, uploaded file contents, or sensitive issue text unnecessarily.
- Escape/render user and AI text safely in React.
- Treat AI output as untrusted text.

## 13. Testing definition

Minimum runnable checks:

- Employee registration and login.
- `GET /api/profile` returns the authenticated user.
- Employee A cannot read Employee B's ticket.
- Draft articles never appear in troubleshooting retrieval.
- Published article retrieval returns ordered verified steps.
- AI provider failure returns a safe ticket recommendation.
- Device-related ticket categories require `device_code`.
- Invalid attachment type/size is rejected.
- Status transitions and activity records are enforced.
- Admin-only article and ticket actions reject employees.

## 14. Future vector-RAG upgrade

When keyword retrieval is insufficient:

1. Add an embedding/vector storage adapter.
2. Generate embeddings when a published article is created or updated.
3. Backfill existing published articles once.
4. Embed the incoming issue description.
5. Retrieve top matches, then apply category and published filters.
6. Keep the same troubleshooting response contract.

No frontend route or ticket contract should change.
