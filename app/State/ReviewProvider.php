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
        $currentUser = $context['user'] ?? null;
        $isAdmin = $currentUser && $currentUser->isAdmin();

        // 🔍 RÉCUPÉRATION D'UN SEUL AVIS
        if (isset($uriVariables['id'])) {
            $reviewId = (int) $uriVariables['id'];
            
            if ($isAdmin) {
                // Admin peut voir tous les avis
                return Review::find($reviewId);
            } else {
                // Public ne voit que les avis publiés
                return Review::published()->find($reviewId);
            }
        }

        // 📋 RÉCUPÉRATION DE LA COLLECTION
        $query = Review::query();

        // Filtrage par statut
        if (!$isAdmin) {
            $query->published();
        }

        // Filtres depuis la requête
        $request = $context['request'] ?? null;
        
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

            if ($isAdmin && $status = $request->query->get('status')) {
                $query->where('status', $status);
            }
        }

        // Tri : les plus récents en premier
        $query->orderBy('created_at', 'desc');

        Log::info('📖 ReviewProvider - Liste des avis', [
            'is_admin' => $isAdmin,
            'total' => $query->count(),
        ]);

        // ✅ Retourner les données sous forme de tableau pour API Platform
        return $query->get()->map(function ($review) {
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
                'photos' => $review->photos,
                'status' => $review->status,
                'created_at' => $review->created_at?->toISOString(),
            ];
        })->toArray();
    }
}