<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\State\ProcessorInterface;
use App\Models\QuoteRequest;
use App\Models\Product;
use App\Services\EmailValidationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Customer;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class QuoteRequestProcessor implements ProcessorInterface
{
    public function __construct(
        private EmailValidationService $emailValidationService
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        return DB::transaction(function () use ($data, $operation, $uriVariables, $context) {
            try {
                switch (true) {
                    case $operation instanceof Post:
                        return $this->createQuoteRequest($context);

                    case $operation instanceof Get && isset($uriVariables['token']):
                        return $this->validateQuoteRequest((int) $uriVariables['id'], $uriVariables['token']);

                    case $operation instanceof Patch:
                        return $this->updateQuoteRequest((int) $uriVariables['id'], $context);

                    default:
                        throw new \InvalidArgumentException('Opération non supportée');
                }
            } catch (\Throwable $e) {
                Log::error('Erreur QuoteRequestProcessor', [
                    'operation' => get_class($operation),
                    'message' => $e->getMessage(),
                    'uri_variables' => $uriVariables
                ]);
                throw $e;
            }
        });
    }

    // ===========================
    // CRÉATION DEVIS + EMAIL VALIDATION
    // ===========================

    private function createQuoteRequest(array $context): QuoteRequest
    {
        $payload = $this->getDataFromRequest($context);

        Log::info('💾 Création demande devis', [
            'email' => $payload['email'] ?? 'N/A',
            'product_ids' => $payload['product_ids'] ?? [],
            'total_amount' => $payload['total_price'] ?? 0
        ]);

        // ✅ 1. Validation email basique
        if (!$this->emailValidationService->isValidEmail($payload['email'] ?? '')) {
            throw new \InvalidArgumentException('Adresse email invalide');
        }

        // ✅ 2. Rate limiting par IP
        $this->checkRateLimit();

        // ✅ 3. Préparation données - STRUCTURE SIMPLE !
        $contactData = [
            'email' => $payload['email'],
            'name' => $payload['contact']['name'] ?? $payload['name'] ?? null,
            'last_name' => $payload['contact']['last_name'] ?? $payload['last_name'] ?? null,
            'phone' => $payload['contact']['phone'] ?? $payload['phone'] ?? null,
        ];

        $quoteData = [
            'product_ids' => $payload['product_ids'] ?? [], // Liste plate : [1, 2, 5, 8]
            'message' => $payload['contact']['message'] ?? $payload['message'] ?? null,
            // ✅ Dates du séjour
            'checkin_date' => $payload['dates']['checkin'] ?? $payload['dates']['start'] ?? null,
            'checkout_date' => $payload['dates']['checkout'] ?? $payload['dates']['endExclusive'] ?? null,
            'guests' => $payload['dates']['guests'] ?? $payload['guests'] ?? 2,

            'total_amount' => $payload['total_price'] ?? 0,
            'source' => 'website',
            'customer_id' => $this->findOrCreateCustomer($contactData),
        ];

        // ✅ 4. Validation des product_ids
        $this->validateProductIds($quoteData['product_ids']);

        // ✅ 5. Création en base
        $quoteRequest = QuoteRequest::createFromQuoteData($quoteData);

        // ✅ 6. Envoi email de validation
        $this->sendValidationEmail($quoteRequest);

        Log::info('✅ Devis créé avec succès', [
            'id' => $quoteRequest->id,
            'reference' => $quoteRequest->quote_reference,
            'products_count' => count($quoteRequest->selected_product_ids),
            'validation_url' => $quoteRequest->validation_url
        ]);

        return $quoteRequest;
    }

    // ===========================
    // VALIDATION VIA TOKEN
    // ===========================

    private function validateQuoteRequest(int $id, string $token): QuoteRequest
    {
        $quoteRequest = QuoteRequest::findOrFail($id);

        Log::info('🔐 Tentative validation token', [
            'id' => $id,
            'current_status' => $quoteRequest->status,
            'token_expired' => $quoteRequest->isTokenExpired()
        ]);

        if ($quoteRequest->validateWithToken($token)) {
            // ✅ Token valide - Envoi du devis par email
            $this->sendQuoteConfirmation($quoteRequest);

            Log::info('✅ Validation réussie - Devis envoyé', [
                'id' => $quoteRequest->id,
                'email' => $quoteRequest->email,
                'products_count' => count($quoteRequest->selected_product_ids)
            ]);
        } else {
            Log::warning('❌ Validation échouée', [
                'id' => $id,
                'reason' => $quoteRequest->isTokenExpired() ? 'Token expiré' : 'Token invalide'
            ]);
        }

        return $quoteRequest;
    }

    // ===========================
    // MISE À JOUR ADMIN
    // ===========================

    private function updateQuoteRequest(int $id, array $context): QuoteRequest
    {
        $quoteRequest = QuoteRequest::findOrFail($id);
        $payload = $this->getDataFromRequest($context);

        // Seuls les champs autorisés pour l'admin
        $allowedFields = ['status', 'message'];
        $updateData = array_intersect_key($payload, array_flip($allowedFields));

        if (!empty($updateData)) {
            $quoteRequest->update($updateData);
        }

        return $quoteRequest;
    }

    // ===========================
    // VALIDATION MÉTIER
    // ===========================

    private function validateProductIds(array $productIds): void
    {
        if (empty($productIds)) {
            throw new \InvalidArgumentException('Aucun produit sélectionné');
        }

        // Vérifier que tous les products existent et sont actifs
        $validProductsCount = Product::whereIn('id', $productIds)
            ->where('status', true)
            ->where('is_draft', false)
            ->count();

        if ($validProductsCount !== count($productIds)) {
            throw new \InvalidArgumentException('Certains produits ne sont plus disponibles');
        }
    }

    // ===========================
    // SERVICES EMAIL
    // ===========================

    private function sendValidationEmail(QuoteRequest $quoteRequest): void
    {
        try {
            $emailData = [
                'quote' => $quoteRequest,
                'customer_name' => $quoteRequest->customer?->name ?? 'Client',
                'reference' => $quoteRequest->quote_reference,
                'verificationUrl' => $quoteRequest->validation_url,
                'expires_at' => $quoteRequest->token_expires_at->format('d/m/Y à H:i'),
                'products_count' => count($quoteRequest->selected_product_ids),
                'total' => $quoteRequest->formatted_total
            ];

            // ✅ ACTIVER l'envoi d'email réel
            Mail::send('quote-verification', $emailData, function ($message) use ($quoteRequest) {
                $message->to($quoteRequest->email)
                    ->subject('Confirmez votre demande de devis - CampCameleonX');
            });

            Log::info('📧 Email validation envoyé', [
                'to' => $quoteRequest->email,
                'reference' => $quoteRequest->quote_reference
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur envoi email validation', [
                'email' => $quoteRequest->email,
                'error' => $e->getMessage()
            ]);
            // Ne pas faire planter la création pour un problème d'email
        }
    }

    private function sendQuoteConfirmation(QuoteRequest $quoteRequest): void
    {
        try {
            // Récupérer les données complètes pour l'email
            $emailData = $quoteRequest->email_data;

            // ✅ ACTIVER l'envoi d'email réel  
            Mail::send('quote-confirmed', $emailData, function ($message) use ($quoteRequest) {
                $message->to($quoteRequest->email)
                    ->subject("Votre devis {$quoteRequest->quote_reference} - CampCameleonX");
            });

            Log::info('📧 Email devis confirmé envoyé', [
                'to' => $quoteRequest->email,
                'reference' => $quoteRequest->quote_reference
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur envoi email devis', [
                'quote_id' => $quoteRequest->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    // ===========================
    // SÉCURITÉ & RATE LIMITING
    // ===========================

    private function checkRateLimit(): void
    {
        $ip = request()->ip();
        $cacheKey = "quote_requests_rate_limit:{$ip}";
        $currentHour = Carbon::now()->format('Y-m-d-H');
        $fullKey = "{$cacheKey}:{$currentHour}";

        // ✅ CORRECTION : Utiliser put() au lieu d'expire()
        $attempts = cache()->get($fullKey, 0) + 1;
        cache()->put($fullKey, $attempts, 3600); // TTL de 3600 secondes (1h)

        if ($attempts > 5) { // Max 5 devis par heure par IP
            throw new \InvalidArgumentException('Limite de demandes atteinte. Réessayez dans une heure.');
        }

        Log::info('🛡️ Rate limiting check', [
            'ip' => $ip,
            'attempts' => $attempts,
            'hour' => $currentHour
        ]);
    }

    // ===========================
    // HELPERS
    // ===========================

    private function getDataFromRequest(array $context): array
    {
        if (isset($context['request']) && $context['request'] instanceof Request) {
            $request = $context['request'];
            $content = $request->getContent();

            if ($content) {
                $decoded = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        $request = app(Request::class);
        if ($request) {
            $content = $request->getContent();
            if ($content) {
                $decoded = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        throw new \InvalidArgumentException('Données de requête introuvables');
    }

    private function findOrCreateCustomer(array $payload): int
    {
        $email = $payload['email'] ?? null;

        if (!$email) {
            throw new \InvalidArgumentException('Adresse email du client manquante');
        }

        $customer = Customer::firstOrNew(['email' => $email]);

        $customer->fill([
            'name' => $payload['name'] ?? $customer->name ?? 'Client',
            'last_name' => $payload['last_name'] ?? $customer->last_name ?? ($payload['name'] ?? 'Client'),
        ]);

        if (array_key_exists('phone', $payload) && $payload['phone'] !== null) {
            $customer->phone = $payload['phone'];
        }

        if (!$customer->exists || $customer->isDirty()) {
            $customer->save();
        }

        return $customer->id;
    }

    // ===========================
    // ÉDITION DEVIS CLIENT
    // ===========================

    /**
     * Récupérer un devis pour édition par le client
     */
    private function getQuoteForEdit(int $id, string $editToken): QuoteRequest
    {
        $quoteRequest = QuoteRequest::findOrFail($id);

        Log::info('📝 Récupération devis pour édition', [
            'id' => $id,
            'quote_reference' => $quoteRequest->quote_reference,
            'current_status' => $quoteRequest->status,
            'email_verified' => (bool) $quoteRequest->email_verified_at
        ]);

        // ✅ 1. Vérifier que le token correspond
        if (!$quoteRequest->validation_token || !hash_equals((string)$quoteRequest->validation_token, (string)$editToken)) {
            Log::warning('❌ Token édition invalide', ['id' => $id]);
            throw new \InvalidArgumentException('Token d\'édition invalide');
        }

        // ✅ 2. Vérifier que le devis n'est pas expiré
        if ($quoteRequest->isTokenExpired()) {
            Log::warning('❌ Token édition expiré', ['id' => $id]);
            throw new \InvalidArgumentException('Le lien d\'édition a expiré');
        }

        // ✅ 3. Vérifier que le devis n'est pas déjà payé
        if ($quoteRequest->status === 'paid') {
            Log::warning('❌ Devis déjà payé, non modifiable', ['id' => $id]);
            throw new \InvalidArgumentException('Ce devis a déjà été payé et ne peut plus être modifié');
        }

        // ✅ 4. Charger les produits sélectionnés pour le frontend
        $quoteRequest->load('customer');

        Log::info('✅ Devis récupéré pour édition', [
            'id' => $quoteRequest->id,
            'reference' => $quoteRequest->quote_reference,
            'products_count' => count($quoteRequest->selected_product_ids)
        ]);

        return $quoteRequest;
    }

    /**
     * Mettre à jour un devis via édition client
     */
    private function updateQuoteForEdit(int $id, string $editToken, array $context): QuoteRequest
    {
        $quoteRequest = QuoteRequest::findOrFail($id);
        $payload = $this->getDataFromRequest($context);

        Log::info('📝 Mise à jour devis client', [
            'id' => $id,
            'quote_reference' => $quoteRequest->quote_reference,
            'new_products' => $payload['product_ids'] ?? [],
            'new_total' => $payload['total_price'] ?? 0
        ]);

        // ✅ 1. Mêmes vérifications que getQuoteForEdit
        if (!$quoteRequest->validation_token || !hash_equals((string)$quoteRequest->validation_token, (string)$editToken)) {
            throw new \InvalidArgumentException('Token d\'édition invalide');
        }

        if ($quoteRequest->isTokenExpired()) {
            throw new \InvalidArgumentException('Le lien d\'édition a expiré');
        }

        if ($quoteRequest->status === 'paid') {
            throw new \InvalidArgumentException('Ce devis a déjà été payé et ne peut plus être modifié');
        }

        // ✅ 2. Rate limiting par IP (même logique que création)
        $this->checkRateLimit();

        // ✅ 3. Validation des nouveaux produits
        if (isset($payload['product_ids'])) {
            $this->validateProductIds($payload['product_ids']);
        }

        // ✅ 4. Mise à jour des données autorisées
        $updateData = [];

        if (isset($payload['product_ids'])) {
            $updateData['selected_product_ids'] = $payload['product_ids'];
        }

        if (isset($payload['total_price'])) {
            $updateData['total_amount'] = $payload['total_price'];
        }

        if (isset($payload['dates']['checkin'])) {
            $updateData['checkin_date'] = $payload['dates']['checkin'];
        }

        if (isset($payload['dates']['checkout']) || isset($payload['dates']['endExclusive'])) {
            $updateData['checkout_date'] = $payload['dates']['checkout'] ?? $payload['dates']['endExclusive'];
        }

        if (isset($payload['dates']['guests']) || isset($payload['guests'])) {
            $updateData['guests'] = $payload['dates']['guests'] ?? $payload['guests'];
        }

        if (isset($payload['contact']['message']) || isset($payload['message'])) {
            $updateData['message'] = $payload['contact']['message'] ?? $payload['message'];
        }

        // ✅ 5. Remettre le devis en statut draft + régénérer token si changements importants
        if (!empty($updateData)) {
            $hasImportantChanges = isset($updateData['selected_product_ids']) || isset($updateData['total_amount']);

            if ($hasImportantChanges) {
                // Régénérer token et remettre à l'état non validé pour nouvelle validation email
                $updateData['validation_token'] = \Illuminate\Support\Str::random(64);
                $updateData['token_expires_at'] = \Carbon\Carbon::now()->addHours(48);
                $updateData['status'] = 'draft';
                $updateData['email_verified_at'] = null;
            }

            $quoteRequest->update($updateData);

            // ✅ 6. Envoyer nouvel email de validation si changements importants
            if ($hasImportantChanges) {
                $this->sendValidationEmail($quoteRequest);

                Log::info('✅ Devis modifié - Nouvel email envoyé', [
                    'id' => $quoteRequest->id,
                    'reference' => $quoteRequest->quote_reference,
                    'new_token' => substr($quoteRequest->validation_token, 0, 10) . '...'
                ]);
            } else {
                Log::info('✅ Devis modifié - Changements mineurs', [
                    'id' => $quoteRequest->id,
                    'reference' => $quoteRequest->quote_reference
                ]);
            }
        }

        return $quoteRequest;
    }
}
