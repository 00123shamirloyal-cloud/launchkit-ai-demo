<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'label' => 'Administrator',
                'description' => 'Full access to agency configuration and client work.',
            ],
            [
                'name' => 'client',
                'label' => 'Client',
                'description' => 'Can access only their own projects, invoices, and approved deliverables.',
            ],
            [
                'name' => 'team_member',
                'label' => 'Team Member',
                'description' => 'Works on assigned projects and related delivery tasks.',
            ],
        ];

        foreach ($roles as $attributes) {
            Role::query()->updateOrCreate(
                ['name' => $attributes['name']],
                [
                    'label' => $attributes['label'],
                    'description' => $attributes['description'],
                ],
            );
        }
    }
}
