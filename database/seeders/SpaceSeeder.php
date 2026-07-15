<?php

namespace Database\Seeders;

use App\Models\Space;
use App\Models\User;
use Illuminate\Database\Seeder;

class SpaceSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();

        $spaces = [
            ['name' => 'Espace DSI',       'description' => 'Espace collaboratif de la DSI',       'type' => 'public'],
            ['name' => 'Espace RH',        'description' => 'Espace collaboratif des Ressources Humaines', 'type' => 'public'],
            ['name' => 'Espace Finance',   'description' => 'Documents financiers et budgets',      'type' => 'private'],
            ['name' => 'Espace Marketing', 'description' => 'Campagnes et supports marketing',      'type' => 'public'],
            ['name' => 'Projet GED',       'description' => 'Espace dédié au projet GED 2026',      'type' => 'private'],
        ];

        foreach ($spaces as $data) {
            $space = Space::create([
                'name'        => $data['name'],
                'description' => $data['description'],
                'type'        => $data['type'],
                'created_by'  => $admin->id,
            ]);

            // Ajouter l'admin comme membre
            $space->members()->attach($admin->id, ['role' => 'admin']);

            // Ajouter quelques users aléatoires
            $users = User::where('id', '!=', $admin->id)->inRandomOrder()->take(3)->get();
            foreach ($users as $user) {
                $space->members()->attach($user->id, ['role' => 'contributeur']);
            }
        }
    }
}