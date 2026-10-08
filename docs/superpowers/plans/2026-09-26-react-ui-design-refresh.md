# React UI Design Refresh Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give the working React frontend the visual language of the approved HTML design reference (cards, tables, badges, slate/blue palette, employee/admin layouts) without changing API contracts, routes, or auth behavior.

**Architecture:** Add a small shared CSS design-token file and a handful of reusable presentational components (layouts, badges, cards, form primitives). Restyle existing pages in place, page by page, reusing the new primitives. No new dependency, no mock data, no route renames.

**Tech Stack:** React, TypeScript, Vite, plain CSS (CSS custom properties + per-component `.css` files, same pattern the scaffold already uses for `App.css`/`index.css`). No Tailwind, no chart or UI-kit library.

**Spec:** `be-sparta-assistant/docs/superpowers/specs/2026-09-26-react-ui-design-refresh.md`

All file paths below are relative to the React repo root: `fe-sparta-assistant-react/`.

## Global Constraints

- Preserve current API services, auth behavior, role guards, and route paths exactly.
- Backend PRD and live backend responses are authoritative over the reference HTML; never port its mock data, `/my-tickets`/`/admin/knowledge-base` route names, "Email or Employee ID" login copy, or `solutionSteps` article field.
- No new npm dependency (no Tailwind, no chart library, no component kit).
- No chat UI, live messaging, or state-management library.
- Status/priority must always be readable text, never color-only.
- Every visual change must keep `npm run build` and the existing Playwright verification scripts green.
- Keep `fe-sparta-assistant/` (Vue) and the backend untouched.

## Review Focus

- A long ticket/article table row on a narrow viewport must stay usable (horizontal scroll or wrap), not silently clip data — no task's build check exercises real viewport width, so this needs a manual check recorded in Task 6.
- A `general_guidance` troubleshooting result must remain visually distinguishable from `verified_knowledge_base` even after restyling — Task 4 keeps distinct source badges, not just a shared card look.
- An admin ticket update failure (e.g. invalid status transition) must still show the backend's message text after the visual refactor, not swallow it silently — Task 5 keeps `FormMessage` wired on every mutating action.
- A draft knowledge-base article must still never render in the employee troubleshooting flow after the article editor's visual rewrite — Task 5's article editor keeps the existing status logic untouched, only restyled.
- An empty state (no tickets yet, no articles match filters) must render an explicit empty message, not a blank table — Task 3/5 keep and restyle existing empty-state branches rather than dropping them for a "cleaner" table.

---

### Task 1: Design tokens and shared primitives

**Files:**
- Modify: `src/index.css`
- Create: `src/styles/tokens.css`
- Create: `src/components/Card.tsx`
- Create: `src/components/Badge.tsx`
- Create: `src/components/Badge.test.ts`
- Create: `src/components/FormField.tsx`
- Create: `src/components/EmptyState.tsx`
- Modify: `src/components/FormMessage.tsx`

**Interfaces:**
- Produces `Card({ children, className? })` — a bordered white surface wrapper.
- Produces `Badge({ tone, children })` with `tone: 'neutral' | 'info' | 'warning' | 'success' | 'danger'` — renders `<span>` with a tone class, text always visible (never icon/color-only).
- Produces `statusTone(status: TicketStatus | ArticleStatus): Badge['tone']` and `priorityTone(priority: Priority): Badge['tone']` — pure mapping functions, unit tested.
- Produces `FormField({ label, htmlFor, error?, children })` — label + control + inline error, replaces repeated `<label>...</label>` blocks.
- Produces `EmptyState({ title, description? })` — centered placeholder block for empty lists.
- Consumes: nothing (first task).

- [ ] **Step 1: Write the failing test for tone mapping**

