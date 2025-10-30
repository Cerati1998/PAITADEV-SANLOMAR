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
        ])->assignRole('Super');

        User::factory()->create([
            'name' => 'Biuler Padilla',
            'email' => 'biuler.padilla@sanlomargroup.com',
            'tipoDoc' => 1,
            'numDoc' => '09983273',
            'password' => bcrypt('BPadill@2027*')
        ])->assignRole('Admin');
        
        User::factory()->create([
            'name' => 'Solange Chuna',
            'email' => 'solanche.chuna@sanlomargroup.com',
            'tipoDoc' => 1,
            'numDoc' => '72803092',
            'password' => bcrypt('Sol@ncheCh2025*')
        ])->assignRole('Supervisor');
    }
}
