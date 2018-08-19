<?php

use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::createFromValues('John Doe', 'demo@demo.com', 'password');

        // Register the user seeder
        $this->call('UsersTableSeeder');
    }
}
