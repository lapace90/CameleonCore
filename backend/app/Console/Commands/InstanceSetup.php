<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class InstanceSetup extends Command
{
    protected $signature = 'instance:setup {--force : Overwrite existing .env}';

    protected $description = 'Configure une nouvelle instance CameleonCore de manière interactive';

    private array $config = [];

    public function handle(): int
    {
        $this->info('');
        $this->info('🦎 CameleonCore — Configuration d\'une nouvelle instance');
        $this->info('=========================================================');
        $this->info('');

        // Vérifier si un .env existe déjà
        if (File::exists(base_path('.env')) && !$this->option('force')) {
            if (!$this->confirm('Un fichier .env existe déjà. Voulez-vous le remplacer ?', false)) {
                $this->warn('Configuration annulée.');
                return 1;
            }
        }

        $this->collectIdentity();
        $this->collectModules();
        $this->collectFeatures();
        $this->collectFactPulse();
        $this->collectDatabase();
        $this->collectStripe();
        $this->collectAdmin();

        // Récap
        $this->displaySummary();

        if (!$this->confirm('Confirmer et lancer la configuration ?', true)) {
            $this->warn('Configuration annulée.');
            return 1;
        }
        // Exécution
        $this->generateEnv();
        $this->reloadDatabaseConfig();
        $this->createDatabase();
        $this->runMigrations();
        $this->seedAdmin();
        $this->generateAppKey();

        $this->info('');
        $this->info('✅ Instance "' . $this->config['name'] . '" prête !');
        $this->info('');
        $this->info('Prochaines étapes :');
        $this->info('  → Configurez vos clés Stripe dans le .env');
        $this->info('  → Lancez le serveur : php artisan serve');
        $this->info('');

        return 0;
    }

    // ===========================
    // COLLECTE DES INFORMATIONS
    // ===========================

    private function collectIdentity(): void
    {
        $this->info('📋 IDENTITÉ DE L\'INSTANCE');
        $this->info('');

        $this->config['name'] = $this->ask('Nom de l\'activité', 'Mon Activité');

        $this->config['type'] = $this->choice(
            'Type d\'activité',
            ['hotel', 'traiteur', 'restaurant', 'service'],
            0
        );

        $this->config['country'] = $this->ask('Pays (code ISO)', 'FR');
        $this->config['email'] = $this->ask('Email de contact');
        $this->config['phone'] = $this->ask('Téléphone', '');
        $this->config['address'] = $this->ask('Adresse', '');
        $this->config['siret'] = $this->ask('SIRET (laisser vide si non applicable)', '');

        $this->info('');
    }

    private function collectModules(): void
    {
        $this->info('📦 MODULES');
        $this->info('');

        // Modules par défaut selon le type
        $defaults = $this->getDefaultModules();

        $this->config['modules'] = [
            'booking' => $this->confirm('Réservations (booking)', $defaults['booking']),
            'invoicing' => $this->confirm('Facturation (invoicing)', $defaults['invoicing']),
            'calendar' => $this->confirm('Calendrier (calendar)', $defaults['calendar']),
            'reviews' => $this->confirm('Avis clients (reviews)', $defaults['reviews']),
            'analytics' => $this->confirm('Analytics', $defaults['analytics']),
            'rbac' => $this->confirm('Multi-rôles RBAC', $defaults['rbac']),
            'staff' => false,
            'quote_builder' => $this->confirm('Devis interactif multi-produits (quote builder)', $defaults['quote_builder']),
        ];

        // Staff dépend de RBAC
        if ($this->config['modules']['rbac']) {
            $this->config['modules']['staff'] = $this->confirm('Planning staff (requiert RBAC)', $defaults['staff']);
        }

        $this->info('');

        $this->collectProductables();
    }

    private function collectProductables(): void
    {
        $this->info('🧩 PRODUCTABLES');
        $this->info('');

        $defaults = $this->getDefaultProductables();
        $all = ['room', 'activity', 'menu', 'dish', 'ingredient'];

        $this->info('  Sélection par défaut pour "' . $this->config['type'] . '" : ' . implode(', ', $defaults));

        if ($this->confirm('Personnaliser les productables ?', false)) {
            $selected = [];
            foreach ($all as $type) {
                $isDefault = in_array($type, $defaults);
                if ($this->confirm("  {$type}", $isDefault)) {
                    $selected[] = $type;
                }
            }
            $this->config['productables'] = $selected;
        } else {
            $this->config['productables'] = $defaults;
        }

        $this->info('');
    }

    private function getDefaultProductables(): array
    {
        return match ($this->config['type']) {
            'hotel' => ['room', 'activity', 'menu', 'dish', 'ingredient'],
            'traiteur' => ['menu', 'dish'],
            'restaurant' => ['menu', 'dish', 'ingredient'],
            'service' => ['activity'],
            default => ['menu', 'dish'],
        };
    }

    private function collectFeatures(): void
    {
        $this->info('⚙️  FEATURES');
        $this->info('');

        $defaults = $this->getDefaultFeatures();

        $this->config['features'] = [
            'deposit' => $this->confirm('Paiement d\'acompte', $defaults['deposit']),
            'deposit_pct' => 30,
            'seasonal' => $this->confirm('Disponibilité saisonnière', $defaults['seasonal']),
            'guest_count' => $this->confirm('Nombre de convives', $defaults['guest_count']),
            'checkin' => $this->confirm('Check-in / Check-out (séjour)', $defaults['checkin']),
            'e_invoicing' => false,
        ];

        if ($this->config['features']['deposit']) {
            $this->config['features']['deposit_pct'] = (int) $this->ask(
                'Pourcentage d\'acompte',
                '30'
            );
        }

        $this->info('');
    }

    private function collectFactPulse(): void
    {
        if ($this->config['country'] !== 'FR') {
            $this->config['factpulse'] = ['enabled' => false, 'api_key' => '', 'sandbox' => true];
            return;
        }

        $this->info('🧾 FACTURATION ÉLECTRONIQUE (FactPulse)');
        $this->info('');

        $enabled = $this->confirm('Activer FactPulse (Factur-X) ?', false);

        $this->config['factpulse'] = [
            'enabled' => $enabled,
            'api_key' => $enabled ? $this->ask('Clé API FactPulse', '') : '',
            'sandbox' => $enabled ? $this->confirm('Mode sandbox ?', true) : true,
        ];

        if ($enabled) {
            $this->config['features']['e_invoicing'] = true;
        }

        $this->info('');
    }

    private function collectDatabase(): void
    {
        $this->info('🗄️  BASE DE DONNÉES');
        $this->info('');

        $this->config['db'] = [
            'host' => $this->ask('Host PostgreSQL', 'myPostgresCore'),
            'port' => $this->ask('Port', '5432'),
            'database' => $this->ask('Nom de la base', 'cameleoncore'),
            'username' => $this->ask('Utilisateur', 'postgres'),
            'password' => $this->secret('Mot de passe') ?: 'secret',
        ];

        $this->info('');
    }

    private function collectStripe(): void
    {
        $this->info('💳 STRIPE');
        $this->info('');

        $this->config['stripe'] = [
            'key' => $this->ask('Clé publique Stripe (pk_test_...)', ''),
            'secret' => $this->ask('Clé secrète Stripe (sk_test_...)', ''),
            'mode' => $this->config['features']['deposit'] ? 'deposit' : 'full',
        ];

        $this->info('');
    }

    private function collectAdmin(): void
    {
        $this->info('👤 COMPTE ADMINISTRATEUR');
        $this->info('');

        $this->config['admin'] = [
            'name' => $this->ask('Nom de l\'administrateur'),
            'email' => $this->ask('Email de l\'administrateur'),
            'password' => $this->secret('Mot de passe') ?: 'password',
        ];

        $this->info('');
    }

    // ===========================
    // DEFAULTS PAR TYPE
    // ===========================

    private function getDefaultModules(): array
    {
        $type = $this->config['type'];

        $defaults = [
            'booking' => true,
            'invoicing' => true,
            'calendar' => true,
            'reviews' => false,
            'analytics' => false,
            'rbac' => false,
            'staff' => false,
            'quote_builder' => false,
        ];

        if ($type === 'hotel') {
            $defaults['reviews'] = true;
            $defaults['analytics'] = true;
            $defaults['rbac'] = true;
            $defaults['staff'] = true;
            $defaults['quote_builder'] = true;
        }

        return $defaults;
    }

    private function getDefaultFeatures(): array
    {
        $type = $this->config['type'];

        return match ($type) {
            'hotel' => [
                'deposit' => true,
                'seasonal' => true,
                'guest_count' => true,
                'checkin' => true,
            ],
            'traiteur' => [
                'deposit' => true,
                'seasonal' => true,
                'guest_count' => true,
                'checkin' => false,
            ],
            'restaurant' => [
                'deposit' => false,
                'seasonal' => true,
                'guest_count' => true,
                'checkin' => false,
            ],
            'service' => [
                'deposit' => true,
                'seasonal' => false,
                'guest_count' => false,
                'checkin' => false,
            ],
            default => [
                'deposit' => true,
                'seasonal' => true,
                'guest_count' => true,
                'checkin' => false,
            ],
        };
    }

    private function getProductables(): string
    {
        return implode(',', $this->config['productables']);
    }

    private function reloadDatabaseConfig(): void
    {
        $db = $this->config['db'];

        config([
            'database.connections.pgsql.host' => $db['host'],
            'database.connections.pgsql.port' => $db['port'],
            'database.connections.pgsql.database' => $db['database'],
            'database.connections.pgsql.username' => $db['username'],
            'database.connections.pgsql.password' => $db['password'],
        ]);

        \Illuminate\Support\Facades\DB::purge('pgsql');
        \Illuminate\Support\Facades\DB::reconnect('pgsql');
    }

    // ===========================
    // RÉCAPITULATIF
    // ===========================

    private function displaySummary(): void
    {
        $this->info('');
        $this->info('📋 RÉCAPITULATIF');
        $this->info('================');
        $this->info('');
        $this->info("  Nom          : {$this->config['name']}");
        $this->info("  Type         : {$this->config['type']}");
        $this->info("  Pays         : {$this->config['country']}");
        $this->info("  Email        : {$this->config['email']}");
        $this->info("  Productables : {$this->getProductables()}");
        $this->info('');

        $this->info('  Modules :');
        foreach ($this->config['modules'] as $key => $enabled) {
            $icon = $enabled ? '  ✓' : '  ✗';
            $this->info("    {$icon} {$key}");
        }

        $this->info('');
        $this->info('  Features :');
        $this->info('    ' . ($this->config['features']['deposit'] ? '✓' : '✗') . " Acompte ({$this->config['features']['deposit_pct']}%)");
        $this->info('    ' . ($this->config['features']['seasonal'] ? '✓' : '✗') . ' Disponibilité saisonnière');
        $this->info('    ' . ($this->config['features']['guest_count'] ? '✓' : '✗') . ' Nombre de convives');
        $this->info('    ' . ($this->config['features']['checkin'] ? '✓' : '✗') . ' Check-in / Check-out');
        $this->info('    ' . ($this->config['features']['e_invoicing'] ? '✓' : '✗') . ' Facturation électronique');
        $this->info('');

        $this->info("  Admin : {$this->config['admin']['name']} ({$this->config['admin']['email']})");
        $this->info("  DB    : {$this->config['db']['database']}@{$this->config['db']['host']}");
        $this->info('');
    }

    // ===========================
    // EXÉCUTION
    // ===========================

    private function generateEnv(): void
    {
        $this->info('⏳ Génération du .env...');

        $modules = $this->config['modules'];
        $features = $this->config['features'];
        $db = $this->config['db'];
        $stripe = $this->config['stripe'];
        $factpulse = $this->config['factpulse'];

        $env = <<<ENV
# ====================================
# APP
# ====================================
APP_NAME=CameleonCore
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=Europe/Paris
APP_URL=http://localhost:8000
APP_FRONTEND_URL=http://localhost:5173
APP_LOCALE=fr
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=fr_FR
APP_MAINTENANCE_DRIVER=file
PHP_CLI_SERVER_WORKERS=4
BCRYPT_ROUNDS=12

# ====================================
# LOG
# ====================================
LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

# ====================================
# DATABASE
# ====================================
DB_CONNECTION=pgsql
DB_HOST={$db['host']}
DB_PORT={$db['port']}
DB_DATABASE={$db['database']}
DB_USERNAME={$db['username']}
DB_PASSWORD={$db['password']}

# ====================================
# INSTANCE
# ====================================
INSTANCE_NAME="{$this->config['name']}"
INSTANCE_TYPE={$this->config['type']}
INSTANCE_COUNTRY={$this->config['country']}
INSTANCE_LOGO=/images/logo.png
INSTANCE_EMAIL={$this->config['email']}
INSTANCE_PHONE={$this->config['phone']}
INSTANCE_ADDRESS="{$this->config['address']}"
INSTANCE_SIRET={$this->config['siret']}

# Modules
MODULE_BOOKING={$this->bool($modules['booking'])}
MODULE_INVOICING={$this->bool($modules['invoicing'])}
MODULE_CALENDAR={$this->bool($modules['calendar'])}
MODULE_REVIEWS={$this->bool($modules['reviews'])}
MODULE_ANALYTICS={$this->bool($modules['analytics'])}
MODULE_RBAC={$this->bool($modules['rbac'])}
MODULE_STAFF={$this->bool($modules['staff'])}
MODULE_QUOTE={$this->bool($modules['quote_builder'])}

# Productables
INSTANCE_PRODUCTABLES={$this->getProductables()}

# Features
FEATURE_DEPOSIT={$this->bool($features['deposit'])}
FEATURE_DEPOSIT_PCT={$features['deposit_pct']}
FEATURE_SEASONAL={$this->bool($features['seasonal'])}
FEATURE_E_INVOICING={$this->bool($features['e_invoicing'])}
FEATURE_GUEST_COUNT={$this->bool($features['guest_count'])}
FEATURE_CHECKIN={$this->bool($features['checkin'])}

# ====================================
# STRIPE
# ====================================
STRIPE_KEY={$stripe['key']}
STRIPE_SECRET={$stripe['secret']}
STRIPE_MODE={$stripe['mode']}

# ====================================
# FACTPULSE
# ====================================
FACTPULSE_ENABLED={$this->bool($factpulse['enabled'])}
FACTPULSE_API_KEY={$factpulse['api_key']}
FACTPULSE_SANDBOX={$this->bool($factpulse['sandbox'])}

# ====================================
# SESSION & CACHE
# ====================================
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
CACHE_STORE=database

# ====================================
# MAIL
# ====================================
MAIL_MAILER=smtp
MAIL_HOST=myMailhogCore
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=noreply@cameleoncore.local
MAIL_FROM_NAME="\${INSTANCE_NAME}"
CONTACT_EMAIL={$this->config['email']}

# ====================================
# FRONTEND
# ====================================
VITE_APP_NAME="\${APP_NAME}"
VITE_API_URL=http://localhost:8000/api
ENV;

        File::put(base_path('.env'), $env);
        $this->info('  ✅ .env généré');
    }

    private function createDatabase(): void
    {
        $this->info('⏳ Création de la base de données...');

        $db = $this->config['db'];

        try {
            // Connexion à PostgreSQL sans spécifier de base
            $pdo = new \PDO(
                "pgsql:host={$db['host']};port={$db['port']}",
                $db['username'],
                $db['password']
            );

            $dbName = $db['database'];

            // Vérifier si la base existe déjà
            $stmt = $pdo->query("SELECT 1 FROM pg_database WHERE datname = '{$dbName}'");

            if ($stmt->fetchColumn()) {
                $this->info("  ℹ️  La base \"{$dbName}\" existe déjà");
            } else {
                $pdo->exec("CREATE DATABASE \"{$dbName}\"");
                $this->info("  ✅ Base \"{$dbName}\" créée");
            }
        } catch (\Exception $e) {
            $this->error("  ❌ Impossible de créer la base : {$e->getMessage()}");
            $this->warn('  Créez-la manuellement et relancez la commande.');
        }
    }

    private function runMigrations(): void
    {
        $this->info('⏳ Migrations...');
        $this->call('migrate', ['--force' => true]);
        $this->info('  ✅ Migrations terminées');
    }

    private function seedAdmin(): void
    {
        $this->info('⏳ Création du compte admin...');

        $admin = $this->config['admin'];

        $user = User::where('email', $admin['email'])->first();

        if ($user) {
            $this->warn("  ⚠️  L'utilisateur {$admin['email']} existe déjà");
            return;
        }

        User::create([
            'name' => $admin['name'],
            'email' => $admin['email'],
            'password' => Hash::make($admin['password']),
            'email_verified_at' => now(),
            'status' => 'active',
        ]);

        $this->info("  ✅ Admin {$admin['email']} créé");
    }

    private function generateAppKey(): void
    {
        $this->info('⏳ Génération APP_KEY...');
        $this->call('key:generate', ['--force' => true]);
        $this->info('  ✅ APP_KEY générée');
    }

    private function bool(bool $value): string
    {
        return $value ? 'true' : 'false';
    }
}
