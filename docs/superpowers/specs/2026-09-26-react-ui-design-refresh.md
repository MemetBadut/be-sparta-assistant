# React UI Design Refresh

**Date:** 2026-09-26
**Status:** Design approved in conversation; implementation pending written-spec review
**Scope:** Visual refresh of `fe-sparta-assistant-react/`

## Intent

Bring the working React frontend closer to the supplied HTML design while treating the approved backend PRD and live API contract as authoritative. Reuse the reference project's visual language—clean enterprise UI, slate/blue palette, cards, tables, badges, whitespace, separate employee/admin surfaces—without copying its mock data, obsolete routes, or stale field names.

## Constraints

- Preserve current API services, auth behavior, role guards, route semantics, and backend.
- Backend PRD and live backend responses override the reference HTML.
- No mock data in product pages.
- No chat UI, live messaging, chart library, or state-management library.
- Keep `fe-sparta-assistant/` unchanged as legacy reference.
- Use native CSS and small React primitives; add no UI dependency.
- Preserve accessibility: labels, keyboard focus, readable status text, responsive tables/forms, and non-color-only status communication.

## Visual system

- **Typography:** system sans fallback with a compact enterprise hierarchy; use a display weight for headings and readable body text.
- **Palette:** slate page background, white surfaces, slate borders/text, blue primary action/focus, semantic green/amber/red only as secondary status signals.
- **Shape:** modest radius, thin borders, restrained shadow, generous page spacing.
- **Controls:** consistent input/select/textarea/button heights, visible focus rings, disabled/loading states, inline validation.
- **Status:** text badges for `Open`, `In Progress`, `Resolved`, `Closed`, `Low`, `Medium`, `High`, `Draft`, `Published`; color never carries meaning alone.

## Layouts

### Auth layout

Centered white card on a light slate page with a thin blue top strip, product mark/name, clear form heading, inline error, loading submit state, and links between login/register. Login label says **Email** because backend login accepts email only. No fake forgot-password or separate admin-login route.

### Employee layout

White header with product identity, links to `/dashboard` and `/tickets`, authenticated user identity, and real logout. Main content remains lower density with one primary action per page.

Employee dashboard (`/dashboard`) receives the main prompt, issue description, fixed category selector, troubleshooting CTA, and recent own tickets from the API. Category labels come from `GET /categories`; no hardcoded mock records.

### Admin layout

Dark slate left sidebar with product/admin identity, links to `/admin/dashboard`, `/admin/tickets`, and `/admin/articles`, authenticated admin identity, and real logout. Main content uses a white header/content area with denser operational tables and filters.

Admin dashboard uses real `GET /admin/dashboard` data: summary cards, recent tickets/articles, and category volume as accessible horizontal bars with visible labels and values. No chart dependency.

## Page treatment

- **Troubleshooting result:** article-like guided solution page, not chat. Distinct verified knowledge-base, non-verified general guidance, and no-guidance states. Feedback and ticket CTA follow PRD semantics.
- **Create ticket:** sectioned form card. Carry issue/category/result ID from troubleshooting. Do not accept client troubleshooting history override. Show device code only for `laptop_pc`/`printer`; keep backend validation authoritative.
- **My tickets:** responsive table/list with ticket number, issue, category, status, created/updated dates, empty/loading/error states. Detail route continues to use `ticket_number`.
- **Ticket detail:** structured issue, troubleshooting, attachment, assignment/status/resolution display. Employee resolution notes remain conditional on backend response.
- **Admin tickets:** filter toolbar and dense table matching reference hierarchy. Detail shows issue context, troubleshooting history, attachments, forward-only status options, assignment, notes, and activity timeline.
- **Admin articles:** searchable/filterable table and sectioned editor. Use only backend fields: `title`, `category`, `symptoms`, `keywords`, `problem_description`, `expected_result`, `status`. Draft/published state is explicit. Delete requires confirmation and `?confirm=1`.

## Component boundary

Create only reusable primitives used in at least two pages: layout shells, buttons/inputs, cards, status/priority badges, field rows, error/empty/loading states, and table wrappers. Keep feature-specific composition in page files. No generic design-system package.

## Verification

- Existing backend tests remain green.
- React build/typecheck remains green.
- Existing auth, employee, admin-ticket, and knowledge-base browser flows remain green.
- Add/adjust browser checks only where visual wiring changes behavior or routes.
- Manually inspect employee and admin pages at desktop and narrow viewport widths.

## Deliberate non-goals

- No API contract changes.
- No route renaming to match stale reference routes (`/my-tickets`, `/admin/knowledge-base`).
- No port of mock data or mock solution steps.
- No feature expansion beyond visual treatment and layout composition.
