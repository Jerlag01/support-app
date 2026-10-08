<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[Signature('support:create-user {--role= : customer, agent or admin}')]
#[Description('Create a support portal user, e.g. the first admin who can log in to /agent and /admin')]
class CreateSupportUser extends Command
{
    public function handle(): int
    {
        $data = [
            'name' => text('Name', required: true),
            'email' => text('E-mail', required: true),
            'password' => password('Password', required: true, hint: 'At least 8 characters'),
            'role' => $this->option('role') ?? select('Role', [
                UserRole::Admin->value => 'Admin (agent + admin panel)',
                UserRole::Agent->value => 'Agent (handles tickets)',
                UserRole::Customer->value => 'Customer (portal only)',
            ], default: UserRole::Admin->value),
            'locale' => select('Language', config('app.locales'), default: config('app.locale')),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['required', Password::defaults()],
            'role' => ['required', Rule::enum(UserRole::class)],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create($data);

        $this->components->info("Created {$user->role->value} {$user->email}.");

        return self::SUCCESS;
    }
}
