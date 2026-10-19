<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed fixed authorization roles in every environment. Demo accounts and
        // sample records are only created in local/testing environments.
        $this->call(RoleSeeder::class);

        if ($this->command?->getLaravel()->environment(['local', 'testing'])) {
            $this->call(LaunchKitDemoSeeder::class);
        }
    }
}
