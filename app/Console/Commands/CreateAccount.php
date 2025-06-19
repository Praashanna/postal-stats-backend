<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use App\Services\AuthService;

class CreateAccount extends Command
{
    protected $signature = 'create:account 
                            {--name= : The name of the user}
                            {--email= : The email address of the user}
                            {--password= : The password for the user}';

    protected $description = 'Create a new user account';

    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        parent::__construct();
        $this->authService = $authService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating a new user account...');

        // Get user input
        $name = $this->option('name') ?: $this->ask('Enter the user\'s name');
        $email = $this->option('email') ?: $this->ask('Enter the user\'s email address');
        $password = $this->option('password') ?: $this->secret('Enter the user\'s password (min 8 characters)');

        // Validate input
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            $this->error('Validation failed:');
            foreach ($validator->errors()->all() as $error) {
                $this->error('- ' . $error);
            }
            return Command::FAILURE;
        }

        try {
            // Create the user
            $user = $this->authService->createUser([
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ]);

            $this->info('✅ User account created successfully!');
            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $user->id],
                    ['Name', $user->name],
                    ['Email', $user->email],
                    ['Created At', $user->created_at->format('Y-m-d H:i:s')],
                ]
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Failed to create user account: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
