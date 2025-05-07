<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->createUserIfNotExists([
            "name" => "Test User",
            "email" => "test@example.com",
        ]);

        $this->createRoles();
    }

    /**
     * Create a user if it does not already exist.
     *
     * @param array $userData
     * @return void
     */
    protected function createUserIfNotExists(array $userData): void
    {
        $email = $userData['email'];

        if (!User::where('email', $email)->exists()) {
            User::factory()->create($userData);
        } else {
            $this->command->info("User with email {$email} already exists. Skipping creation.");
        }
    }

    /**
     * Create predefined roles if they do not already exist.
     *
     * @return void
     */
    protected function createRoles(): void
    {
        $roles = [
            [
                'name' => '學員',
                'slug' => 'student',
                'note' => 'A student enrolled in courses.',
                'sort' => 1,
                'status' => true,
            ],
            [
                'name' => '教師',
                'slug' => 'teacher',
                'note' => 'A teacher delivering course content.',
                'sort' => 2,
                'status' => true,
            ],
            [
                'name' => '助教',
                'slug' => 'assistant',
                'note' => 'An assistant helping students and teachers.',
                'sort' => 3,
                'status' => true,
            ],
            [
                'name' => '業務',
                'slug' => 'sales',
                'note' => 'A sales representative managing courses.',
                'sort' => 4,
                'status' => true,
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['slug' => $role['slug']], // Check for existing role by slug
                $role // Attributes to create if not exists
            );
        }
    }
}
