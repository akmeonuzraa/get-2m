<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::create([
            'name'      => 'Admin GED',
            'email'     => 'admin@ged.ma',
            'password'  => Hash::make('password123'),
            'role'      => 'admin',
            'service'   => 'DSI',
            'is_active' => true,
        ]);

        // Responsables
        User::create([
            'name'      => 'Responsable RH',
            'email'     => 'responsable.rh@ged.ma',
            'password'  => Hash::make('password123'),
            'role'      => 'responsable',
            'service'   => 'RH',
            'is_active' => true,
        ]);

        User::create([
            'name'      => 'Responsable DSI',
            'email'     => 'responsable.dsi@ged.ma',
            'password'  => Hash::make('password123'),
            'role'      => 'responsable',
            'service'   => 'DSI',
            'is_active' => true,
        ]);

        // Users normaux
        $services = ['RH', 'DSI', 'Finance', 'Marketing', 'Juridique'];

        foreach (range(1, 10) as $i) {
            User::create([
                'name'      => "Utilisateur {$i}",
                'email'     => "user{$i}@ged.ma",
                'password'  => Hash::make('password123'),
                'role'      => 'user',
                'service'   => $services[array_rand($services)],
                'is_active' => true,
            ]);
        }
    }
}