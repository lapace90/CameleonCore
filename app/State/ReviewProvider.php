<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Models\Review;
use Illuminate\Support\Facades\Log;

class ReviewProvider implements ProviderInterface
{
    /**
     * 📖 Fournir les reviews pour API Platform
     * 
     * LECTURE PUBLIQUE : Retourne uniquement les avis publiés et approuvés
     * LECTURE ADMIN : Retourne tous les avis
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        /** @var \App\Models\User|null $currentUser */
        $currentUser = auth('sanctum')->user();
        $isAdmin = $currentUser && $currentUser->isAdmin();

        Log::info('📖 ReviewProvider - Début', [
            'is_authenticated' => $currentUser !== null,
            'is_admin' => $isAdmin,
            'user_id' => $currentUser?->id
        ]);

        // 🔍 RÉCUPÉRATION D'UN SEUL AVIS
        if (isset($uriVariables['id'])) {
            $reviewId = (int) $uriVariables['id'];

            if ($isAdmin) {
                $review = Review::find($reviewId);
            } else {
                $review = Review::published()->find($reviewId);
            }

            if (!$review) {
                return null;
            }

            return $this->normalizeReview($review);
        }

        // RÉCUPÉRATION DE LA COLLECTION
        $query = Review::query();

        // Récupérer la requête AVANT le filtrage
        $request = $context['request'] ?? null;
        
        // Vérifier si on force le mode public
        $forcePublicMode = $request && $request->query->get('public_only') === 'true';

        // Filtrage par statut
        if (!$isAdmin || $forcePublicMode) {
            // PUBLIC ou mode public forcé : Seulement les avis publiés
            $query->published();
            Log::info('📖 Mode PUBLIC', ['force_public' => $forcePublicMode]);
        } else {
            Log::info('📖 Mode ADMIN - tous les avis');
        }

        // Filtres depuis la requête
        if ($request) {
            if ($category = $request->query->get('category')) {
                $query->byCategory($category);
            }

            if ($rating = $request->query->get('rating')) {
                $query->byRating((int) $rating);
            }

            if ($request->query->get('featured') === 'true') {
                $query->featured();
            }

            if ($isAdmin && !$forcePublicMode && $status = $request->query->get('status')) {
                $query->where('status', $status);
            }
        }

        // Tri : les plus récents en premier
        $query->orderBy('created_at', 'desc');

        $total = $query->count();

        Log::info('📖 ReviewProvider - Résultat', [
            'is_admin' => $isAdmin,
            'force_public' => $forcePublicMode,
            'total' => $total,
        ]);

        // Retourner les données normalisées
        return $query->get()->map(function ($review) {
            return $this->normalizeReview($review);
        })->toArray();
    }

    /**
     * Normaliser un avis pour l'API
     * IMPORTANT : S'assure que photos est toujours un ARRAY, jamais un STRING
     */
    private function normalizeReview(Review $review): array
    {
        // Récupérer les photos et s'assurer que c'est un array
        $photos = $review->photos;

        // Si c'est une string JSON, la décoder
        if (is_string($photos)) {
            $decoded = json_decode($photos, true);
            $photos = is_array($decoded) ? $decoded : [];
        }

        // Si c'est null, mettre un array vide
        if (!is_array($photos)) {
            $photos = [];
        }

        return [
            'id' => $review->id,
            'client_name' => $review->client_name,
            'location' => $review->location,
            'email' => $review->email,
            'testimonial_text' => $review->testimonial_text,
            'rating' => $review->rating,
            'category' => $review->category,
            'featured' => $review->featured,
            'is_published' => $review->is_published,
            'photos' => $photos,
            'status' => $review->status,
            'created_at' => $review->created_at?->toISOString(),
        ];
    }
}