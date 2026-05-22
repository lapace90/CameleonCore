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
use Illuminate\Support\Str;

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
            'items' => $payload['items'] ?? [],
            'total_amount' => $payload['total_price'] ?? 0
        ]);

        // 1. Validation email basique AVEC TON SERVICE
        if (!$this->emailValidationService->isValidEmail($payload['email'] ?? '')) {
            throw new \InvalidArgumentException('Adresse email invalide');
        }

        // 2. Rate limiting par IP
        $this->checkRateLimit();

        // 3. Préparation données - STRUCTURE SIMPLE !
        $contactData = [
            'email' => $payload['email'],
            'name' => $payload['contact']['name'] ?? $payload['name'] ?? null,
            'last_name' => $payload['contact']['last_name'] ?? $payload['last_name'] ?? null,
            'phone' => $payload['contact']['phone'] ?? $payload['phone'] ?? null,
        ];

        $quoteData = [
            'message' => $payload['contact']['message'] ?? $payload['message'] ?? null,
            'checkin_date' => $payload['dates']['checkin'] ?? $payload['dates']['start'] ?? null,
            'checkout_date' => $payload['dates']['checkout'] ?? $payload['dates']['endExclusive'] ?? null,
            'guests' => $payload['dates']['guests'] ?? $payload['guests'] ?? 2,
            'total_amount' => $payload['total_price'] ?? 0,
            'source' => 'website',
            'customer_id' => $this->findOrCreateCustomer($contactData),
        ];

        // 4. Validation des items
        if (empty($payload['items'])) {
            throw new \InvalidArgumentException('Aucun produit sélectionné');
        }

        $productIds = array_column($payload['items'], 'product_id');
        $this->validateProductIds($productIds);

        // 5. Création en base
        $quoteRequest = QuoteRequest::createFromQuoteData($quoteData);

        // 6. Synchroniser les items
        $this->syncProducts($quoteRequest, $payload['items']);

        // 7. Envoi email de validation
        $this->sendValidationEmail($quoteRequest);

        Log::info('✅ Devis créé avec succès', [
            'id' => $quoteRequest->id,
            'reference' => $quoteRequest->quote_reference,
            'products_count' => $quoteRequest->items()->count(),
            'validation_url' => $quoteRequest->validation_url
        ]);

        return $quoteRequest;
    }

    // ===========================
    // SYNCHRONISATION PRODUITS
    // ===========================

    private function syncProducts(QuoteRequest $quoteRequest, array $items): void
    {
        if (empty($items)) {
            return;
        }

        $pivotData = [];
        foreach ($items as $item) {
            $productId = $item['product_id'] ?? null;
            $quantity = $item['quantity'] ?? 1;

            if ($productId) {
                $pivotData[$productId] = ['quantity' => max(1, (int)$quantity)];
            }
        }

        if (!empty($pivotData)) {
            $quoteRequest->products()->sync($pivotData);

            Log::info('✅ Produits synchronisés', [
                'quote_id' => $quoteRequest->id,
                'products' => $pivotData
            ]);
        }
    }

    private function generateQuoteReference(): string
    {
        $prefix = 'QUOTE';
        $date = Carbon::now()->format('Ymd');
        $random = Str::upper(Str::random(4));
        return "{$prefix}-{$date}-{$random}";
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
            // Token valide - Envoi du devis par email
            $this->sendQuoteConfirmation($quoteRequest);

            Log::info('✅ Validation réussie - Devis envoyé', [
                'id' => $quoteRequest->id,
                'email' => $quoteRequest->email,
                'products_count' => $quoteRequest->items()->count()
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

        $uniqueProductIds = array_unique($productIds);

        $validProductsCount = Product::whereIn('id', $uniqueProductIds)
            ->where('status', true)
            ->where('is_draft', false)
            ->count();

        if ($validProductsCount !== count($uniqueProductIds)) {
            $validProducts = Product::whereIn('id', $uniqueProductIds)
                ->where('status', true)
                ->where('is_draft', false)
                ->pluck('id')
                ->toArray();

            $invalidIds = array_diff($uniqueProductIds, $validProducts);

            Log::warning('Produits invalides détectés', [
                'requested_unique_ids' => $uniqueProductIds,
                'valid_ids' => $validProducts,
                'invalid_ids' => $invalidIds
            ]);

            throw new \InvalidArgumentException('Certains produits ne sont plus disponibles : ' . implode(', ', $invalidIds));
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
                'products_count' => $quoteRequest->items()->count(),
                'total' => $quoteRequest->formatted_total
            ];

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
        }
    }

    private function sendQuoteConfirmation(QuoteRequest $quoteRequest): void
    {
        try {
            $emailData = $quoteRequest->email_data;

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

        $attempts = cache()->get($fullKey, 0) + 1;
        cache()->put($fullKey, $attempts, 3600);

        if ($attempts > 5) {
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

    private function findOrCreateCustomer(array $contactData): int
    {
        $email = $contactData['email'];
        $request = app(\Illuminate\Http\Request::class);
        $ipAddress = $request->ip();

        $customer = Customer::firstOrNew(['email' => $email]);

        $customer->fill([
            'name' => $contactData['name'] ?? $customer->name ?? '',
            'last_name' => $contactData['last_name'] ?? $customer->last_name ?? '',
            'phone' => $contactData['phone'] ?? $customer->phone ?? null,
            'gdpr_consent' => true,
            'gdpr_consent_at' => now(),
            'gdpr_consent_ip' => $ipAddress,
            'newsletter_consent' => $contactData['newsletter'] ?? false,
            'newsletter_consent_at' => ($contactData['newsletter'] ?? false) ? now() : null,
        ]);

        $customer->save();

        return $customer->id;
    }

    // ===========================
    // ÉDITION DEVIS CLIENT
    // ===========================

    public function getQuoteForEdit(int $id, string $editToken): QuoteRequest
    {
        $quoteRequest = QuoteRequest::findOrFail($id);

        Log::info('📝 Récupération devis pour édition', [
            'id' => $id,
            'quote_reference' => $quoteRequest->quote_reference,
            'current_status' => $quoteRequest->status,
            'email_verified' => (bool) $quoteRequest->email_verified_at
        ]);

        if (!$quoteRequest->validation_token || !hash_equals((string)$quoteRequest->validation_token, (string)$editToken)) {
            Log::warning('❌ Token édition invalide', ['id' => $id]);
            throw new \InvalidArgumentException('Token d\'édition invalide');
        }

        if ($quoteRequest->isTokenExpired()) {
            Log::warning('❌ Token édition expiré', ['id' => $id]);
            throw new \InvalidArgumentException('Le lien d\'édition a expiré');
        }

        if ($quoteRequest->status === 'paid') {
            Log::warning('❌ Devis déjà payé', ['id' => $id]);
            throw new \InvalidArgumentException('Ce devis a déjà été payé');
        }

        $quoteRequest->load('customer');

        Log::info('✅ Devis récupéré pour édition', [
            'id' => $quoteRequest->id,
            'reference' => $quoteRequest->quote_reference,
            'products_count' => $quoteRequest->items()->count()
        ]);

        return $quoteRequest;
    }

    public function updateQuoteForEdit(int $id, string $editToken, array $context): QuoteRequest
    {
        $quoteRequest = QuoteRequest::findOrFail($id);
        $payload = $this->getDataFromRequest($context);

        Log::info('📝 Mise à jour devis client', [
            'id' => $id,
            'quote_reference' => $quoteRequest->quote_reference,
            'email_verified' => (bool) $quoteRequest->email_verified_at,
            'new_items' => $payload['items'] ?? [],
            'new_total' => $payload['total_price'] ?? 0
        ]);

        if (!$quoteRequest->validation_token || !hash_equals((string)$quoteRequest->validation_token, (string)$editToken)) {
            throw new \InvalidArgumentException('Token d\'édition invalide');
        }

        if ($quoteRequest->isTokenExpired()) {
            throw new \InvalidArgumentException('Le lien d\'édition a expiré');
        }

        if ($quoteRequest->status === 'paid') {
            throw new \InvalidArgumentException('Ce devis a déjà été payé');
        }

        $this->checkRateLimit();

        if (isset($payload['items'])) {
            $productIds = array_column($payload['items'], 'product_id');
            $this->validateProductIds($productIds);
        }

        $updateData = [];

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

        if (!empty($updateData) || isset($payload['items'])) {
            $hasImportantChanges = isset($payload['items']) || isset($updateData['total_amount']);

            // Ne PAS reset la vérification si l'email est déjà vérifié
            if ($hasImportantChanges && !$quoteRequest->email_verified_at) {
                // Email pas encore vérifié → régénérer token et renvoyer email
                $updateData['validation_token'] = Str::random(64);
                $updateData['token_expires_at'] = Carbon::now()->addHours(48);
                $updateData['status'] = 'draft';
            }

            $quoteRequest->update($updateData);

            if (isset($payload['items'])) {
                $this->syncProducts($quoteRequest, $payload['items']);
            }

            // Ne renvoyer l'email QUE si pas déjà vérifié
            if ($hasImportantChanges && !$quoteRequest->email_verified_at) {
                $this->sendValidationEmail($quoteRequest);

                Log::info('✅ Devis modifié - Nouvel email envoyé (email non vérifié)', [
                    'id' => $quoteRequest->id,
                    'reference' => $quoteRequest->quote_reference
                ]);
            } else {
                Log::info('✅ Devis modifié - Pas de nouvel email (email déjà vérifié)', [
                    'id' => $quoteRequest->id,
                    'reference' => $quoteRequest->quote_reference,
                    'email_verified_at' => $quoteRequest->email_verified_at
                ]);
            }
        }

        return $quoteRequest;
    }
}
