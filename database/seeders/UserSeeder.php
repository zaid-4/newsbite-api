<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as FakerFactory;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {

        User::factory(1)->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'test@1234'
        ]);
    }
}
