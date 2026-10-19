# LaunchKit AI

**LaunchKit AI** is a Laravel-based agency operations starter kit for managing clients, projects, invoices, subscription billing, and AI-assisted workflows from one dashboard. The application is designed around PHP, Laravel, MySQL, Tailwind CSS, Alpine.js, Stripe Checkout, and optional OpenAI or Anthropic integrations.

> **Repository setup note:** The `git clone` command below requires a complete LaunchKit AI Git repository containing Laravel's application skeleton (`artisan`, `composer.json`, `package.json`, `bootstrap/`, and `public/`). If you are assembling the application from separately delivered component packages, copy and merge those files into a Laravel project first. Replace the sample repository URL with the actual repository URL before publishing this README.

## Table of Contents

- [Features](#features)
- [System Requirements](#system-requirements)
- [Installation](#installation)
- [Environment Configuration](#environment-configuration)
- [Data Model and Demo Seed Data](#data-model-and-demo-seed-data)
- [Stripe Checkout and Webhook Configuration](#stripe-checkout-and-webhook-configuration)
- [AI Provider Configuration](#ai-provider-configuration)
- [AI Automation Usage](#ai-automation-usage)
- [Dashboard UI Integration](#dashboard-ui-integration)
- [Directory Structure](#directory-structure)
- [Development and Verification](#development-and-verification)
- [Production Deployment Checklist](#production-deployment-checklist)
- [Troubleshooting](#troubleshooting)
- [Security Notes](#security-notes)
- [Official Documentation](#official-documentation)

## Features

The integrated application can provide the following capabilities, depending on which optional modules are installed and configured:

- **Agency dashboard:** responsive dashboard UI, project overview, revenue cards, invoice metrics, and quick actions.
- **Users and roles:** user accounts and role records for `admin`, `client`, and `team_member`.
- **Project management:** project ownership, assignment, status, and progress presentation.
- **Invoices:** invoice records, payment states, client/project relationships, and billing views.
- **Stripe billing:** Basic (`$99/month`), Agency (`$499/month`), and Enterprise (`$999 one-time`) checkout flows.
- **AI automation:** onboarding email drafts, project status summaries, and lead urgency classification through either OpenAI or Anthropic Claude.
- **AI usage records:** operational metadata for AI jobs, where the data-layer module is enabled.
- **Demo seed data:** local/testing demo users and sample agency records, where the demo seeder is installed.

The included foundation is not a substitute for application-specific authorization, tenant isolation, accounting requirements, or a full security review.

## System Requirements

| Dependency | Requirement | Notes |
|---|---|---|
| PHP | **8.2 or newer** | Use a PHP version supported by the Laravel version pinned in `composer.lock`. |
| Composer | Composer 2.x | Installs backend PHP dependencies. |
| Laravel | Use the version declared in `composer.json` | The supplied data-layer notes target Laravel 11/12 conventions; verify package compatibility before a major-version upgrade. |
| MySQL | MySQL 8.0.16+ recommended | The supplied schema uses relational constraints, JSON fields, and MySQL enum columns. |
| Node.js | Node.js 20+ recommended | Use a Node version compatible with the project's `package-lock.json` and Vite version. |
| npm | Included with Node.js | Installs/builds Tailwind CSS and JavaScript assets. |
| Git | Current version | Required for cloning and updating the codebase. |
| Web server | Nginx or Apache for deployment | The document root must point to Laravel's `public/` directory. |
| PHP extensions | `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `PDO`, `pdo_mysql`, `session`, `tokenizer`, and `xml` | Install any additional extensions required by `composer check-platform-reqs`. |

Check the local versions before installing:

```bash
php -v
composer --version
node --version
npm --version
mysql --version
php -m
```

## Installation

### 1. Clone the repository

Replace the placeholder URL with the Git repository URL you own or have access to.

```bash
git clone https://github.com/YOUR-ORGANIZATION/launchkit-ai.git
cd launchkit-ai
```

Confirm that the directory contains `artisan`, `composer.json`, `package.json`, and `.env.example` before continuing.

### 2. Install PHP and frontend dependencies

```bash
composer install
npm install
```

For a production deployment that uses a committed `composer.lock`, run `composer install --no-dev --prefer-dist --optimize-autoloader` during the deployment build instead of a development install.

### 3. Create the environment file

On macOS/Linux:

```bash
cp .env.example .env
```

On Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Open `.env` in the project root—the same directory as `artisan` and `composer.json`. Set `APP_NAME`, `APP_URL`, and database credentials. Keep `.env` private and do not commit it to Git.

### 4. Create a MySQL database

Create a database and a dedicated database user through MySQL or your hosting panel. Example SQL for a local development database:

```sql
CREATE DATABASE launchkit_ai
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
```

Configure the database section in `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=launchkit_ai
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
```

Use a least-privilege database account. Do not use a blank password or a root database account in production.

### 5. Generate the application key

```bash
php artisan key:generate
```

Do not regenerate `APP_KEY` on a deployed application without understanding the impact: encrypted application data and sessions may become unreadable.

### 6. Run migrations and seed data

Verify that the `.env` database values are correct, then run:

```bash
php artisan optimize:clear
php artisan migrate --seed
```

**Migration note:** The LaunchKit data-layer module includes a `users` table migration. If your Laravel skeleton already has a migration that creates `users`, consolidate the schema so only one migration creates that table. Do not blindly delete a migration from an existing, populated database. Review migrations and back up important data before changing schema history.

The demo seeder is intended for local/testing environments. Demo logins must never be used in production. Never run `php artisan migrate:fresh --seed` on a database containing data you need; `migrate:fresh` drops all tables.

### 7. Build frontend assets

```bash
npm run build
```

This should create Vite's production build under `public/build/`. If the dashboard uses Alpine.js from a CDN, ensure that the deployment's Content Security Policy permits that source; alternatively, bundle Alpine locally and remove the duplicate CDN script.

### 8. Link uploaded files (if used)

```bash
php artisan storage:link
```

Run this only if the application uses Laravel's public storage disk for user-accessible uploads.

### 9. Start the local server

```bash
php artisan serve
```

Open the URL printed by Artisan, usually `http://127.0.0.1:8000`. In a separate terminal, you can verify registered routes and database migrations:

```bash
php artisan route:list
php artisan migrate:status
```

`php artisan serve` is intended for local development, not as the production web server.

## Environment Configuration

The exact variables required depend on which optional integrations you have installed. Example configuration:

```dotenv
APP_NAME="LaunchKit AI"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=launchkit_ai
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

# Stripe: use matching test-mode credentials during development
STRIPE_KEY=pk_test_REPLACE_ME
STRIPE_SECRET=sk_test_REPLACE_ME
STRIPE_WEBHOOK_SECRET=whsec_REPLACE_ME

# AI provider: choose openai or anthropic
AI_PROVIDER=openai
AI_TIMEOUT=30
AI_CONNECT_TIMEOUT=5
AI_MAX_OUTPUT_TOKENS=1200

OPENAI_API_KEY=
OPENAI_MODEL=gpt-4.1-mini
OPENAI_BASE_URL=https://api.openai.com/v1

ANTHROPIC_API_KEY=
ANTHROPIC_MODEL=claude-sonnet-5
ANTHROPIC_BASE_URL=https://api.anthropic.com
```

After changing `.env` or service configuration, clear cached configuration:

```bash
php artisan optimize:clear
```

Merge configuration entries into the existing `config/services.php`; do not replace the entire file with a snippet. Production secrets should be configured through the hosting provider's secret/environment-variable manager. Use `APP_DEBUG=false` in production.

## Data Model and Demo Seed Data

The backend module provides the relational foundation used by the dashboard and billing/AI components. Its migrations create the following core tables:

| Table | Purpose |
|---|---|
| `roles` | Fixed application roles: `admin`, `client`, and `team_member`. |
| `users` | User identity, role reference, account details, and billing fields added by the Stripe integration. |
| `projects` | Client-owned projects, assigned team members, project creator, status, and project metadata. |
| `invoices` | Invoice number, client/project references, amount, currency, dates, and payment status. |
| `ai_logs` | Operational AI usage metadata; store only appropriately redacted telemetry. |
| `stripe_webhook_events` | Stripe event IDs and processing state for webhook idempotency/auditing. This table is added by the billing integration. |

### Status enums

- `ProjectStatus`: `pending`, `active`, `completed`.
- `InvoiceStatus`: `unpaid`, `paid`, `refunded`.

The enum-backed models help keep application values consistent, but they do not replace HTTP request validation, authorization, or allowed-transition rules.

### Relationships

- `Role` has many `User` records; each `User` belongs to a `Role`.
- `User` has project relationships named `clientProjects`, `assignedProjects`, and `createdProjects`.
- `Project` belongs to a client, an assigned team member, and a creator; it has many invoices and AI log records.
- `User` has invoices as a client and as the issuer; each `Invoice` belongs to its project, client, and issuer where applicable.
- `AiLog` can be associated with a user and/or project.

### Seeder behavior and demo accounts

The role seeder installs the fixed role records. In `local` or `testing` environments, the demo seeder can create sample users, projects, invoices, and AI usage logs. In other environments, it should seed only the fixed roles and must not create demo logins.

Local-only sample credentials (never use these in production):

| Role | Email | Password |
|---|---|---|
| Admin | `admin@launchkit.test` | `ChangeMe-Admin-2026!` |
| Team member | `harper@launchkit.test` | `ChangeMe-Team-2026!` |
| Client | `olivia.bennett@example.test` | `ChangeMe-Client-2026!` |

These accounts are only expected when `APP_ENV=local` or `APP_ENV=testing` and the demo seeder is installed/enabled. Change the sample credentials before sharing a demo with anyone, and do not change a production environment to `local` to make demo records appear.

**Migration conflict warning:** The supplied data-layer module includes a migration that creates `users`. A standard Laravel application may already have a users-table migration. Consolidate the schema so only one migration creates `users`; do not delete or rewrite migrations in a populated database without reviewing migration history and taking a backup. `php artisan migrate:fresh --seed` drops all tables and is for disposable local databases only.

## Stripe Checkout and Webhook Configuration

Stripe integration is optional. Install and configure it only if the Stripe controllers/routes are present in your application.

| Plan key | Price | Checkout mode | Expected local state after verified payment |
|---|---:|---|---|
| `basic` | $99/month | `subscription` | `subscription_plan=basic`, `subscription_status=active` |
| `agency` | $499/month | `subscription` | `subscription_plan=agency`, `subscription_status=active` |
| `enterprise` | $999 one-time | `payment` | `subscription_plan=enterprise`, `subscription_status=active`, and `enterprise_purchased_at` set |

Plan amounts are defined server-side in `app/Support/LaunchKitBillingPlans.php`. The browser should submit only a recognized plan key, never a price, Stripe Price ID, user ID, or payment outcome.

### 1. Install the Stripe PHP SDK

```bash
composer require stripe/stripe-php
```

### 2. Add Stripe service configuration

Merge the following entry into the array returned by `config/services.php`:

```php
'stripe' => [
    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
],
```

Add the corresponding variables to `.env`:

```dotenv
STRIPE_KEY=pk_test_YOUR_PUBLISHABLE_KEY
STRIPE_SECRET=sk_test_YOUR_SECRET_KEY
STRIPE_WEBHOOK_SECRET=whsec_YOUR_ENDPOINT_SIGNING_SECRET
```

`STRIPE_KEY` is the publishable key. `STRIPE_SECRET` is a server-side secret key. `STRIPE_WEBHOOK_SECRET` is the signing secret for a particular webhook endpoint; it is not the API secret key. Use test-mode keys with a test-mode webhook endpoint, and live credentials only in production.

### 3. Register and verify routes

The supplied route snippets use these endpoints:

| Method | URL | Purpose |
|---|---|---|
| `POST` | `/billing/checkout` | Authenticated user requests checkout for a server-defined plan. |
| `POST` | `/api/stripe/webhook` | Stripe sends signed events to update billing status. |
| `GET` | `/billing/success` | Informational checkout return page. |
| `GET` | `/billing/cancel` | Checkout cancellation page. |

Merge route snippets into the existing route files instead of overwriting them. The checkout route should require authentication and be rate-limited. The webhook route should use a stateless API middleware group and must not require a browser CSRF token. Do not disable CSRF protection globally.

If `routes/api.php` is not registered in your Laravel skeleton, register it in `bootstrap/app.php` using the `api:` argument in `withRouting(...)` while preserving your existing routing configuration. Then check:

```bash
php artisan route:list --path=billing
php artisan route:list --path=api/stripe
```

### 4. Configure Stripe webhook events

For production or a public staging domain, open the Stripe Dashboard's Developers/Webhooks area, create an endpoint pointing to:

```text
https://YOUR_DOMAIN.example/api/stripe/webhook
```

Subscribe to the event types handled by this integration:

- `checkout.session.completed`
- `checkout.session.async_payment_succeeded`
- `invoice.payment_succeeded`
- `invoice.payment_failed`
- `customer.subscription.deleted`

Copy the endpoint's signing secret into `STRIPE_WEBHOOK_SECRET`, then redeploy/refresh configuration. The sample checkout logic uses fixed server-side plan prices: Basic `$99/month`, Agency `$499/month`, and Enterprise `$999 one-time`. Confirm applicable taxes, pricing, refund rules, customer terms, and provider availability for the business's jurisdiction before taking live payments.

### 5. Test webhooks locally

Install and authenticate the Stripe CLI using Stripe's official instructions. With the Laravel development server running, start forwarding events in another terminal:

```bash
stripe listen --forward-to http://127.0.0.1:8000/api/stripe/webhook
```

The CLI prints a temporary/local webhook signing secret beginning with `whsec_`. Use that CLI-provided value in your local `.env` while testing locally; it is separate from the secret shown for the production Dashboard endpoint. Keep the listener running while testing, and don't commit either secret.

### 6. Confirm payment state handling

The application should update the user's billing state only after validating a Stripe-signed webhook event. Do not grant paid access simply because a browser reaches `/billing/success`. Webhook processing should be idempotent because Stripe may retry event delivery. Verify state transitions against the current Stripe subscription/payment object and your application's user record before production deployment.

## AI Provider Configuration

LaunchKit's AI automation service makes **outbound** requests to the selected provider. For the synchronous onboarding-email, project-summary, and lead-classification functions, an inbound AI webhook is not normally required. A route or queued job in your own application may trigger the service; it is not a webhook from OpenAI or Anthropic.

### 1. Configure the provider and credentials

For OpenAI:

```dotenv
AI_PROVIDER=openai
OPENAI_API_KEY=your_server_side_api_key
OPENAI_MODEL=gpt-4.1-mini
OPENAI_BASE_URL=https://api.openai.com/v1
```

For Anthropic Claude:

```dotenv
AI_PROVIDER=anthropic
ANTHROPIC_API_KEY=your_server_side_api_key
ANTHROPIC_MODEL=claude-sonnet-5
ANTHROPIC_BASE_URL=https://api.anthropic.com
```

Only the selected provider's key is required for that configuration. Confirm that your account has access to the model ID you configure; model names and availability may change. Keep all API keys on the server and out of Blade templates and browser JavaScript.

### 2. Merge `config/services.php` settings

For the AI service module, merge the `ai`, `openai`, and `anthropic` configuration arrays from the provided AI configuration snippet into `config/services.php`. Do not overwrite your Stripe or mail configuration. Ensure the configured environment names match the `AiAutomationService` implementation used in your repository.

Then run:

```bash
php artisan optimize:clear
```

### 3. Expected AI behavior

- `generateClientOnboardingEmail($clientData)` produces an email **draft**; it does not send the email.
- `autoSummarizeProjectStatus($projectId)` summarizes project fields and recent update records if the model's `updates()` relationship is configured.
- `classifyLeadUrgency($messageBody)` returns an advisory `high`, `medium`, or `low` label with a proposed reply.
- A local fallback may be returned when the configured key is missing, the provider call fails, or output cannot be parsed.

Add controller-level authorization before summarizing a project so users cannot access another customer's data. Apply rate limits/usage quotas, monitor provider errors, and avoid forwarding unnecessary personal data, credentials, payment data, or confidential client material to an AI provider. If you later implement inbound callbacks or asynchronous provider notifications, create an explicit route and verify its authentication/signature according to that provider's current documentation; do not expose an unauthenticated generic AI endpoint.

## AI Automation Usage

The reusable service lives at `app/Services/AiAutomationService.php`. It uses Laravel's HTTP client, so the provided implementation does not require a separate AI SDK. It supports three workflows:

### Generate a client onboarding email

```php
use App\Services\AiAutomationService;

$email = app(AiAutomationService::class)->generateClientOnboardingEmail([
    'client_name' => 'Jordan Lee',
    'client_email' => 'jordan@example.com',
    'company_name' => 'Northstar Studio',
    'project_name' => 'Website Redesign',
    'project_type' => 'Website design and development',
    'project_goals' => 'Improve mobile usability and increase consultation requests',
    'timeline' => '6 weeks',
    'budget' => '$4,500',
    'next_steps' => 'Schedule the kickoff call and confirm access to brand assets',
]);

// Review the draft before sending it.
$subject = $email['subject'];
$body = $email['body'];
```

The required fields are `client_name` and `project_name`. The service also accepts common aliases such as `name`, `email`, `company`, and `goals`. Input validation failures raise Laravel's `ValidationException` rather than silently substituting fallback content.

### Summarize a project

```php
$report = app(\App\Services\AiAutomationService::class)
    ->autoSummarizeProjectStatus($projectId);

echo $report['summary'];
// $report['fallback'] is true if local summary logic was used.
```

The `Project` model is expected to be `App\Models\Project`. To include project update history, add an `updates()` Eloquent relationship that matches your schema. The service can read up to 10 recent update records whose text field is one of `body`, `content`, `message`, `description`, or `title`. Adjust the relationship/query if your schema uses different model or column names. If no usable update history is available, the service falls back to a summary of the project fields. A missing project remains a `ModelNotFoundException` and should become a 404 response at the controller boundary.

### Classify a lead inquiry

```php
$classification = app(\App\Services\AiAutomationService::class)
    ->classifyLeadUrgency($messageBody);

$urgency = $classification['urgency']; // high, medium, or low
$reason = $classification['reason'];
$replyDraft = $classification['response_template'];
```

The urgency tag is advisory. Review the proposed reply and do not let the label alone trigger destructive or high-impact actions.

### Return values and fallback behavior

Successful calls return an associative array with fields such as `success`, `fallback`, `provider`, and `requested_provider`, plus task-specific data. `fallback=false` means the selected AI provider returned a usable result; `fallback=true` means local deterministic logic was used because a key was missing, a request failed, or the model response could not be parsed. Provider failures should not crash the calling workflow when a fallback is available. Validation errors and missing-project errors remain explicit exceptions.

The service creates drafts and summaries; it does not send onboarding emails automatically. Project summaries must be protected by controller-level authorization, and AI calls should be rate-limited/subject to per-user usage limits. Do not send credentials, payment-card data, or unnecessary sensitive client information to a third-party provider. Disclose AI processing in your privacy notice.

## Dashboard UI Integration

The dashboard template belongs at `resources/views/dashboard.blade.php` and uses Tailwind CSS, Vite, and Alpine.js.

1. Copy the supplied Blade view into `resources/views/dashboard.blade.php`.
2. Ensure `resources/css/app.css` is configured as a Tailwind entry point and the app's Vite configuration builds it.
3. Load Alpine.js only once. If the view includes the Alpine CDN script and your `resources/js/app.js` already initializes Alpine, remove the CDN script and use your bundled version.
4. Enable class-based dark mode. With Tailwind CSS v3, configure `darkMode: 'class'` in `tailwind.config.js`. With Tailwind CSS v4, configure the dark variant in the CSS entry point per your Tailwind setup.
5. Render the view through a Laravel route; do not open the Blade file directly in a browser.

### Dashboard controller data contract

The view can receive these variables from the controller:

| Variable | Expected content |
|---|---|
| `$stats` | Values for `total_revenue`, `revenue_change`, `active_projects`, `active_projects_change`, `outstanding_invoices`, `outstanding_invoices_count`, `ai_tasks_executed`, and `ai_tasks_change`. |
| `$projects` | A collection or array of project records. Eloquent models are supported. Eager-load the client relationship, e.g. `Project::with('client:id,name')->latest()->limit(5)->get()`. |
| `$workspaces` | Optional array of workspace records with `id` and `name`. |
| `$notificationCount` | Optional integer count for the notification badge. |

The view contains presentation demo values when data is omitted. Replace demo statistics with real aggregate queries scoped to the authenticated user's authorized agency/client. The supplied project schema does not persist a `progress` field; the view may estimate progress from status (`pending` 0%, `active` 60%, `completed` 100%) unless you provide a real value or calculate it from milestones. Treat estimated progress as illustrative only.

### Routes the UI expects

Wire up and authorize the endpoints/actions before production use:

- `/dashboard`
- `/projects`, `/projects/{project}`, `/projects/{project}/edit`, and `DELETE /projects/{project}`
- `/invoices`, `/ai-automation`, and `/settings`
- `POST /ai/auto-invoice` to validate the request, create an invoice draft, and require explicit review before delivery
- `/logout` or the logout route provided by your authentication starter kit

The Blade view is a frontend template, not a complete backend implementation of invoice delivery, notifications, authorization, or AI auto-invoice generation. Keep CSRF protection enabled on browser routes and enforce client/tenant ownership for every operation.

## Directory Structure

The exact layout depends on which optional integrations are included in the final repository. The following is the intended high-level structure of an integrated application:

```text
launchkit-ai/
├── app/
│   ├── Enums/                  # ProjectStatus and InvoiceStatus backed enums
│   ├── Http/
│   │   ├── Controllers/        # Dashboard, billing, webhook, and application controllers
│   │   ├── Middleware/         # Request middleware where the app defines custom middleware
│   │   └── Requests/           # Form Request validation classes, if used
│   ├── Models/                 # User, Role, Project, Invoice, AiLog, and related models
│   ├── Services/               # AiAutomationService and reusable business logic
│   └── Support/                # Shared helpers, e.g. LaunchKitBillingPlans
├── bootstrap/                  # Laravel bootstrap and route registration
├── config/                     # Framework and service configuration (services.php)
├── database/
│   ├── factories/              # Model factories, if present
│   ├── migrations/             # Versioned database schema changes
│   └── seeders/                # Roles and local/testing demo data
├── public/                     # Public web root and compiled Vite assets
├── resources/
│   ├── css/                    # Tailwind CSS source styles
│   ├── js/                     # JavaScript entrypoints and frontend behavior
│   └── views/                  # Blade templates, dashboard, and billing pages
├── routes/
│   ├── web.php                 # Browser routes (sessions/CSRF as configured)
│   ├── api.php                 # Stateless API routes/webhooks when registered
│   └── console.php             # Console commands (when used by the skeleton)
├── storage/
│   ├── app/                    # Application-generated files and uploads
│   ├── framework/              # Cache, sessions, views, and other framework files
│   └── logs/                   # Application logs; restrict access
├── tests/                      # Automated unit and feature tests
├── .env.example                # Safe configuration template; no real secrets
├── artisan                     # Laravel CLI entry point
├── composer.json               # PHP dependencies and scripts
├── package.json                # Frontend dependencies and scripts
├── vite.config.js              # Vite build configuration
└── README.md
```

## Development and Verification

Run the automated tests when present:

```bash
php artisan test
```

Useful diagnostics:

```bash
php artisan about
php artisan route:list
php artisan migrate:status
php artisan config:show services
npm run build
```

Do not print or paste complete environment configuration into public issue trackers. If a diagnostic could expose keys or customer information, redact it first.

## Production Deployment Checklist

1. Set `APP_ENV=production`, `APP_DEBUG=false`, and `APP_URL=https://your-domain.example`.
2. Configure secrets in your host's secret manager/environment settings; do not deploy a developer's `.env` file.
3. Point Nginx/Apache's document root at the Laravel `public/` directory, not the repository root.
4. Enable HTTPS and ensure the PHP runtime can make outbound HTTPS requests to Stripe and the selected AI provider.
5. Build assets with `npm ci && npm run build` when a lockfile is committed; deploy only the resulting build assets.
6. Run migrations as a deliberate release step after a backup; do not use `migrate:fresh` in production.
7. Ensure `storage/` and `bootstrap/cache/` are writable by the web/CLI user without granting world-write access.
8. Register the production Stripe webhook URL and use its production signing secret.
9. Configure logging, error monitoring, database backups, and a process for rotating secrets.
10. Add and test Laravel authorization policies, tenant isolation, rate limiting, invoice status transitions, and subscription access rules before selling or onboarding customer organizations.
11. If using queues for slow tasks, configure a real queue driver and keep workers supervised by your hosting platform/process manager.
12. Verify that demo credentials and local-only debug routes are not enabled in production.

After the environment is configured, a typical deployment may run the following commands as part of its release pipeline (review your hosting setup first):

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Use `npm ci` only when a `package-lock.json` is committed. If your route definitions contain unsupported closure routes for route caching, address that before enabling `route:cache`.

## Troubleshooting

### Composer reports a PHP version or missing-extension error

Check the PHP binary used by both your shell and web server:

```bash
php -v
composer check-platform-reqs
php -m
```

Enable required extensions in the correct PHP configuration, especially `pdo_mysql`, `curl`, `mbstring`, `openssl`, `fileinfo`, `dom`, and `xml`. On hosting platforms, the CLI PHP version can differ from the PHP-FPM/Apache version; make sure both meet the repository's requirements, then restart the relevant PHP/web service if needed.

### `could not find driver` or database connection fails

- Confirm `pdo_mysql` is enabled: `php -m` should list `pdo_mysql`.
- Check `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`.
- Confirm the database exists and that the configured user can connect to it.
- Try `127.0.0.1` instead of `localhost` if the host's MySQL socket configuration causes a mismatch.
- Clear cached configuration: `php artisan optimize:clear`.
- Check that PHP CLI and the web server use the same database driver extensions.

### Migration fails because `users` already exists

The project may have both the Laravel skeleton's default users migration and the LaunchKit data-layer migration. Review both files and merge their columns into a single migration for a fresh install. For an existing production database, write a forward migration and back up the database first—do not delete migration history or run `migrate:fresh` to silence the error.

### `storage` or `bootstrap/cache` permission errors

Laravel needs the runtime account to write to these directories. On Linux, set ownership to the correct web/CLI deployment user. For example, only if `www-data` is the correct account on your server:

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rwX storage bootstrap/cache
```

Adjust the user/group for your host. Do **not** use `chmod -R 777`; it grants unnecessarily broad write access. On Windows, grant the account running PHP write access to these directories.

### Vite reports a missing manifest or the dashboard has no styles

Run:

```bash
npm install
npm run build
```

Confirm `public/build/manifest.json` exists and that the web server serves the `public/` directory. In production, ensure Node/npm build steps ran before deployment and that the built `public/build` directory was released with the application.

### CORS errors in a separate frontend

Blade pages served by the same Laravel origin usually do not need CORS configuration. If a separate frontend calls Laravel from another origin, allow only the necessary origin(s), methods, and headers in Laravel's CORS configuration. Do not use a wildcard origin with credentialed cookies. For session-authenticated cross-origin requests, correctly configure trusted domains, session cookie settings, HTTPS, and CSRF protection. After edits, run `php artisan optimize:clear`.

### HTTPS/SSL or outbound TLS errors

- Verify the certificate chain and hostname for your domain.
- Set `APP_URL` to the correct HTTPS URL in production.
- Ensure the PHP runtime has current CA certificates and can make outbound HTTPS requests.
- Confirm the server clock is synchronized; time skew can break TLS and signed requests.
- Do not disable TLS certificate verification to work around a certificate problem.
- If using a reverse proxy/load balancer, configure trusted proxies and secure URL handling according to the Laravel deployment documentation.

### Stripe webhooks return 400 or signature verification fails

- Confirm `STRIPE_WEBHOOK_SECRET` belongs to the exact endpoint receiving the event.
- Use the `whsec_...` secret printed by the Stripe CLI for local forwarding and the Dashboard endpoint secret in production.
- Ensure test credentials are paired with a test-mode endpoint and live credentials with a live-mode endpoint.
- Verify the incoming request body is not modified before signature verification.
- Make sure the endpoint is reachable and routes to `/api/stripe/webhook`.
- Review Laravel logs and Stripe's webhook event delivery page; never log secret keys or full payment details.

### Checkout works but the user's subscription is not active

Do not rely on the success redirect alone. Confirm that Stripe delivered the expected signed event, the webhook returned a successful HTTP response, the user/customer metadata maps to the correct local user, and the event has not been rejected as a duplicate. Check `storage/logs/laravel.log` with sensitive values redacted. Test both recurring subscription and one-time Enterprise purchase flows.

### AI API returns `401`, `403`, `404`, `429`, or times out

- `401`: verify the selected provider's API key and ensure there are no extra spaces or quotes.
- `403`: check account permissions, provider access, restrictions, or billing status.
- `404`: confirm `AI_PROVIDER`, base URL, and model ID.
- `429`: reduce request frequency, apply per-user limits, and review provider quota/billing.
- Timeout: check outbound network access, DNS, TLS certificates, and `AI_TIMEOUT`/`AI_CONNECT_TIMEOUT`.
- Fallback results: confirm the selected provider key is configured and inspect sanitized application logs.

Clear stale config after environment edits using `php artisan optimize:clear`. Never paste API keys or full client messages into support tickets.

### `419 Page Expired` on checkout

For browser POST routes, confirm the form includes `@csrf`, the session cookie is being sent, and the domain/scheme is consistent. Keep CSRF protection enabled for normal browser routes. The Stripe webhook should use the stateless API middleware arrangement described above rather than disabling CSRF protection application-wide.

### `/api/stripe/webhook` returns 404

Confirm that `routes/api.php` exists, is registered in the Laravel routing configuration, and contains the Stripe webhook route. Inspect `php artisan route:list --path=api/stripe`. Avoid adding `/api` a second time if the configured route prefix already supplies it.

### Seeded demo users do not appear

Demo records may only be created when `APP_ENV=local` or `APP_ENV=testing`, by design. Check the seeder and environment used for the database command. Do not change production to `APP_ENV=local` to obtain demo data.

## Security Notes

- Keep credentials in environment variables or a managed secret store. Rotate any key that has been exposed.
- Use HTTPS in production and set `APP_DEBUG=false`.
- Enforce authorization policies and client/tenant scoping on every controller, query, export, and background job. Foreign-key relationships alone do not prevent cross-client data access.
- Never trust plan prices, role IDs, user IDs, invoice states, or payment outcomes supplied by the browser.
- Verify Stripe signatures, make webhook processing idempotent, and grant access based on server-verified billing state.
- Rate-limit checkout and AI endpoints to reduce abuse and unexpected costs.
- Treat AI output as a draft/advisory result. Review generated content before sending it to clients or using it to make consequential decisions.
- Disclose when client content is sent to an external AI provider, and avoid transmitting sensitive data that is not necessary for the task.
- Redact secrets and personal data from logs, and establish retention limits for AI usage records and billing metadata.
- Back up the database before production migrations and test restores regularly.

## Official Documentation

- [Laravel Installation](https://laravel.com/docs/12.x/installation)
- [Laravel Deployment](https://laravel.com/docs/12.x/deployment)
- [Laravel Configuration](https://laravel.com/docs/12.x/configuration)
- [Laravel CSRF Protection](https://laravel.com/docs/12.x/csrf)
- [Stripe Checkout Sessions](https://docs.stripe.com/api/checkout/sessions/create)
- [Stripe Webhooks](https://docs.stripe.com/webhooks)
- [Stripe CLI](https://docs.stripe.com/stripe-cli)
- [OpenAI API Overview](https://developers.openai.com/api/reference/overview)
- [OpenAI Responses API](https://developers.openai.com/api/reference/resources/responses/methods/create)
- [Anthropic Messages API](https://platform.claude.com/docs/en/api/messages)
- [Anthropic Model IDs and Versions](https://platform.claude.com/docs/en/about-claude/models/model-ids-and-versions)

---

**Maintainer note:** Before distributing LaunchKit AI commercially, replace the sample Git URL, confirm the installed module versions and exact config keys, test migrations on a clean database, run feature tests for role/tenant authorization and billing, and complete an end-to-end Stripe test-mode checkout/webhook cycle.
