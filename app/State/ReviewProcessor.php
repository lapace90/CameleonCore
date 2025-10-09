<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\State\ProcessorInterface;
use App\Models\Review;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ReviewProcessor implements ProcessorInterface
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        /** @var \App\Models\User|null $currentUser */
        $currentUser = auth('sanctum')->user();
        $isAdmin = $currentUser && $currentUser->isAdmin();

        // ✅ PATTERN CORRECT comme les autres Processors
        try {
            switch (true) {
                case $operation instanceof Post:
                    return $this->createReview($data, $context);

                case $operation instanceof Put:
                case $operation instanceof Patch:
                    return $this->updateReview($data, $uriVariables, $context, $isAdmin);

                case $operation instanceof Delete:
                    $this->deleteReview($uriVariables, $isAdmin);
                    return null;

                default:
                    throw new \InvalidArgumentException('Opération non supportée: ' . get_class($operation));
            }
        } catch (\Throwable $e) {
            Log::error('❌ Erreur ReviewProcessor', [
                'operation' => get_class($operation),
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * ✅ CRÉER un avis (public ou admin)
     */
    private function createReview($data, array $context): Review
    {
        $payload = $this->getDataFromRequest($context);

        Log::info('📝 ReviewProcessor - Création avis', [
            'client' => $payload['client_name'] ?? 'unknown',
        ]);

        // Validation
        $validator = Validator::make($payload, [
            'client_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'location' => 'nullable|string|max:255',
            'testimonial_text' => 'required|string|min:20',
            'rating' => 'required|integer|min:1|max:5',
            'category' => 'nullable|string|in:all,couples,families,solo,groups',
            'photos' => 'nullable|array',
        ], [
            'client_name.required' => 'Le nom est requis',
            'email.required' => "L'email est requis",
            'email.email' => "L'email doit être valide",
            'testimonial_text.required' => 'Le témoignage est requis',
            'testimonial_text.min' => 'Le témoignage doit contenir au moins 20 caractères',
            'rating.required' => 'La note est requise',
            'rating.min' => 'La note doit être entre 1 et 5',
            'rating.max' => 'La note doit être entre 1 et 5',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Créer l'avis
        $review = Review::create([
            'client_name' => $payload['client_name'],
            'email' => $payload['email'],
            'location' => $payload['location'] ?? null,
            'testimonial_text' => $payload['testimonial_text'],
            'rating' => (int) $payload['rating'],
            'category' => $payload['category'] ?? 'all',
            'photos' => $payload['photos'] ?? null,
            'status' => 'pending',
            'is_published' => false,
        ]);

        Log::info('✅ Avis créé', [
            'review_id' => $review->id,
            'client' => $review->client_name,
        ]);

        return $review;
    }

    /**
     * 🔄 METTRE À JOUR un avis (admin seulement)
     */
    private function updateReview($data, array $uriVariables, array $context, bool $isAdmin): Review
    {
        if (!$isAdmin) {
            throw new \Exception('Seuls les admins peuvent modifier les avis');
        }

        $reviewId = (int) $uriVariables['id'];
        $review = Review::findOrFail($reviewId);

        $payload = $this->getDataFromRequest($context);

        Log::info('🔄 ReviewProcessor - Mise à jour avis', [
            'review_id' => $reviewId,
            'payload_reçu' => $payload, // ✅ AJOUTÉ
            'is_published_avant' => $review->is_published, // ✅ AJOUTÉ
        ]);

        // Validation
        $validator = Validator::make($payload, [
            'status' => 'nullable|string|in:pending,approved,rejected',
            'is_published' => 'nullable|boolean',
            'featured' => 'nullable|boolean',
            'admin_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Mise à jour
        $review->update(array_filter($payload, fn($value) => $value !== null));

        Log::info('✅ Avis mis à jour', [
            'review_id' => $review->id,
            'status' => $review->status,
            'is_published_après' => $review->is_published, // ✅ AJOUTÉ
        ]);

        return $review->fresh();
    }

    /**
     * 🗑️ SUPPRIMER un avis (admin seulement)
     */
    private function deleteReview(array $uriVariables, bool $isAdmin): void
    {
        if (!$isAdmin) {
            throw new \Exception('Seuls les admins peuvent supprimer les avis');
        }

        $reviewId = (int) $uriVariables['id'];
        $review = Review::findOrFail($reviewId);

        Log::info('🗑️ ReviewProcessor - Suppression avis', [
            'review_id' => $reviewId,
        ]);

        $review->delete();

        Log::info('✅ Avis supprimé');
    }

    /**
     * Extraire les données depuis la requête
     */
    private function getDataFromRequest(array $context): array
    {
        $request = $context['request'] ?? null;

        if (!$request) {
            throw new \Exception('Requête non disponible');
        }

        return $request->request->all();
    }
}
