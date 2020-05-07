<?php

namespace App\Console\Commands;

use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Console\Command;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create {login} {password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create User';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $registrate = new RegisterController();

        $requestArray['login'] = $this->argument('login');
        $requestArray['password'] = $this->argument('password');

        $registrate->register($requestArray);

        return true;
    }
}
