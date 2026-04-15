# Structure And Runtime Guide

## Purpose

This guide explains the current project structure and how a request moves through the app.

## Architecture Overview

Pixelwar follows a plain PHP structure with three main runtime layers:

1. `index.php`
2. `submission.php`
3. `navigator/navigator.php`

## Important Structure

| Path | What It Does |
| --- | --- |
| `index.php` | Main entry point. Loads config, starts the session, resolves `?c=<page>`, and starts the runtime. |
| `config.php` | Loads `.env`, defines constants, and opens the MySQL connection when fully configured. |
| `submission.php` | Loads shared classes and auto-loads top-level request handler loaders from `submissions/`. |
| `navigator/navigator.php` | Builds the page whitelist, loads shared layout parts, and renders the selected page or 404 view. |
| `classes/` | Reusable PHP logic shared across the app. |
| `pages/` | Route-like page templates loaded with `?c=<page>`. |
| `submissions/` | Top-level handler loaders. Endpoint files live in clear subfolders such as `submissions/auth/`. |
| `teacher/` | Teacher panel runtime. It follows the same `index.php`, `submission.php`, `navigator/`, `pages/`, `components/`, `styling/`, and `submissions/` pattern while sharing root `config.php` and root classes. |
| `components/` | Shared UI pieces and fallback views. |
| `styling/` | Global CSS and small page-specific CSS that supplements Tailwind. |
| `assets/` | Static assets such as icons and images. |

## Runtime Flow

1. `index.php` includes `config.php`
2. `config.php` loads environment values and sets app constants
3. `index.php` starts the session and resolves the requested page
4. `submission.php` initializes shared objects and loads top-level handler loaders
5. Handler loaders load their endpoint files in numeric natural order
6. `navigator/navigator.php` checks whether the page exists
7. The selected page is rendered from `pages/`
8. Shared components such as navbar, footer, and alerts are rendered around it

The default route `/` resolves to `pages/landing.php`. The fullscreen `pages/pixelwar.php` game route skips the shared navbar and footer so the game can own the full viewport.

The teacher panel starts at `teacher/index.php`. It shares root `config.php`, root `.env`, root classes, and the same session, but keeps teacher pages, components, styles, and endpoints inside `teacher/`. The teacher panel is intentionally accessible without an active player login; if a session exists, teacher endpoints may hydrate the current profile, but the panel should not depend on player login state unless a specific action needs authentication.

## Development Guide

### Where To Put New Code

- New page: `pages/`
- New page CSS: `styling/page/`
- New student request action: `submissions/<domain>/<number>_<clear_action>.endpoint.php`
- New teacher request action: `teacher/submissions/<domain>/<number>_<clear_action>.endpoint.php`
- New handler helper or loader: the matching top-level file, for example `submissions/auth.php` or `teacher/submissions/auth.php`
- New shared helper: `classes/`
- New database operation: a categorized class in `classes/`, for example `UserRepository` or `VerificationRepository`
- New reusable layout block: `components/`
- New page styling: Tailwind classes first, then `styling/page/` only if necessary

### What Not To Mix

- Do not put request-handling logic inside page templates
- Do not put database boot logic inside page files
- Do not put reusable helper logic inside components
- Do not write SQL queries directly in `submissions/` or `teacher/submissions/`
- Do not add unrelated endpoint actions into a large mixed handler file
- Do not duplicate root configuration inside `teacher/`; require the shared root config/classes instead
- Do not move core visual direction away from the retro arcade light theme unless the project priority file changes

### Endpoint File Pattern

Use one endpoint file per action. Keep naming explicit and route-focused:

| Pattern | Use |
| --- | --- |
| `submissions/auth.php` | Loader and shared auth helpers only. |
| `submissions/auth/60_login.endpoint.php` | Student/root login POST endpoint. |
| `submissions/auth/70_signup.endpoint.php` | Student/root signup POST endpoint. |
| `teacher/submissions/auth.php` | Teacher auth loader only. |
| `teacher/submissions/auth/10_require_teacher.endpoint.php` | Optional teacher session hydration endpoint. Do not block guest panel access here. |

Rules:

- Prefix endpoint files with a number when execution order matters.
- Use `SORT_NATURAL` in loaders so `90_...` runs before `100_...`.
- Use lowercase snake_case action names.
- End endpoint files with `.endpoint.php`.
- Exit early or return when the request does not match that endpoint.
- Keep shared helper functions in the loader or a `00_*_helpers.php` file, not inside endpoint files.

### Repository Pattern

Submissions are request coordinators only. They can validate input, call classes, set session messages, and redirect. They must not prepare SQL or manage table queries directly.

Use categorized classes in `classes/` for database work:

| Class Type | Use |
| --- | --- |
| `UserRepository` | User login lookup, account creation, profile details, avatars, email availability, and session-user lookups. |
| `VerificationRepository` | Verification token records, status updates, and pending-token invalidation. |
| `UserAccountService` | Multi-table user workflows such as signup with verification, email verification completion, and settings/profile saves. |
| `*Repository` | Future database categories such as challenges, rooms, comments, or analytics. |
| Service class | External integrations such as Supabase uploads or email delivery. |

Rules:

- Put SQL in repository classes, not endpoint files.
- Keep endpoint files focused on request flow.
- Name repository methods after the domain action, for example `findLoginUser`, `emailExistsForOtherUser`, or `saveSettingsProfile`.
- Use service classes for workflows that coordinate multiple repositories or write multiple tables.
- Keep transactions inside service classes or repository methods, not endpoint files.
- Let endpoint files handle redirects and session messages after repository calls return.

## Example Use Case

### Example: Add A New `scoreboard` Page

1. Create `pages/scoreboard.php`
2. Build the page with Tailwind classes first
3. Create `styling/page/scoreboard.css` only if a small CSS addition is still needed
4. Open `/?c=scoreboard`

The navigator will automatically allow the page because it builds its whitelist from `pages/*.php`.

### Example: Add A Shared Game Banner Component

1. Create `components/game-banner.php`
2. Include it from a page or from `navigator/navigator.php`
3. Keep the component in the same retro arcade light design language
