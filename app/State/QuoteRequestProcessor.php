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

// app/State/QuoteRequestProcessor.php - LOGIQUE SIMPLE : Juste les products !
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
        $quoteData = [
            'product_ids' => $payload['product_ids'] ?? [], // Liste plate : [1, 2, 5, 8]
            'email' => $payload['email'],
            'name' => $payload['contact']['name'] ?? $payload['name'] ?? null,
            'phone' => $payload['contact']['phone'] ?? $payload['phone'] ?? null,
            'message' => $payload['contact']['message'] ?? $payload['message'] ?? null,
            
            // ✅ Dates du séjour
            'checkin_date' => $payload['dates']['checkin'] ?? $payload['dates']['start'] ?? null,
            'checkout_date' => $payload['dates']['checkout'] ?? $payload['dates']['endExclusive'] ?? null,
            'guests' => $payload['dates']['guests'] ?? $payload['guests'] ?? 2,
            
            'total_amount' => $payload['total_price'] ?? 0,
            'source' => 'website'
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
                'name' => $quoteRequest->name ?? 'Client',
                'reference' => $quoteRequest->quote_reference,
                'validation_url' => $quoteRequest->validation_url,
                'expires_at' => $quoteRequest->token_expires_at->format('d/m/Y à H:i'),
                'products_count' => count($quoteRequest->selected_product_ids),
                'total' => $quoteRequest->formatted_total
            ];

            // Pour l'instant, log - à remplacer par Mail::send()
            Log::info('📧 Email validation à envoyer', [
                'to' => $quoteRequest->email,
                'data' => $emailData
            ]);

            // TODO: Implémenter avec vraies vues Blade
            // Mail::send('emails.quote-validation', $emailData, function ($message) use ($quoteRequest) {
            //     $message->to($quoteRequest->email)
            //             ->subject('Confirmez votre demande de devis - CampCameleonX');
            // });

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

            Log::info('📧 Email devis confirmé à envoyer', [
                'to' => $quoteRequest->email,
                'reference' => $quoteRequest->quote_reference,
                'products' => count($emailData['products']),
                'total' => $emailData['total']['formatted']
            ]);

            // TODO: Template Blade avec devis complet
            // Mail::send('emails.quote-confirmed', $emailData, function ($message) use ($quoteRequest) {
            //     $message->to($quoteRequest->email)
            //             ->subject("Votre devis {$quoteRequest->quote_reference} - CampCameleonX");
            // });

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

        $attempts = cache()->increment($fullKey);
        cache()->expire($fullKey, 3600); // Expire dans 1h

        if ($attempts > 5) { // Max 5 devis par heure par IP
            throw new \InvalidArgumentException('Limite de demandes atteinte. Réessayez dans une heure.');
        }
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

    // Dans QuoteRequestProcessor
private function findOrCreateCustomer(array $payload): int 
{
    $email = $payload['email'];
    
    $customer = Customer::where('email', $email)->first();
    
    if (!$customer) {
        $customer = Customer::create([
            'name' => $payload['name'],
            'email' => $payload['email'], 
            'phone' => $payload['phone']
        ]);
    }
    
    return $customer->id;
}
}