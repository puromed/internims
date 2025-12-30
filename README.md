# InternIMS - Internship Management System

InternIMS is a modern, reactive internship management system designed for UiTM. It streamlines the process of eligibility verification, placement registration, and logbook tracking for students, faculty, and administrators.

---

##  Quick Setup (Docker / Laravel Sail)

This project uses [Laravel Sail](https://laravel.com/docs/sail) for a consistent development environment.

### 1. Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop) installed and running.

### 2. Environment Setup

```bash
cp .env.example .env
```

### 3. Start the Application

```bash
./vendor/bin/sail up -d
```

*Note: The first time you run this, it will build the containers, which may take a few minutes.*

### 4. Install Dependencies & Generate Key

```bash
./vendor/bin/sail composer install
./vendor/bin/sail npm install
./vendor/bin/sail php artisan key:generate
```

### 5. Run Migrations & Seeders

```bash
./vendor/bin/sail php artisan migrate --seed
```

### 6. Build Assets

```bash
./vendor/bin/sail npm run dev
```

The application will be available at: **[http://localhost](http://localhost)**

Mailpit will be available at: **[http://localhost:8025](http://localhost:8025)**

---

##  Seeded Credentials

You can log in with the following accounts for testing:

| Role | Email | Password |
|------|-------|----------|
| **Admin** | `admin@example.com` | `password` |
| **Faculty** | `faculty@example.com` | `password` |
| **Student** | `student@example.com` | `password` |

---

##  Branding & Features

### Custom Branding

- **UiTM Identity**: Integrated UiTM logos in sidebar, header, auth pages, and email templates.
- **Dark Mode Support**: Dynamic logo switching and high-contrast UI tokens.

### Secure Authentication

- **OAuth 2.0**: Support for Google and Microsoft (UiTM iSiswa) logins.
- **Email Restriction**: New registrations are strictly restricted to UiTM email domains (`@isiswa.uitm.edu.my`, `@student.uitm.edu.my`, `@uitm.edu.my`).

### System Intelligence

- **Automated Semester Detection**: Logic to auto-detect academic semesters based on dates (March-August: Sem 1, Oct-Feb: Sem 2).
- **Proactive Notifications**: Multi-channel (Email + Web) welcoming flow and deadline reminders.

---

##  OAuth Configuration

To enable Google/Microsoft logins, add the following to your `.env`:

```env
# Google
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...

# Microsoft
MICROSOFT_CLIENT_ID=...
MICROSOFT_CLIENT_SECRET=...
```

For a detailed technical overview of system flows, see `SYSTEM_DOCUMENTATION.md`.
