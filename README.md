# Droplinks Builder

Low-code platform for building OutSystems-like apps using Laravel, Livewire, and SQLite only (no Node.js).

## Current status

- Dashboard and per-app studio for versions, entities, pages, data sources, actions, workflows, roles, and records.
- Metadata model with export/import support.
- Runtime renderer for text, table, and form components.

## Local setup (no Node)

1. Install dependencies:
   - `composer install`
2. Set up environment:
   - `cp .env.example .env`
   - `php artisan key:generate`
3. Create SQLite database:
   - `touch database/database.sqlite`
4. Run migrations:
   - `php artisan migrate`
5. Start the server:
   - `php artisan serve`

## Key routes

- Dashboard: `GET /`
- App studio: `GET /apps/{app:slug}`
- Export metadata: `GET /apps/{app:slug}/export`
- Runtime: `GET /run/{app:slug}/{page?}`

## Next steps

- Entity designer.
- Page builder without Node.
- Extensible component renderer.
