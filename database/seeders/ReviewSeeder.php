<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Review;
use Carbon\Carbon;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $reviews = [
            [
                'client_name' => 'Sophie & Alexandre',
                'location' => 'Paris, France',
                'email' => 'sophie.martin@email.fr',
                'testimonial_text' => 'Un séjour magique dans cette magnifique maison d\'hôte ! L\'architecture traditionnelle marocaine, l\'hospitalité chaleureuse et la cuisine délicieuse nous ont conquis. Le cadre est apaisant et parfait pour se ressourcer. On recommande à 100% !',
                'rating' => 5,
                'category' => 'couples',
                'featured' => true,
                'is_published' => true,
                'status' => 'approved',
                'photos' => null,
                'created_at' => Carbon::now()->subDays(15),
            ],
            [
                'client_name' => 'Famille Dubois',
                'location' => 'Lyon, France',
                'email' => 'dubois.famille@email.fr',
                'testimonial_text' => 'Une découverte merveilleuse pour toute la famille ! Les chambres sont spacieuses et décorées avec goût. Les enfants ont adoré la piscine et le patio. Le petit-déjeuner marocain était un régal. Mention spéciale pour l\'accueil exceptionnel de l\'équipe.',
                'rating' => 4,
                'category' => 'families',
                'featured' => false,
                'is_published' => true,
                'status' => 'approved',
                'photos' => null,
                'created_at' => Carbon::now()->subDays(10),
            ],
            [
                'client_name' => 'James Miller',
                'location' => 'Londres, UK',
                'email' => 'james.miller@email.com',
                'testimonial_text' => 'Absolutely stunning riad! The authentic Moroccan decoration, the peaceful courtyard, and the rooftop terrace with amazing views made this stay unforgettable. Perfect place to experience true Moroccan hospitality.',
                'rating' => 5,
                'category' => 'solo',
                'featured' => true,
                'is_published' => true,
                'status' => 'approved',
                'photos' => null,
                'created_at' => Carbon::now()->subDays(7),
            ],
            [
                'client_name' => 'Groupe Copines Madrid',
                'location' => 'Madrid, Espagne',
                'email' => 'maria.garcia@email.es',
                'testimonial_text' => 'Un weekend entre amies absolument parfait ! La maison d\'hôte est sublime avec ses zellige traditionnels et ses patios fleuris. On a adoré les tajines faits maison et l\'ambiance conviviale. Emplacement idéal pour visiter la médina.',
                'rating' => 5,
                'category' => 'groups',
                'featured' => false,
                'is_published' => true,
                'status' => 'approved',
                'photos' => null,
                'created_at' => Carbon::now()->subDays(5),
            ],
            [
                'client_name' => 'Lisa & Thomas',
                'location' => 'Bruxelles, Belgique',
                'email' => 'lisa.thomas@email.be',
                'testimonial_text' => 'Une parenthèse enchantée dans ce riad authentique ! Tout est parfait : le confort des chambres, la beauté du lieu, la gentillesse du personnel. Le massage traditionnel au hammam était divin. On reviendra sans hésiter !',
                'rating' => 5,
                'category' => 'couples',
                'featured' => false,
                'is_published' => false, // ← Cet avis est approuvé mais pas encore publié
                'status' => 'approved',
                'photos' => null,
                'created_at' => Carbon::now()->subDays(2),
            ],
            [
                'client_name' => 'Marc Bertrand',
                'location' => 'Marseille, France',
                'email' => 'marc.bertrand@email.fr',
                'testimonial_text' => 'Bon séjour dans l\'ensemble. Le lieu est joli mais un peu éloigné du centre. Le rapport qualité-prix pourrait être meilleur. Service correct sans plus.',
                'rating' => 3,
                'category' => 'solo',
                'featured' => false,
                'is_published' => false,
                'status' => 'pending', // ← Cet avis est en attente de validation
                'photos' => null,
                'created_at' => Carbon::now()->subDays(1),
            ],
        ];

        foreach ($reviews as $review) {
            Review::create($review);
        }

        $this->command->info('✅ ' . count($reviews) . ' avis créés avec succès !');
    }
}