<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SUPER_ADMIN_EMAIL', 'milicofelix@gmail.com');
        $password = env('SUPER_ADMIN_PASSWORD');
        $resetPassword = filter_var(env('SUPER_ADMIN_RESET_PASSWORD', false), FILTER_VALIDATE_BOOL);

        /** @var User&Model $user */
        $user = User::query()->firstOrNew(['email' => $email]);

        if (! $user->exists && blank($password)) {
            throw new RuntimeException('Defina SUPER_ADMIN_PASSWORD no .env para criar o dono do software em produção.');
        }

        $user->forceFill([
            'name' => env('SUPER_ADMIN_NAME', 'Adriano F Freitas'),
            'role' => User::ROLE_SUPER_ADMIN,
            'wash_location_id' => null,
            'is_active' => true,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ]);

        if (! $user->exists || $resetPassword) {
            if (blank($password)) {
                throw new RuntimeException('Defina SUPER_ADMIN_PASSWORD no .env para resetar a senha do dono do software.');
            }

            $user->password = Hash::make($password);
        }

        $user->save();
    }
}