```typescript
// src/components/Badge.test.ts
import assert from 'node:assert/strict'
import test from 'node:test'
import { priorityTone, statusTone } from './Badge.ts'

test('maps every ticket status to a distinct readable tone', () => {
  assert.equal(statusTone('Open'), 'info')
  assert.equal(statusTone('In Progress'), 'warning')
  assert.equal(statusTone('Resolved'), 'success')
  assert.equal(statusTone('Closed'), 'neutral')
})

test('maps every priority to a distinct readable tone', () => {
  assert.equal(priorityTone('Low'), 'neutral')
  assert.equal(priorityTone('Medium'), 'warning')
  assert.equal(priorityTone('High'), 'danger')
})

test('maps article status to a tone', () => {
  assert.equal(statusTone('Draft'), 'neutral')
  assert.equal(statusTone('Published'), 'success')
})
```

- [ ] **Step 2: Run test to verify it fails**

Run: `node --experimental-strip-types --test src/components/Badge.test.ts`
Expected: FAIL — `Cannot find module './Badge.ts'`

- [ ] **Step 3: Write the design tokens**

```css
/* src/styles/tokens.css */
:root {
  --color-bg: #f8fafc;
  --color-surface: #ffffff;
  --color-border: #e2e8f0;
  --color-border-strong: #cbd5e1;
  --color-text: #0f172a;
  --color-text-muted: #64748b;
  --color-text-subtle: #94a3b8;
  --color-primary: #2563eb;
  --color-primary-hover: #1d4ed8;
  --color-primary-light: #eff6ff;

  --tone-info-bg: #eff6ff;
  --tone-info-text: #1d4ed8;
  --tone-info-border: #bfdbfe;
  --tone-warning-bg: #fffbeb;
  --tone-warning-text: #b45309;
  --tone-warning-border: #fde68a;
  --tone-success-bg: #f0fdf4;
  --tone-success-text: #15803d;
  --tone-success-border: #bbf7d0;
  --tone-danger-bg: #fef2f2;
  --tone-danger-text: #b91c1c;
  --tone-danger-border: #fecaca;
  --tone-neutral-bg: #f1f5f9;
  --tone-neutral-text: #475569;
  --tone-neutral-border: #e2e8f0;

  --radius-sm: 6px;
  --radius-md: 10px;
  --shadow-sm: 0 1px 2px rgba(15, 23, 42, 0.06);
  --font-display: 'Segoe UI', system-ui, sans-serif;
}
```

- [ ] **Step 4: Import tokens and set page background**

```css
/* src/index.css — replace full contents */
@import './styles/tokens.css';

:root {
  font-family: system-ui, sans-serif;
  color: var(--color-text);
  background: var(--color-bg);
  font-synthesis: none;
  text-rendering: optimizeLegibility;
  -webkit-font-smoothing: antialiased;
}

body {
  min-width: 320px;
  margin: 0;
}

:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}
```

- [ ] **Step 5: Implement Badge with the tone mappings the test expects**

```typescript
// src/components/Badge.tsx
import './Badge.css'
import type { ArticleStatus } from '../types/article.ts'
import type { Priority, TicketStatus } from '../types/helpdesk.ts'

export type Tone = 'neutral' | 'info' | 'warning' | 'success' | 'danger'

export function statusTone(status: TicketStatus | ArticleStatus): Tone {
  switch (status) {
    case 'Open':
      return 'info'
    case 'In Progress':
      return 'warning'
    case 'Resolved':
    case 'Published':
      return 'success'
    case 'Closed':
    case 'Draft':
      return 'neutral'
  }
}

export function priorityTone(priority: Priority): Tone {
  switch (priority) {
    case 'Low':
      return 'neutral'
    case 'Medium':
      return 'warning'
    case 'High':
      return 'danger'
  }
}

export function Badge({ tone, children }: { tone: Tone; children: React.ReactNode }) {
  return <span className={`badge badge-${tone}`}>{children}</span>
}
```

```css
/* src/components/Badge.css */
.badge {
  display: inline-flex;
  align-items: center;
  padding: 2px 8px;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 600;
  border: 1px solid transparent;
  white-space: nowrap;
}
.badge-neutral { background: var(--tone-neutral-bg); color: var(--tone-neutral-text); border-color: var(--tone-neutral-border); }
.badge-info { background: var(--tone-info-bg); color: var(--tone-info-text); border-color: var(--tone-info-border); }
.badge-warning { background: var(--tone-warning-bg); color: var(--tone-warning-text); border-color: var(--tone-warning-border); }
.badge-success { background: var(--tone-success-bg); color: var(--tone-success-text); border-color: var(--tone-success-border); }
.badge-danger { background: var(--tone-danger-bg); color: var(--tone-danger-text); border-color: var(--tone-danger-border); }
```

