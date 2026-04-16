<?php

namespace VHAP\Core\Console\Commands;

use Illuminate\Console\Command;
use VHAP\Core\Models\LandlordUser;
use Illuminate\Support\Facades\Hash;

class InstallLandlordCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'landlord:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates the initial Landlord Super Admin user.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting Landlord Setup...');

        $name = $this->ask('Super Admin Name', 'System Admin');
        $email = $this->ask('Super Admin Email', 'admin@landlord.local');
        $password = $this->secret('Super Admin Password (leave blank to generate random)');

        if (empty($password)) {
            $password = \Illuminate\Support\Str::password(16);
            $this->info("Generated Password: {$password}");
            $this->warn("Make sure to save this password securely!");
        }

        $user = LandlordUser::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
            ]
        );

        if ($user->wasRecentlyCreated) {
            $this->info('Super Admin user created successfully.');
        } else {
            $this->info('A user with this email already exists.');
        }

        return Command::SUCCESS;
    }
}
