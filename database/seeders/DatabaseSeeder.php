<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $name = config('admin.name');
        $email = config('admin.email');
        $password = config('admin.password');

        if (! is_string($name) || trim($name) === ''
            || ! is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false
            || ! is_string($password) || $password === '') {
            throw new RuntimeException(
                'Set ADMIN_NAME, ADMIN_EMAIL, dan ADMIN_PASSWORD di .env sebelum menjalankan seeder.',
            );
        }

        $this->call(PhoneModelSeeder::class);

        $admin = User::query()->first();

        if ($admin === null) {
            User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ]);
        } elseif ($admin->email !== $email) {
            throw new RuntimeException(
                'Akun admin sudah ada. Gunakan ADMIN_EMAIL yang sama agar seeder tidak membuat akun kedua.',
            );
        }

        if (app()->environment('local') && config('app.seed_demo_data')) {
            $this->call([
                SaleSeeder::class,
                StockSeeder::class,
            ]);
        }
    }
}