- [ ] **Step 6: Run test to verify it passes**

Run: `node --experimental-strip-types --test src/components/Badge.test.ts`
Expected: PASS, 3/3

- [ ] **Step 7: Implement Card, FormField, EmptyState**

```typescript
// src/components/Card.tsx
import './Card.css'

export function Card({ children, className = '' }: { children: React.ReactNode; className?: string }) {
  return <div className={`card ${className}`}>{children}</div>
}
```

```css
/* src/components/Card.css */
.card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  padding: 1.25rem;
}
```

```typescript
// src/components/FormField.tsx
export function FormField({
  label,
  htmlFor,
  error,
  children,
}: {
  label: string
  htmlFor: string
  error?: string
  children: React.ReactNode
}) {
  return (
    <div className="form-field">
      <label htmlFor={htmlFor}>{label}</label>
      {children}
      {error && <p className="field-error" role="alert">{error}</p>}
    </div>
  )
}
```

```typescript
// src/components/EmptyState.tsx
export function EmptyState({ title, description }: { title: string; description?: string }) {
  return (
    <div className="empty-state">
      <p className="empty-title">{title}</p>
      {description && <p className="empty-description">{description}</p>}
    </div>
  )
}
```

- [ ] **Step 8: Restyle FormMessage to reuse the danger tone visually**

Read the current file before editing:

```bash
cat src/components/FormMessage.tsx
```

Update its rendered markup to use a `form-error` class already referenced by `App.css` (no behavior change — same `errorMessage()` export, same conditional render). Do not change its exported function signatures.

- [ ] **Step 9: Run full build**

Run: `npm run build`
Expected: successful build, no type errors

- [ ] **Step 10: Commit**

```bash
git add src/styles/tokens.css src/index.css src/components/Card.tsx src/components/Card.css src/components/Badge.tsx src/components/Badge.css src/components/Badge.test.ts src/components/FormField.tsx src/components/EmptyState.tsx src/components/FormMessage.tsx
git commit -m "feat: add shared design tokens and presentational primitives"
```

---

### Task 2: Restyle auth layout and employee/admin shells

**Files:**
- Modify: `src/pages/LoginPage.tsx`
- Modify: `src/pages/RegisterPage.tsx`
- Create: `src/styles/auth.css`
- Create: `src/layouts/EmployeeShell.tsx`
- Create: `src/layouts/EmployeeShell.css`
- Create: `src/layouts/AdminShell.tsx`
- Create: `src/layouts/AdminShell.css`
- Modify: `src/pages/EmployeeHomePage.tsx`
- Modify: `src/pages/admin/AdminDashboardPage.tsx`

**Interfaces:**
- Consumes: `Card` and `Badge`/`FormField` from Task 1; `useAuth()` from existing `src/auth/useAuth.ts` (unchanged signature: `{ user, logout, ... }`).
- Produces `EmployeeShell({ children })` — renders the white header (logo mark, `Dashboard`/`My Tickets` nav using existing route paths `/dashboard` and `/tickets`, user identity, logout) then `children` in a `<main>`.
- Produces `AdminShell({ children })` — renders the dark sidebar (`Dashboard`/`Tickets`/`Knowledge Base` nav using existing route paths `/admin/dashboard`, `/admin/tickets`, `/admin/articles`, user identity, logout) then `children` in a content column.
- Both shells read `useAuth()` themselves; pages stop rendering their own header/logout button once wrapped.

- [ ] **Step 1: Read current LoginPage and EmployeeHomePage before editing**

```bash
cat src/pages/LoginPage.tsx src/pages/EmployeeHomePage.tsx src/pages/admin/AdminDashboardPage.tsx
```

- [ ] **Step 2: Write auth page styles**

