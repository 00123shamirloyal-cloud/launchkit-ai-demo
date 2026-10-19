<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Enums\ProjectStatus;
use App\Models\AiLog;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class LaunchKitDemoSeeder extends Seeder
{
    public function run(): void
    {
        $roles = Role::query()->get()->keyBy('name');

        $admin = $this->upsertUser($roles->get('admin'), [
            'name' => 'Morgan Blake',
            'email' => 'admin@launchkit.test',
            'password' => 'ChangeMe-Admin-2026!',
        ]);

        $teamMembers = [
            $this->upsertUser($roles->get('team_member'), [
                'name' => 'Harper Chen',
                'email' => 'harper@launchkit.test',
                'password' => 'ChangeMe-Team-2026!',
            ]),
            $this->upsertUser($roles->get('team_member'), [
                'name' => 'Jordan Ellis',
                'email' => 'jordan@launchkit.test',
                'password' => 'ChangeMe-Team-2026!',
            ]),
            $this->upsertUser($roles->get('team_member'), [
                'name' => 'Casey Rivera',
                'email' => 'casey@launchkit.test',
                'password' => 'ChangeMe-Team-2026!',
            ]),
        ];

        $clients = [
            $this->upsertUser($roles->get('client'), [
                'name' => 'Olivia Bennett',
                'email' => 'olivia.bennett@example.test',
                'password' => 'ChangeMe-Client-2026!',
            ]),
            $this->upsertUser($roles->get('client'), [
                'name' => 'Ethan Walker',
                'email' => 'ethan.walker@example.test',
                'password' => 'ChangeMe-Client-2026!',
            ]),
            $this->upsertUser($roles->get('client'), [
                'name' => 'Ava Patel',
                'email' => 'ava.patel@example.test',
                'password' => 'ChangeMe-Client-2026!',
            ]),
            $this->upsertUser($roles->get('client'), [
                'name' => 'Noah Thompson',
                'email' => 'noah.thompson@example.test',
                'password' => 'ChangeMe-Client-2026!',
            ]),
        ];

        $today = now()->startOfDay();
        $projectDefinitions = [
            [
                'slug' => 'northstar-ecommerce-rebuild',
                'name' => 'Northstar E-commerce Rebuild',
                'description' => 'Replatform a growing retail brand with a faster storefront, improved checkout, and inventory sync.',
                'client' => 0,
                'team' => 0,
                'status' => ProjectStatus::Active,
                'budget' => '18500.00',
                'starts_at' => $today->copy()->subDays(28)->toDateString(),
                'due_date' => $today->copy()->addDays(18)->toDateString(),
                'completed_at' => null,
            ],
            [
                'slug' => 'orchid-dental-website',
                'name' => 'Orchid Dental Website',
                'description' => 'A responsive clinic website with service pages, lead forms, and appointment-request workflow.',
                'client' => 1,
                'team' => 1,
                'status' => ProjectStatus::Completed,
                'budget' => '7200.00',
                'starts_at' => $today->copy()->subDays(90)->toDateString(),
                'due_date' => $today->copy()->subDays(48)->toDateString(),
                'completed_at' => $today->copy()->subDays(47)->setTime(16, 30),
            ],
            [
                'slug' => 'atlas-fitness-membership-portal',
                'name' => 'Atlas Fitness Membership Portal',
                'description' => 'Member dashboard for class schedules, membership renewals, and trainer profiles.',
                'client' => 2,
                'team' => 2,
                'status' => ProjectStatus::Active,
                'budget' => '14000.00',
                'starts_at' => $today->copy()->subDays(14)->toDateString(),
                'due_date' => $today->copy()->addDays(36)->toDateString(),
                'completed_at' => null,
            ],
            [
                'slug' => 'solace-wellness-brand-refresh',
                'name' => 'Solace Wellness Brand Refresh',
                'description' => 'Brand-aligned landing pages, content refresh, and a conversion-focused lead funnel.',
                'client' => 3,
                'team' => 0,
                'status' => ProjectStatus::Pending,
                'budget' => '6800.00',
                'starts_at' => $today->copy()->addDays(5)->toDateString(),
                'due_date' => $today->copy()->addDays(45)->toDateString(),
                'completed_at' => null,
            ],
            [
                'slug' => 'harbor-analytics-dashboard',
                'name' => 'Harbor Analytics Dashboard',
                'description' => 'Internal reporting dashboard consolidating sales, campaign, and customer metrics.',
                'client' => 0,
                'team' => 1,
                'status' => ProjectStatus::Completed,
                'budget' => '11200.00',
                'starts_at' => $today->copy()->subDays(75)->toDateString(),
                'due_date' => $today->copy()->subDays(32)->toDateString(),
                'completed_at' => $today->copy()->subDays(30)->setTime(11, 0),
            ],
            [
                'slug' => 'evergreen-legal-website',
                'name' => 'Evergreen Legal Website',
                'description' => 'Professional services website with practice-area pages, accessible design, and consultation forms.',
                'client' => 1,
                'team' => null,
                'status' => ProjectStatus::Pending,
                'budget' => '6400.00',
                'starts_at' => $today->copy()->addDays(12)->toDateString(),
                'due_date' => $today->copy()->addDays(55)->toDateString(),
                'completed_at' => null,
            ],
        ];

        $projects = [];

        foreach ($projectDefinitions as $definition) {
            $project = Project::query()->firstOrNew(['slug' => $definition['slug']]);
            $project->fill([
                'name' => $definition['name'],
                'slug' => $definition['slug'],
                'description' => $definition['description'],
                'status' => $definition['status'],
                'budget' => $definition['budget'],
                'currency' => 'USD',
                'starts_at' => $definition['starts_at'],
                'due_date' => $definition['due_date'],
                'completed_at' => $definition['completed_at'],
            ]);
            $project->client()->associate($clients[$definition['client']]);
            $project->createdBy()->associate($admin);

            if ($definition['team'] === null) {
                $project->assignedTo()->dissociate();
            } else {
                $project->assignedTo()->associate($teamMembers[$definition['team']]);
            }

            $project->save();
            $projects[$definition['slug']] = $project;
        }

        $invoiceDefinitions = [
            [
                'number' => 'LK-2026-0001',
                'project' => 'northstar-ecommerce-rebuild',
                'client' => 0,
                'description' => 'Northstar e-commerce rebuild — kickoff deposit',
                'amount' => '9250.00',
                'status' => InvoiceStatus::Paid,
                'issued_days_ago' => 28,
                'due_days_from_now' => -14,
                'paid_days_ago' => 12,
                'refunded_days_ago' => null,
            ],
            [
                'number' => 'LK-2026-0002',
                'project' => 'northstar-ecommerce-rebuild',
                'client' => 0,
                'description' => 'Northstar e-commerce rebuild — development milestone',
                'amount' => '5500.00',
                'status' => InvoiceStatus::Unpaid,
                'issued_days_ago' => 3,
                'due_days_from_now' => 14,
                'paid_days_ago' => null,
                'refunded_days_ago' => null,
            ],
            [
                'number' => 'LK-2026-0003',
                'project' => 'orchid-dental-website',
                'client' => 1,
                'description' => 'Orchid Dental website — final delivery',
                'amount' => '7200.00',
                'status' => InvoiceStatus::Paid,
                'issued_days_ago' => 75,
                'due_days_from_now' => -60,
                'paid_days_ago' => 62,
                'refunded_days_ago' => null,
            ],
            [
                'number' => 'LK-2026-0004',
                'project' => 'atlas-fitness-membership-portal',
                'client' => 2,
                'description' => 'Atlas Fitness portal — phase one',
                'amount' => '7000.00',
                'status' => InvoiceStatus::Unpaid,
                'issued_days_ago' => 5,
                'due_days_from_now' => 7,
                'paid_days_ago' => null,
                'refunded_days_ago' => null,
            ],
            [
                'number' => 'LK-2026-0005',
                'project' => 'solace-wellness-brand-refresh',
                'client' => 3,
                'description' => 'Solace Wellness — discovery workshop cancellation adjustment',
                'amount' => '450.00',
                'status' => InvoiceStatus::Refunded,
                'issued_days_ago' => 32,
                'due_days_from_now' => -18,
                'paid_days_ago' => null,
                'refunded_days_ago' => 15,
            ],
            [
                'number' => 'LK-2026-0006',
                'project' => 'harbor-analytics-dashboard',
                'client' => 0,
                'description' => 'Harbor analytics dashboard — final invoice',
                'amount' => '11200.00',
                'status' => InvoiceStatus::Paid,
                'issued_days_ago' => 45,
                'due_days_from_now' => -30,
                'paid_days_ago' => 33,
                'refunded_days_ago' => null,
            ],
        ];

        foreach ($invoiceDefinitions as $definition) {
            $invoice = Invoice::query()->firstOrNew([
                'invoice_number' => $definition['number'],
            ]);
            $issuedAt = $today->copy()->subDays($definition['issued_days_ago']);
            $dueAt = $today->copy()->addDays($definition['due_days_from_now']);

            $invoice->fill([
                'invoice_number' => $definition['number'],
                'description' => $definition['description'],
                'amount' => $definition['amount'],
                'currency' => 'USD',
                'status' => $definition['status'],
                'issue_date' => $issuedAt->toDateString(),
                'due_date' => $dueAt->toDateString(),
                'paid_at' => $definition['paid_days_ago'] === null
                    ? null
                    : $today->copy()->subDays($definition['paid_days_ago'])->setTime(10, 15),
                'refunded_at' => $definition['refunded_days_ago'] === null
                    ? null
                    : $today->copy()->subDays($definition['refunded_days_ago'])->setTime(14, 0),
                'line_items' => [[
                    'description' => $definition['description'],
                    'quantity' => 1,
                    'unit_price' => $definition['amount'],
                    'total' => $definition['amount'],
                ]],
                'notes' => 'Demonstration invoice. Replace with approved billing data before production use.',
            ]);
            $invoice->project()->associate($projects[$definition['project']]);
            $invoice->client()->associate($clients[$definition['client']]);
            $invoice->issuedBy()->associate($admin);
            $invoice->save();
        }

        $actors = [$admin, ...$teamMembers, ...$clients];

        $logDefinitions = [
            ['provider' => 'openai', 'model' => 'gpt-4.1-mini', 'action' => 'proposal.generate', 'status' => 'succeeded', 'input_tokens' => 1420, 'output_tokens' => 860, 'cost' => '0.003840', 'latency_ms' => 1840, 'user' => 0, 'project' => 'northstar-ecommerce-rebuild', 'days_ago' => 1, 'metadata' => ['feature' => 'proposal_builder', 'redacted' => true]],
            ['provider' => 'anthropic', 'model' => 'claude-3-5-haiku-latest', 'action' => 'copy.rewrite', 'status' => 'succeeded', 'input_tokens' => 740, 'output_tokens' => 310, 'cost' => '0.001520', 'latency_ms' => 990, 'user' => 1, 'project' => 'orchid-dental-website', 'days_ago' => 2, 'metadata' => ['feature' => 'content_assistant', 'redacted' => true]],
            ['provider' => 'openai', 'model' => 'gpt-4.1-mini', 'action' => 'project.brief_summarize', 'status' => 'succeeded', 'input_tokens' => 2110, 'output_tokens' => 430, 'cost' => '0.002910', 'latency_ms' => 1560, 'user' => 2, 'project' => 'atlas-fitness-membership-portal', 'days_ago' => 3, 'metadata' => ['feature' => 'project_workspace', 'redacted' => true]],
            ['provider' => 'openai', 'model' => 'gpt-4.1-mini', 'action' => 'seo.meta_generate', 'status' => 'succeeded', 'input_tokens' => 520, 'output_tokens' => 270, 'cost' => '0.001130', 'latency_ms' => 810, 'user' => 0, 'project' => 'evergreen-legal-website', 'days_ago' => 4, 'metadata' => ['feature' => 'seo_assistant', 'redacted' => true]],
            ['provider' => 'anthropic', 'model' => 'claude-3-5-haiku-latest', 'action' => 'proposal.generate', 'status' => 'failed', 'input_tokens' => 0, 'output_tokens' => 0, 'cost' => '0.000000', 'latency_ms' => 2050, 'user' => 0, 'project' => 'solace-wellness-brand-refresh', 'days_ago' => 5, 'metadata' => ['feature' => 'proposal_builder', 'redacted' => true, 'retryable' => true], 'error_code' => 'PROVIDER_TIMEOUT'],
            ['provider' => 'openai', 'model' => 'gpt-4.1-mini', 'action' => 'email.draft', 'status' => 'succeeded', 'input_tokens' => 680, 'output_tokens' => 340, 'cost' => '0.001410', 'latency_ms' => 1060, 'user' => 1, 'project' => 'orchid-dental-website', 'days_ago' => 7, 'metadata' => ['feature' => 'client_communications', 'redacted' => true]],
        ];

        foreach ($logDefinitions as $index => $definition) {
            $log = AiLog::query()->firstOrNew([
                'request_id' => 'demo-request-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
            ]);
            $log->fill([
                'provider' => $definition['provider'],
                'model' => $definition['model'],
                'action' => $definition['action'],
                'status' => $definition['status'],
                'input_tokens' => $definition['input_tokens'],
                'output_tokens' => $definition['output_tokens'],
                'cost' => $definition['cost'],
                'request_id' => 'demo-request-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'latency_ms' => $definition['latency_ms'],
                'metadata' => $definition['metadata'],
                'error_code' => $definition['error_code'] ?? null,
            ]);
            $log->user()->associate($actors[$definition['user']]);
            $log->project()->associate($projects[$definition['project']]);
            $log->created_at = now()->subDays($definition['days_ago']);
            $log->save();
        }
    }

    /**
     * Create/update demo users without exposing role_id to mass assignment.
     * These known demo passwords are for local previews only and must be changed.
     *
     * @param array{name: string, email: string, password: string} $attributes
     */
    private function upsertUser(Role $role, array $attributes): User
    {
        $user = User::query()->firstOrNew(['email' => $attributes['email']]);
        $user->name = $attributes['name'];
        $user->password = $attributes['password'];
        $user->is_active = true;
        $user->role()->associate($role);
        $user->save();

        return $user;
    }
}
