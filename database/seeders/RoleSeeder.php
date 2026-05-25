<?php

namespace Database\Seeders;

use App\Enums\Role as AppRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (AppRole::cases() as $role) {
            Role::findOrCreate($role->value, 'web');
        }
    }
}