```css
/* src/styles/auth.css */
.auth-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: var(--color-bg);
}
.auth-page::before {
  content: '';
  height: 4px;
  background: var(--color-primary);
}
.auth-page-body {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
}
.auth-card {
  width: 100%;
  max-width: 24rem;
}
.auth-title {
  text-align: center;
  margin-bottom: 1.5rem;
}
```

- [ ] **Step 3: Restyle LoginPage markup (keep existing state/handlers, only change JSX/labels)**

Replace the returned JSX of `LoginPage` to wrap the form in `.auth-page` / `.auth-page-body` / `Card` with class `auth-card`, and change the email field's visible `<label>` text to exactly `Email` (backend accepts email only — this fixes the reference design's stale "Email or Employee ID" copy per the spec). Keep every existing `useState`, `onSubmit`, and `useAuth()` call unchanged — only the returned markup and its `className`s change. Import `Card` from `../components/Card.tsx` and `./../styles/auth.css` becomes `../styles/auth.css`.

- [ ] **Step 4: Restyle RegisterPage the same way**

Apply the same `.auth-page` wrapper and `Card` treatment to `RegisterPage`, reusing `../styles/auth.css`. Keep all existing field state and `register()` call untouched.

- [ ] **Step 5: Build EmployeeShell**

```typescript
// src/layouts/EmployeeShell.tsx
import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../auth/useAuth.ts'
import './EmployeeShell.css'

export function EmployeeShell({ children }: { children: React.ReactNode }) {
  const { user, logout } = useAuth()
  const navigate = useNavigate()

  const handleLogout = async () => {
    await logout()
    navigate('/login', { replace: true })
  }

  return (
    <div className="employee-shell">
      <header className="employee-header">
        <div className="employee-header-left">
          <Link to="/dashboard" className="brand">IT Helpdesk Assistant</Link>
          <nav>
            <Link to="/dashboard">Dashboard</Link>
            <Link to="/tickets">My Tickets</Link>
          </nav>
        </div>
        <div className="employee-header-right">
          <span>{user?.name}</span>
          <button type="button" onClick={handleLogout}>Log out</button>
        </div>
      </header>
      <main className="employee-main">{children}</main>
    </div>
  )
}
```

```css
/* src/layouts/EmployeeShell.css */
.employee-shell { min-height: 100vh; display: flex; flex-direction: column; background: var(--color-bg); }
.employee-header {
  background: var(--color-surface);
  border-bottom: 1px solid var(--color-border);
  height: 3.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 1.5rem;
}
.employee-header-left { display: flex; align-items: center; gap: 2rem; }
.employee-header-left nav { display: flex; gap: 1rem; }
.employee-header-left a { color: var(--color-text-muted); text-decoration: none; font-size: 0.9rem; }
.employee-header-right { display: flex; align-items: center; gap: 0.75rem; font-size: 0.85rem; }
.brand { font-weight: 600; color: var(--color-text); text-decoration: none; }
.employee-main { flex: 1; }
```

- [ ] **Step 6: Build AdminShell**

```typescript
// src/layouts/AdminShell.tsx
import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../auth/useAuth.ts'
import './AdminShell.css'

export function AdminShell({ children }: { children: React.ReactNode }) {
  const { user, logout } = useAuth()
  const navigate = useNavigate()

  const handleLogout = async () => {
    await logout()
    navigate('/login', { replace: true })
  }

  return (
    <div className="admin-shell">
      <aside className="admin-sidebar">
        <div className="admin-brand">IT Helpdesk Assistant<span>Admin</span></div>
        <nav>
          <Link to="/admin/dashboard">Dashboard</Link>
          <Link to="/admin/tickets">Tickets</Link>
          <Link to="/admin/articles">Knowledge Base</Link>
        </nav>
        <div className="admin-user">
          <span>{user?.name}</span>
          <button type="button" onClick={handleLogout}>Log out</button>
        </div>
      </aside>
      <div className="admin-content">{children}</div>
    </div>
  )
}
```

