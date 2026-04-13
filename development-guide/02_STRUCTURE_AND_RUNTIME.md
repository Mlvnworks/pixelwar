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
| `submission.php` | Loads shared classes and auto-loads request handlers from `submissions/`. |
| `navigator/navigator.php` | Builds the page whitelist, loads shared layout parts, and renders the selected page or 404 view. |
| `classes/` | Reusable PHP logic shared across the app. |
| `pages/` | Route-like page templates loaded with `?c=<page>`. |
| `submissions/` | Request handlers for POST forms, AJAX actions, and small action endpoints. |
| `components/` | Shared UI pieces and fallback views. |
| `styling/` | Global CSS and small page-specific CSS that supplements Tailwind. |
| `assets/` | Static assets such as icons and images. |

## Runtime Flow

1. `index.php` includes `config.php`
2. `config.php` loads environment values and sets app constants
3. `index.php` starts the session and resolves the requested page
4. `submission.php` initializes shared objects and loads handlers
5. `navigator/navigator.php` checks whether the page exists
6. The selected page is rendered from `pages/`
7. Shared components such as navbar, footer, and alerts are rendered around it

The default route `/` resolves to `pages/landing.php`. The fullscreen `pages/pixelwar.php` game route skips the shared navbar and footer so the game can own the full viewport.

## Development Guide

### Where To Put New Code

- New page: `pages/`
- New page CSS: `styling/page/`
- New request action: `submissions/`
- New shared helper: `classes/`
- New reusable layout block: `components/`
- New page styling: Tailwind classes first, then `styling/page/` only if necessary

### What Not To Mix

- Do not put request-handling logic inside page templates
- Do not put database boot logic inside page files
- Do not put reusable helper logic inside components
- Do not move core visual direction away from the retro arcade light theme unless the project priority file changes

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
