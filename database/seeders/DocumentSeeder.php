<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Folder;
use App\Models\Space;
use App\Models\User;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        $users  = User::all();
        $spaces = Space::all();

        foreach ($spaces as $space) {
            // Créer un dossier par espace
            $folder = Folder::create([
                'name'       => 'Documents généraux',
                'space_id'   => $space->id,
                'created_by' => $space->created_by,
            ]);

            // Créer 3 documents par espace
            foreach (range(1, 3) as $i) {
                $user = $users->random();

                $document = Document::create([
                    'title'             => "Document {$i} — {$space->name}",
                    'description'       => "Document de démonstration numéro {$i} pour l'espace {$space->name}",
                    'file_path'         => "documents/demo_{$space->id}_{$i}.pdf",
                    'original_filename' => "demo_{$i}.pdf",
                    'file_type'         => 'pdf',
                    'mime_type'         => 'application/pdf',
                    'file_size'         => rand(50000, 500000),
                    'keywords'          => ['demo', 'test', $space->name],
                    'service'           => $user->service,
                    'folder_id'         => $folder->id,
                    'space_id'          => $space->id,
                    'uploaded_by'       => $user->id,
                    'current_version'   => 1,
                    'status'            => 'active',
                ]);

                DocumentVersion::create([
                    'document_id'       => $document->id,
                    'version_number'    => 1,
                    'file_path'         => $document->file_path,
                    'original_filename' => $document->original_filename,
                    'file_size'         => $document->file_size,
                    'uploaded_by'       => $user->id,
                ]);
            }
        }
    }
}