```css
/* src/layouts/AdminShell.css */
.admin-shell { min-height: 100vh; display: flex; }
.admin-sidebar {
  width: 14rem;
  background: #0f172a;
  color: #cbd5e1;
  display: flex;
  flex-direction: column;
  padding: 1rem;
  gap: 1.5rem;
}
.admin-brand { color: white; font-weight: 600; font-size: 0.9rem; display: flex; flex-direction: column; }
.admin-brand span { font-size: 0.7rem; color: #94a3b8; font-weight: 400; }
.admin-sidebar nav { display: flex; flex-direction: column; gap: 0.25rem; }
.admin-sidebar nav a { color: #cbd5e1; text-decoration: none; padding: 0.5rem 0.75rem; border-radius: var(--radius-sm); font-size: 0.85rem; }
.admin-sidebar nav a:hover { background: #1e293b; color: white; }
.admin-user { margin-top: auto; display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.8rem; }
.admin-content { flex: 1; background: var(--color-bg); }
```

- [ ] **Step 7: Wrap EmployeeHomePage and AdminDashboardPage in the new shells**

Read each file, then replace their outer `<main className="app-shell">...</main>` wrapper with `<EmployeeShell>`/`<AdminShell>` respectively, removing the header/logout markup those pages built inline (now owned by the shell). Keep all existing data-fetching (`adminApi.dashboard()`, etc.) and `FormMessage` usage unchanged.

- [ ] **Step 8: Run full build**

Run: `npm run build`
Expected: successful build, no type errors

- [ ] **Step 9: Run existing auth browser verification**

Run: `npm run dev -- --host localhost --port 5173 &` then `node scripts/verify-auth.mjs`, then stop the dev server.
Expected: `All auth flow checks passed`

- [ ] **Step 10: Commit**

```bash
git add src/pages/LoginPage.tsx src/pages/RegisterPage.tsx src/styles/auth.css src/layouts/EmployeeShell.tsx src/layouts/EmployeeShell.css src/layouts/AdminShell.tsx src/layouts/AdminShell.css src/pages/EmployeeHomePage.tsx src/pages/admin/AdminDashboardPage.tsx
git commit -m "feat: restyle auth pages and add employee/admin shells"
```

---

### Task 3: Restyle employee dashboard and ticket pages

**Files:**
- Modify: `src/pages/EmployeeHomePage.tsx`
- Modify: `src/pages/employee/TroubleshootingPage.tsx`
- Modify: `src/pages/employee/MyTicketsPage.tsx`
- Modify: `src/pages/employee/TicketDetailPage.tsx`
- Modify: `src/pages/employee/CreateTicketPage.tsx`
- Create: `src/styles/table.css`

**Interfaces:**
- Consumes: `Card`, `Badge`/`statusTone`/`priorityTone`, `FormField`, `EmptyState` from Task 1; `EmployeeShell` from Task 2.
- No new exports — this task only restyles existing page components; their exported function names and props stay identical.

- [ ] **Step 1: Read all five files before editing**

```bash
cat src/pages/employee/TroubleshootingPage.tsx src/pages/employee/MyTicketsPage.tsx src/pages/employee/TicketDetailPage.tsx src/pages/employee/CreateTicketPage.tsx
```

- [ ] **Step 2: Write shared table styles**

```css
/* src/styles/table.css */
.data-table-wrap { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.data-table th {
  text-align: left;
  padding: 0.6rem 0.9rem;
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  color: var(--color-text-muted);
  border-bottom: 1px solid var(--color-border);
  white-space: nowrap;
}
.data-table td {
  padding: 0.6rem 0.9rem;
  border-bottom: 1px solid var(--color-border);
  vertical-align: top;
}
.data-table tr:last-child td { border-bottom: none; }
.data-table tr:hover td { background: var(--color-bg); }
```

- [ ] **Step 3: Restyle EmployeeHomePage's dashboard content (form + recent tickets) inside the Task-2 EmployeeShell**

