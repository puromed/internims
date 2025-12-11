---
description: Repository Information Overview
alwaysApply: true
---

# Internship Management System Information

## Summary
A modern Laravel 12 web application for managing student internships, placements, and logbooks. Built with Livewire 3 for real-time interactivity, featuring role-based access (students, faculty, admin), authentication via Fortify, and comprehensive testing with Pest.

## Structure
**Main directories**:
- `app/` - Laravel application code (Models, Controllers, Livewire components, Policies)
- `resources/` - Frontend assets (Blade templates, CSS, JavaScript, views)
- `routes/` - Web routing configuration
- `database/` - Migrations, seeders, factories
- `tests/` - Feature and unit tests
- `config/` - Configuration files
- `bootstrap/` - Application bootstrap files

## Language & Runtime
**Language**: PHP  
**Version**: 8.2+ (from composer.json requirement)  
**Build System**: Laravel Artisan  
**Package Manager**: Composer (PHP), npm (Node.js)

## Dependencies

**Main Dependencies**:
- `laravel/framework` (^12.0) - Web framework
- `livewire/volt` (^1.7.0) - Single-file Livewire components
- `livewire/flux` (^2.9.0) - UI component library for Livewire
- `laravel/fortify` (^1.30) - Authentication scaffolding
- `laravel/tinker` (^2.10.1) - Interactive shell

**Development Dependencies**:
- `pestphp/pest` (^4.1) - Testing framework
- `pestphp/pest-plugin-laravel` (^4.0) - Laravel testing utilities
- `laravel/pint` (^1.24) - Code formatter
- `laravel/sail` (^1.41) - Docker development environment
- `laravel/boost` (^1.8) - Development tools
- `mockery/mockery` (^1.6) - Mocking library
- `fakerphp/faker` (^1.23) - Fake data generation

**Frontend Dependencies**:
- `tailwindcss` (^4.0.7) - CSS framework
- `alpinejs` (^3.15.2) - Lightweight JavaScript framework
- `vite` (^7.0.4) - Build tool
- `laravel-vite-plugin` (^2.0) - Laravel integration

## Build & Installation

**Setup**:
```bash
composer setup
```

**Development server** (runs Laravel, queue listener, and Vite simultaneously):
```bash
composer run dev
```

**Build for production**:
```bash
npm run build
```

**Run migrations**:
```bash
php artisan migrate
```

## Testing

**Framework**: Pest (PHPUnit-based)  
**Test Location**: `tests/` directory (Feature and Unit subdirectories)  
**Naming Convention**: `*Test.php` files  
**Configuration**: `phpunit.xml`  
**Test Environment**: SQLite in-memory database with testing configuration

**Run Tests**:
```bash
composer test
```

Test suites include: authentication, dashboard, settings, two-factor authentication, eligibility, placement, and logbook features.

## Database
**Default**: SQLite (file-based `database/database.sqlite`)  
**Supported**: MySQL, PostgreSQL (configurable via DB_CONNECTION in .env)  
**Features**: Migrations in `database/migrations/`, factories in `database/factories/`, seeders in `database/seeders/`

## Main Application Features
- **Authentication**: Email/password with two-factor authentication, email verification
- **Role-Based Access**: Students, faculty, admin roles with middleware enforcement
- **Student Features**: Dashboard, eligibility documents, placement tracking, logbook entries
- **Faculty Features**: Dashboard, logbook review and management
- **User Settings**: Profile management, password updates, appearance preferences

## Code Style & Quality
**Code Formatter**: Laravel Pint (`vendor/bin/pint --dirty`)  
**Entry Point**: `public/index.php`  
**Frontend Entry**: `resources/js/app.js`, `resources/css/app.css`
