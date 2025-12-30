<?php

namespace App\Console\Commands;

use App\Models\AdminUser;
use Illuminate\Console\Command;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create-user {email : Admin email address} {--name=}';

    protected $description = 'Create or activate an admin user with the provided email address';

    public function handle(): int
    {
        $email = strtolower($this->argument('email'));
        $name = $this->option('name');

        $admin = AdminUser::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'is_active' => true,
            ],
        );

        $this->info("Admin user ready: {$admin->email}");

        return self::SUCCESS;
    }
}
