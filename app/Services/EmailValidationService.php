<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

// app/Services/EmailValidationService.php - Validation email basique pour Phase 1
class EmailValidationService
{
    // Domaines email suspects à bloquer
    private array $blockedDomains = [
        // Domaines jetables courants
        '10minutemail.com',
        'guerrillamail.com',
        'mailinator.com',
        'tempmail.org',
        'yopmail.com',
        '0-mail.com',
        '33mail.com',
        // Ajouter d'autres domaines suspects selon besoins
    ];

    // Domaines email autorisés (liste blanche optionnelle)
    private array $trustedDomains = [
        'gmail.com',
        'outlook.com',
        'hotmail.com',
        'yahoo.com',
        'orange.fr',
        'free.fr',
        'wanadoo.fr',
        'laposte.net',
        'sfr.fr',
        'live.fr'
    ];

    // ===========================
    // VALIDATION PRINCIPALE
    // ===========================

    /**
     * Valider une adresse email (syntaxe + domaine)
     */
    public function isValidEmail(string $email): bool
    {
        if (empty(trim($email))) {
            return false;
        }

        // 1. Validation syntaxe de base
        if (!$this->isValidSyntax($email)) {
            Log::warning('Email syntaxe invalide', ['email' => $email]);
            return false;
        }

        // 2. Validation domaine
        $domain = $this->extractDomain($email);
        if (!$this->isValidDomain($domain)) {
            Log::warning('Email domaine invalide', ['email' => $email, 'domain' => $domain]);
            return false;
        }

        // 3. Vérification domaines bloqués
        if ($this->isBlockedDomain($domain)) {
            Log::warning('Email domaine bloqué', ['email' => $email, 'domain' => $domain]);
            return false;
        }

        return true;
    }

    /**
     * Validation renforcée (avec vérification MX)
     * À utiliser pour les cas critiques
     */
    public function isValidEmailAdvanced(string $email): bool
    {
        if (!$this->isValidEmail($email)) {
            return false;
        }

        $domain = $this->extractDomain($email);
        
        // Vérification enregistrement MX du domaine
        if (!$this->hasMxRecord($domain)) {
            Log::warning('Domaine sans enregistrement MX', ['domain' => $domain]);
            return false;
        }

        return true;
    }

    // ===========================
    // VALIDATIONS SPÉCIFIQUES
    // ===========================

    /**
     * Validation syntaxe avec filter_var + regex
     */
    private function isValidSyntax(string $email): bool
    {
        // Filter PHP standard
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Regex complémentaire pour cas edge
        $pattern = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
        if (!preg_match($pattern, $email)) {
            return false;
        }

        // Vérifications supplémentaires
        if (strlen($email) > 254) { // RFC 5321
            return false;
        }

        [$local, $domain] = explode('@', $email, 2);
        
        if (strlen($local) > 64 || strlen($domain) > 253) { // RFC 5321
            return false;
        }

        return true;
    }

    /**
     * Validation domaine basique
     */
    private function isValidDomain(string $domain): bool
    {
        if (empty($domain)) {
            return false;
        }

        // Domaine doit contenir au moins un point
        if (!str_contains($domain, '.')) {
            return false;
        }

        // Pas de caractères suspects
        if (preg_match('/[^a-zA-Z0-9.-]/', $domain)) {
            return false;
        }

        // Vérification TLD minimum 2 caractères
        $parts = explode('.', $domain);
        $tld = end($parts);
        
        return strlen($tld) >= 2 && ctype_alpha($tld);
    }

    /**
     * Vérifier si domaine est dans la liste noire
     */
    private function isBlockedDomain(string $domain): bool
    {
        $domain = strtolower($domain);
        
        foreach ($this->blockedDomains as $blocked) {
            if ($domain === $blocked || Str::endsWith($domain, '.' . $blocked)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifier enregistrement MX (avec cache)
     */
    private function hasMxRecord(string $domain): bool
    {
        $cacheKey = "mx_record:" . strtolower($domain);
        
        return Cache::remember($cacheKey, 3600, function () use ($domain) { // Cache 1h
            try {
                return checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
            } catch (\Throwable $e) {
                Log::warning('Erreur vérification MX', [
                    'domain' => $domain,
                    'error' => $e->getMessage()
                ]);
                // En cas d'erreur DNS, on accepte (pour éviter faux négatifs)
                return true;
            }
        });
    }

    // ===========================
    // UTILITAIRES
    // ===========================

    /**
     * Extraire le domaine d'un email
     */
    private function extractDomain(string $email): string
    {
        $parts = explode('@', $email);
        return strtolower(end($parts));
    }

    /**
     * Normaliser une adresse email
     */
    public function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    /**
     * Vérifier si l'email est dans un domaine de confiance
     */
    public function isTrustedDomain(string $email): bool
    {
        $domain = $this->extractDomain($email);
        return in_array($domain, $this->trustedDomains, true);
    }

    /**
     * Obtenir suggestions pour domaine mal tapé
     */
    public function getSuggestions(string $email): array
    {
        $domain = $this->extractDomain($email);
        [$local] = explode('@', $email, 2);
        
        $suggestions = [];
        
        // Suggestions pour erreurs de frappe courantes
        $commonTypos = [
            'gmial.com' => 'gmail.com',
            'gmai.com' => 'gmail.com', 
            'hotmial.com' => 'hotmail.com',
            'yahooo.com' => 'yahoo.com',
            'orang.fr' => 'orange.fr'
        ];

        if (isset($commonTypos[$domain])) {
            $suggestions[] = $local . '@' . $commonTypos[$domain];
        }

        return $suggestions;
    }

    // ===========================
    // CONFIGURATION
    // ===========================

    /**
     * Ajouter un domaine à la liste noire
     */
    public function blockDomain(string $domain): void
    {
        $domain = strtolower($domain);
        if (!in_array($domain, $this->blockedDomains, true)) {
            $this->blockedDomains[] = $domain;
        }
    }

    /**
     * Obtenir statistiques de validation
     */
    public function getValidationStats(): array
    {
        // À implémenter si besoin de métriques
        return [
            'blocked_domains_count' => count($this->blockedDomains),
            'trusted_domains_count' => count($this->trustedDomains),
            'cache_hits' => 0 // À implémenter avec compteurs Redis
        ];
    }
}