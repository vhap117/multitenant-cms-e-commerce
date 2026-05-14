<?php

namespace VHAP\Core\Console\Commands;

use Illuminate\Console\Command;

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
     * @param \VHAP\Core\Actions\InstallLandlordAction $action
     * @return int
     */
    public function handle(\VHAP\Core\Actions\InstallLandlordAction $action)
    {
        $this->info('Starting Landlord Setup Pipeline...');

        $name = $this->ask('Super Admin Name', 'System Admin');
        $email = $this->ask('Super Admin Email', 'admin@landlord.local');
        $password = $this->secret('Super Admin Password (leave blank to generate random)');

        if (empty($password)) {
            $password = \Illuminate\Support\Str::password(16);
            $this->info("Generated Password: {$password}");
            $this->warn("Make sure to save this password securely!");
        }

        $payload = [
            'database' => config('database.connections.landlord.database'),
            'name'     => $name,
            'email'    => $email,
            'password' => $password,
        ];

        try {
            $this->info('Running Landlord Database Creation, Migrations, and Admin Provisioning...');
            
            $action->execute($payload);
            
            $this->info('Landlord Environment successfully installed and provisioned!');
            
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Landlord Setup failed: ' . $e->getMessage());
            
            return Command::FAILURE;
        }
    }
}
