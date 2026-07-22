<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\Space;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin Demo',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'service' => 'Direction',
            'is_active' => true,
        ]);

        $responsable = User::factory()->create([
            'name' => 'Responsable Demo',
            'email' => 'responsable@example.com',
            'password' => Hash::make('password'),
            'role' => 'responsable',
            'service' => 'Juridique',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'User Demo',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'role' => 'user',
            'service' => 'Juridique',
            'is_active' => true,
        ]);

        $spaceA = Space::create([
            'name' => 'Espace Juridique',
            'description' => 'Documents juridiques',
            'type' => 'private',
            'created_by' => $responsable->id,
        ]);

        $spaceB = Space::create([
            'name' => 'Espace Direction',
            'description' => 'Documents de direction',
            'type' => 'private',
            'created_by' => $admin->id,
        ]);

        $spaceA->members()->attach($admin->id, [
            'role' => 'admin',
            'joined_at' => now(),
        ]);
        $spaceA->members()->attach($responsable->id, [
            'role' => 'contributor',
            'joined_at' => now(),
        ]);
        $spaceA->members()->attach($user->id, [
            'role' => 'reader',
            'joined_at' => now(),
        ]);

        $spaceB->members()->attach($admin->id, [
            'role' => 'admin',
            'joined_at' => now(),
        ]);
        $spaceB->members()->attach($responsable->id, [
            'role' => 'reader',
            'joined_at' => now(),
        ]);

        Document::create([
            'title' => 'Procédure interne',
            'description' => 'Guide de procédure',
            'file_path' => 'documents/procedure.pdf',
            'original_filename' => 'procedure.pdf',
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'keywords' => ['procedure', 'interne'],
            'service' => 'Juridique',
            'space_id' => $spaceA->id,
            'uploaded_by' => $responsable->id,
            'status' => 'active',
        ]);

        Document::create([
            'title' => 'Plan stratégique',
            'description' => 'Plan annuel',
            'file_path' => 'documents/plan-strategique.pdf',
            'original_filename' => 'plan-strategique.pdf',
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 2048,
            'keywords' => ['strategie', 'direction'],
            'service' => 'Direction',
            'space_id' => $spaceB->id,
            'uploaded_by' => $admin->id,
            'status' => 'active',
        ]);
    }
}
