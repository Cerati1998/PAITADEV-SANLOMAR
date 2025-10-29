<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Eddy Barranzuela',
            'email' => 'demo@inspectec.cloud',
            'tipoDoc' => 1,
            'numDoc' => '71431466',
            'password' => bcrypt('12345678')
        ]);
    }
}
