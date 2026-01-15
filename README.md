# Droplinks Builder

Low-code platform for building OutSystems-like apps using Laravel, Livewire, and SQLite only (no Node.js).

## Current status

- Early dashboard for creating apps and managing versions.
- Metadata data model for entities, pages, components, and data sources.
- Initial runtime route to render built app pages.

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
- Runtime: `GET /run/{app:slug}/{page?}`

## Next steps

- Entity designer.
- Page builder without Node.
- Extensible component renderer.
