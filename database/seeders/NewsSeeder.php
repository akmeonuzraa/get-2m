<?php

namespace Database\Seeders;

use App\Models\News;
use App\Models\User;
use Illuminate\Database\Seeder;

class NewsSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $responsable = User::where('role', 'responsable')->first();

        $articles = [
            [
                'title'      => 'Lancement de la plateforme GED',
                'content'    => 'Nous sommes heureux de vous annoncer le lancement officiel de la plateforme collaborative GED de 2M. Cette plateforme centralise tous vos documents et facilite la collaboration entre les équipes.',
                'target'     => 'all',
                'is_pinned'  => true,
                'status'     => 'published',
                'published_at' => now(),
                'created_by' => $admin->id,
            ],
            [
                'title'      => 'Mise à jour des procédures RH',
                'content'    => 'Les nouvelles procédures RH sont disponibles dans l\'espace RH. Merci de les consulter avant le 31 juillet 2026.',
                'target'     => 'service',
                'target_value' => 'RH',
                'is_pinned'  => false,
                'status'     => 'published',
                'published_at' => now()->subDays(2),
                'created_by' => $responsable->id,
            ],
            [
                'title'      => 'Maintenance programmée',
                'content'    => 'Une maintenance est prévue le 20 juillet 2026 de 22h à 02h. La plateforme sera indisponible durant cette période.',
                'target'     => 'all',
                'is_pinned'  => true,
                'status'     => 'published',
                'published_at' => now()->subDay(),
                'created_by' => $admin->id,
            ],
            [
                'title'      => 'Brouillon — Rapport annuel',
                'content'    => 'Contenu du rapport annuel en cours de rédaction.',
                'target'     => 'all',
                'is_pinned'  => false,
                'status'     => 'draft',
                'created_by' => $admin->id,
            ],
        ];

        foreach ($articles as $article) {
            News::create($article);
        }
    }
}