Wrap the issue-description/category form in a `Card`; render category options as a labeled `<select>` inside `FormField` (do not invent icon-button category tiles — that visual flourish is reference-only decoration, not in the PRD's required fields). Add a "Recent Tickets" section below the form using `data-table` classes and `Badge`/`statusTone` for the status column, with `EmptyState` when the employee has no tickets yet. Keep the existing navigation to `/troubleshooting` and any existing links unchanged.

- [ ] **Step 4: Restyle TroubleshootingPage**

Wrap the category/description form in a `Card` with `FormField` wrappers. Keep existing `helpdeskApi.submitTroubleshooting()` call and navigation to the result page unchanged.

- [ ] **Step 5: Restyle the troubleshooting result page's source badges**

Read `src/pages/employee/TroubleshootingResultPage.tsx`. Replace its ad hoc `className="eyebrow"` source labels with `Badge` using `tone="success"` for `verified_knowledge_base`, `tone="warning"` for `general_guidance`, and `tone="neutral"` for `no_guidance` — each badge text stays exactly as it is today (do not reword the "non-verified" language required by the PRD). Wrap each result section in a `Card`.

- [ ] **Step 6: Restyle MyTicketsPage**

Replace its `<ul>` list with a `data-table` (`Ticket Number`, `Issue`, `Status`, columns already available from `Ticket`), a `Badge`/`statusTone` status cell, and `EmptyState` for the zero-tickets case (replacing the current `<p>No tickets yet.</p>`). Keep the existing pagination buttons and `helpdeskApi.myTickets(page)` call unchanged.

- [ ] **Step 7: Restyle TicketDetailPage**

Wrap the ticket header, description, attachments, and resolution-notes sections each in their own `Card`. Use `Badge`/`statusTone` and `Badge`/`priorityTone` next to the ticket number. Keep the existing upload `<input type="file">` handler and attachment list untouched — only wrap it in a `Card`.

- [ ] **Step 8: Restyle CreateTicketPage**

Wrap each existing field group (issue title / category / description, priority / device code / repair-required) in a `Card` with `FormField` wrappers per input. Keep the existing `requiresDeviceCode()` conditional rendering and `helpdeskApi.createTicket()` call unchanged.

- [ ] **Step 9: Run full build**

Run: `npm run build`
Expected: successful build, no type errors

- [ ] **Step 10: Run existing employee flow browser verification**

Run: `npm run dev -- --host localhost --port 5173 &` then `node scripts/verify-employee-flow.mjs`, then stop the dev server.
Expected: all 4 checks `PASS`

- [ ] **Step 11: Commit**

```bash
git add src/pages/EmployeeHomePage.tsx src/pages/employee/TroubleshootingPage.tsx src/pages/employee/TroubleshootingResultPage.tsx src/pages/employee/MyTicketsPage.tsx src/pages/employee/TicketDetailPage.tsx src/pages/employee/CreateTicketPage.tsx src/styles/table.css
git commit -m "feat: restyle employee dashboard and ticket pages"
```

---

### Task 4: Restyle admin dashboard, ticket list/detail

**Files:**
- Modify: `src/pages/admin/AdminDashboardPage.tsx`
- Modify: `src/pages/admin/AdminTicketsPage.tsx`
- Modify: `src/pages/admin/AdminTicketDetailPage.tsx`
- Create: `src/components/BarRow.tsx`
- Create: `src/components/BarRow.test.ts`

**Interfaces:**
- Consumes: `Card`, `Badge`/`statusTone`/`priorityTone`, `FormField`, `EmptyState`, `.data-table` from earlier tasks; `AdminShell` from Task 2.
- Produces `barWidthPercent(value: number, max: number): number` — pure function clamped to `[0, 100]`, used to size the category-volume bars without a chart library.
- Produces `BarRow({ label, value, max })` — renders a labeled horizontal bar with the numeric value always visible as text (never color-only).

- [ ] **Step 1: Write the failing test for the bar width calculation**

```typescript
// src/components/BarRow.test.ts
import assert from 'node:assert/strict'
import test from 'node:test'
import { barWidthPercent } from './BarRow.ts'

test('scales a value to a percentage of the max', () => {
  assert.equal(barWidthPercent(5, 10), 50)
  assert.equal(barWidthPercent(10, 10), 100)
  assert.equal(barWidthPercent(0, 10), 0)
})

test('clamps to zero when max is zero to avoid division by zero', () => {
  assert.equal(barWidthPercent(0, 0), 0)
})
```

- [ ] **Step 2: Run test to verify it fails**

Run: `node --experimental-strip-types --test src/components/BarRow.test.ts`
Expected: FAIL — `Cannot find module './BarRow.ts'`

- [ ] **Step 3: Implement BarRow and barWidthPercent**

```typescript
// src/components/BarRow.tsx
import './BarRow.css'

export function barWidthPercent(value: number, max: number): number {
  if (max <= 0) return 0
  return Math.min(100, Math.max(0, (value / max) * 100))
}

export function BarRow({ label, value, max }: { label: string; value: number; max: number }) {
  return (
    <div className="bar-row">
      <span className="bar-label">{label}</span>
      <div className="bar-track">
        <div className="bar-fill" style={{ width: `${barWidthPercent(value, max)}%` }} />
      </div>
      <span className="bar-value">{value}</span>
    </div>
  )
}
```

```css
/* src/components/BarRow.css */
.bar-row { display: grid; grid-template-columns: 9rem 1fr 2.5rem; align-items: center; gap: 0.75rem; font-size: 0.8rem; padding: 0.35rem 0; }
.bar-label { color: var(--color-text-muted); }
.bar-track { background: var(--color-bg); border-radius: 999px; height: 8px; overflow: hidden; }
.bar-fill { background: var(--color-primary); height: 100%; border-radius: 999px; }
.bar-value { text-align: right; font-weight: 600; }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `node --experimental-strip-types --test src/components/BarRow.test.ts`
Expected: PASS, 2/2

- [ ] **Step 5: Read AdminDashboardPage, AdminTicketsPage, AdminTicketDetailPage before editing**

```bash
cat src/pages/admin/AdminDashboardPage.tsx src/pages/admin/AdminTicketsPage.tsx src/pages/admin/AdminTicketDetailPage.tsx
```

- [ ] **Step 6: Restyle AdminDashboardPage**

Wrap the summary stats in a 4-up grid of `Card`s. Add a "Tickets by category" section using `BarRow` for each entry of the existing `dashboard.tickets_by_category` map (computing `max` as the largest value in that map). Wrap "Recent tickets" in a `Card` with a `data-table` and `Badge`/`statusTone`/`priorityTone` cells, replacing any plain-text status. Keep the existing `adminApi.dashboard()` call and `AdminShell` wrapper unchanged.

- [ ] **Step 7: Restyle AdminTicketsPage**

Wrap the filter controls (search/category/status/priority) in a `Card` using `FormField` per control. Replace the ticket `<ul>`/plain rows with `.data-table`, `Badge`/`statusTone`/`priorityTone` cells, and `EmptyState` for the zero-results case. Keep the existing `adminApi.tickets(filters)` call, pagination, and route links unchanged.

- [ ] **Step 8: Restyle AdminTicketDetailPage**

Wrap issue details, troubleshooting history/description, and the management panel (assignee/status/resolution-notes form) in separate `Card`s. Use `FormField` around the assignee input, status select, and resolution-notes textarea. Use `Badge`/`statusTone`/`priorityTone` next to the ticket number heading. Wrap the activity log in its own `Card`, keeping the existing `adminApi.activities()` call and rendering. Keep `nextStatusOptions()` usage and `adminApi.updateTicket()` call unchanged.

- [ ] **Step 9: Run full build**

Run: `npm run build`
Expected: successful build, no type errors

- [ ] **Step 10: Run existing admin flow browser verifications**

Run the dev server, then in sequence: `node scripts/verify-admin-flow.mjs` and `node scripts/verify-employee-denied.mjs`, then stop the dev server.
Expected: both scripts report all checks `PASS`

- [ ] **Step 11: Commit**

```bash
git add src/pages/admin/AdminDashboardPage.tsx src/pages/admin/AdminTicketsPage.tsx src/pages/admin/AdminTicketDetailPage.tsx src/components/BarRow.tsx src/components/BarRow.css src/components/BarRow.test.ts
git commit -m "feat: restyle admin dashboard and ticket pages"
```

---

### Task 5: Restyle admin knowledge base pages

**Files:**
- Modify: `src/pages/admin/AdminArticlesPage.tsx`
- Modify: `src/pages/admin/ArticleEditorPage.tsx`

**Interfaces:**
- Consumes: `Card`, `Badge`/`statusTone`, `FormField`, `EmptyState`, `.data-table` from earlier tasks; `AdminShell` from Task 2.
- No new exports.

- [ ] **Step 1: Read both files before editing**

```bash
cat src/pages/admin/AdminArticlesPage.tsx src/pages/admin/ArticleEditorPage.tsx
```

- [ ] **Step 2: Restyle AdminArticlesPage**

Wrap the search/category/status filters in a `Card` with `FormField` wrappers. Replace the article `<ul>` with a `.data-table` (`Title`, `Category`, `Status`, `Updated`, actions), `Badge`/`statusTone` for the status column, and `EmptyState` for the zero-results case. Keep the existing delete-confirmation `window.confirm()` flow and `articlesApi.remove(id)` call unchanged — this preserves the PRD's required delete confirmation and the backend's `?confirm=1` contract.

- [ ] **Step 3: Restyle ArticleEditorPage**

Wrap "Article Information" fields (title, category, symptoms, keywords, problem description, expected result) in a `Card` with one `FormField` per input, and the status selector in the same card using existing `<select>` bound to `form.status` (do not add the reference design's fake step-by-step editor — the backend has no `solution_steps` field, only `symptoms`/`problem_description`/`expected_result`, per spec). Keep `articlesApi.create()`/`articlesApi.update()` calls and validation untouched.

- [ ] **Step 4: Run full build**

Run: `npm run build`
Expected: successful build, no type errors

- [ ] **Step 5: Run existing knowledge base flow browser verification**

Run the dev server, then `node scripts/verify-kb-flow.mjs`, then stop the dev server.
Expected: all 5 checks `PASS`

- [ ] **Step 6: Commit**

```bash
git add src/pages/admin/AdminArticlesPage.tsx src/pages/admin/ArticleEditorPage.tsx
git commit -m "feat: restyle admin knowledge base pages"
```

---

### Task 6: Full regression, manual responsive check, and documentation

**Files:**
- Modify: `fe-sparta-assistant-react/README.md`

**Interfaces:**
- None — verification and documentation only.

- [ ] **Step 1: Run backend regression**

Run: `cd ../be-sparta-assistant && php artisan test`
Expected: `35 passed`

- [ ] **Step 2: Run full React build, lint, and unit suite**

Run: `cd ../fe-sparta-assistant-react && npm run build && npm run lint && node --experimental-strip-types --test src/**/*.test.ts`
Expected: build succeeds; lint shows no new errors beyond the pre-existing stylistic warnings recorded in the migration ledger; all unit tests pass

- [ ] **Step 3: Run every existing Playwright verification script**

Run the dev server on port 5173, then in sequence: `node scripts/verify-auth.mjs`, `node scripts/verify-employee-flow.mjs`, `node scripts/verify-admin-flow.mjs`, `node scripts/verify-employee-denied.mjs`, `node scripts/verify-kb-flow.mjs`, `node scripts/verify-stale-csrf.mjs`, then stop the dev server.
Expected: every script reports all checks `PASS`

- [ ] **Step 4: Manual responsive check (Review Focus item — no automated test covers this)**

With the dev server running, open `http://localhost:5173` in a real browser at both a desktop width (~1440px) and a narrow width (~375px). Confirm: the admin sidebar and ticket/article tables either wrap or scroll horizontally without clipping data at 375px; badges remain legible text at both widths; no layout overlaps the viewport. Record the result in the commit message.

- [ ] **Step 5: Update the React README with the new component/layout structure**

Add a short "UI components" subsection to `fe-sparta-assistant-react/README.md` listing `src/components/` (Card, Badge, FormField, EmptyState, BarRow) and `src/layouts/` (EmployeeShell, AdminShell) alongside the existing architecture notes.

- [ ] **Step 6: Commit**

```bash
git add README.md
git commit -m "docs: document UI component and layout structure after design refresh"
```
