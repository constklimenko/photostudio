# Laravel Project — Agents Guide

## Project Structure

```
fotoskazka/
├── app/
│   ├── Http/        # Controllers, Middleware, Requests
│   ├── Models/      # Eloquent models
│   └── Providers/   # Service providers
├── bootstrap/       # App bootstrap & cache
├── config/          # All config files
├── database/        # Migrations, factories, seeders
├── routes/
│   ├── web.php      # Web routes
│   └── console.php  # Artisan commands
├── resources/       # Views (Blade), assets (JS/CSS), lang
├── storage/         # Logs, cache, uploaded files
├── tests/
│   ├── Feature/     # Feature tests
│   ├── Unit/        # Unit tests
│   └── TestCase.php
├── public/          # Web server document root
├── composer.json
├── package.json
├── vite.config.js
└── phpunit.xml
```

## Common Commands

| Action | Command |
|---|---|
| Serve locally | `php artisan serve` |
| Run all tests | `php artisan test` or `composer test` |
| Run tests (parallel) | `php artisan test --parallel` |
| Run a specific test file | `php artisan test tests/Feature/ExampleTest.php` |
| Run a specific test method | `php artisan test --filter=test_name` |
| Lint / style fix | `./vendor/bin/pint` |
| Lint (dry-run) | `./vendor/bin/pint --test` |
| Static analysis | `./vendor/bin/phpstan analyse` (if installed) |
| Dev server + queue + logs | `composer dev` |
| Migrate | `php artisan migrate` |
| Fresh migrate with seed | `php artisan migrate:fresh --seed` |
| Create migration | `php artisan make:migration create_xxx_table` |
| Create model + migration + factory + controller | `php artisan make:model Xxx -mfsc` |
| Create controller | `php artisan make:controller XxxController` |
| Tinker (REPL) | `php artisan tinker` |
| Clear cache | `php artisan optimize:clear` |
| Queue work | `php artisan queue:work` |
| Make observer | `php artisan make:observer XxxObserver --model=Xxx` |

## Code Conventions

- **PHP**: Laravel 13, PHP 8.3+, strict types recommended
- **Style**: PSR-12 via Laravel Pint (`./vendor/bin/pint`)
- **Naming**:
  - Models: singular, StudlyCase (`User`, `BlogPost`)
  - Controllers: singular, StudlyCase, `XxxController`
  - Migrations: snake_case, `create_xxx_table`
  - Routes: camelCase for URI, kebab-case for named routes
  - Methods: camelCase
  - Database tables: snake_case, plural (`blog_posts`)
  - Columns: snake_case (`created_at`, `is_active`)
- **Routes**: Define in `routes/web.php` using the Route facade
- **Validation**: Use Form Request classes (`php artisan make:request StoreXxxRequest`)
- **Blade templates**: Stored in `resources/views/`, use `.blade.php` extension
- **Testing**: PHPUnit with `Tests\TestCase` base class. Feature tests for HTTP layer, Unit tests for isolated logic.
- **Factories**: Use model factories for test data (`php artisan make:factory XxxFactory --model=Xxx`)
- **Middleware**: Custom middleware in `app/Http/Middleware/`
- **Services**: Business logic should live in dedicated service classes in `app/Services/`
- **No comments in code** unless absolutely necessary — let the code speak

## Environment

- `.env` file is **not** committed (only `.env.example` is)
- To regenerate app key: `php artisan key:generate`
- Database: check `.env` for `DB_*` values (default SQLite)

## Testing

- Run tests: `composer test` or `php artisan test`
- CI test command: `php artisan config:clear && php artisan test`
- Write tests under `tests/Feature/` for HTTP routes, `tests/Unit/` for isolated logic
- Use `RefreshDatabase` trait when tests modify the database

## Useful Artisan Shortcuts

```bash
# Make full CRUD resource
php artisan make:model Post -mfsc

# Make a single-action controller
php artisan make:controller InvokableController --invokable

# List all routes
php artisan route:list

# List registered commands
php artisan list
```

## Before Committing

1. Run `composer test` to ensure all tests pass
2. Run `./vendor/bin/pint` to auto-fix code style
3. Check `git diff` for unintended changes
