# Droplinks Builder

Low-code platform for building OutSystems-like apps using Laravel, Livewire, and SQLite only (no Node.js).

## Current status

- App studio covering versions, entities, pages, components, data sources, actions, workflows, roles, records, and deployments.
- Metadata import/export including entity permissions and webhooks.
- Runtime renderer for text, table, form, chart, KPI, kanban, timeline, file upload, and record detail components.
- Data sources support entity, static, join, and REST connectors with filters, sort, and caching.

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
