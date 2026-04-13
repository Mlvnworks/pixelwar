# Setup And Development Guide

## Purpose

This guide explains how to configure, run, and safely extend the current PHVN codebase.

## Quick Setup

1. Review `.env`
2. Replace placeholder values with your local settings
3. If you need database access, set all `DB_*` values
4. If you need mail sending, set all `MAIL_*` values
5. Serve the project through XAMPP or Apache

## Current App Direction

- Website description: gamified CSS game for students
- Visual direction: retro and arcade light theme
- Styling direction: Tailwind first
- UX direction: small, readable, student-friendly interactions

## Key Environment Variables

| Variable | Purpose |
| --- | --- |
| `APP_NAME` | App label shown in the UI |
| `APP_ENV` | Runtime environment such as `local` or `production` |
| `APP_DEBUG` | Controls PHP error display |
| `APP_URL` | Base URL for local or deployed access |
| `APP_TIMEZONE` | Default timezone |
| `DB_HOST` | MySQL host |
| `DB_PORT` | MySQL port |
| `DB_NAME` | MySQL database name |
| `DB_USER` | MySQL username |
| `DB_PASS` | MySQL password |
| `MAIL_HOST` | SMTP host |
| `MAIL_PORT` | SMTP port |
| `MAIL_ENCRYPTION` | SMTP encryption type |
| `MAIL_USERNAME` | SMTP username |
| `MAIL_PASSWORD` | SMTP password |
| `MAIL_FROM_ADDRESS` | Sender email |
| `MAIL_FROM_NAME` | Sender display name |

## Development Guide

### General Rules

- Keep runtime secrets in `.env`
- Keep `.env.example` safe for sharing
- Put shared PHP logic in `classes/`
- Put form and AJAX handlers in `submissions/`
- Put route-like page templates in `pages/`
- Put shared UI in `components/`
- Keep `config.php` responsible for environment and database setup
- Keep `submission.php` responsible for object boot and handler loading

### Database Rules

- If you use DB features, provide all of `DB_HOST`, `DB_USER`, and `DB_NAME`
- Partial DB config is treated as an error
- If no DB config is provided, the app stays in no-database mode

### Mail Rules

- Use environment values only
- Do not place SMTP credentials inside classes or page files
- Prefer `APP_NAME` and mail sender values that match the current PHVN branding

### Documentation Rules

- Keep new instructional content inside `development-guide/`
- Name guide files by purpose, not by generic names
- Keep guide wording aligned with the current game-oriented project direction

## Example Use Case

### Example: Configure Local Database

Update `.env` like this:

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=phvn_local
DB_USER=root
DB_PASS=
```

Then reload the project. `config.php` will connect automatically during bootstrap.

### Example: Enable Mail Sending

Update `.env` like this:

```env
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=your-account@example.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_ADDRESS=your-account@example.com
MAIL_FROM_NAME="PHVN"
```
