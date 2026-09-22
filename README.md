# CameleonCore

> Plateforme SaaS modulaire et configurable pour la gestion d'activités
> Un seul codebase, des instances personnalisées par client

Laravel Vue.js PostgreSQL Docker API Platform

## Vue d'ensemble

CameleonCore est une plateforme web modulaire capable de s'adapter à différents types d'activités — hôtellerie, restauration, traiteur, services à domicile — via un système de configuration par instance.

Chaque déploiement est une instance configurée avec ses propres modules, productables, features et thème visuel. Un seul codebase, une commande de setup, un client opérationnel.

### Origine

Le projet est l'évolution de [CampCameleonX](https://campcameleonx.ipace.dev), une plateforme de gestion hôtelière développée comme projet CDA (Bac+3). L'architecture et le code ont été refactorisés pour devenir une base SaaS réutilisable.

## Architecture

### Principe

Chaque instance est définie par un fichier `config/instance.php` qui lit les variables d'environnement :

```
Type d'activité → Modules activés → Productables disponibles → Features spécifiques
```

Le code reste identique entre les instances. Seule la configuration change.

### Configuration par instance

```
Instance traiteur          Instance hôtel
─────────────────          ──────────────────
Modules :                  Modules :
  ✓ Booking                  ✓ Booking
  ✓ Invoicing                ✓ Invoicing
  ✓ Calendar                 ✓ Calendar
  ✗ RBAC                     ✓ RBAC
  ✗ Staff                    ✓ Staff
  ✗ Reviews                  ✓ Reviews
  ✗ Analytics                ✓ Analytics
  ✗ Quote Builder            ✓ Quote Builder

Productables :             Productables :
  menu, dish                 room, activity, menu,
                             dish, ingredient

Features :                 Features :
  ✓ Acompte (30%)            ✓ Acompte (30%)
  ✓ Convives                 ✓ Check-in/out
  ✗ Check-in/out             ✓ Convives
```

### Modules disponibles

| Module | Description |
|--------|-------------|
| **Booking** | Réservations avec parcours adaptatif (simplifié ou devis multi-produits) |
| **Invoicing** | Facturation avec système acompte/solde, PDF automatisés |
| **Calendar** | Agenda des réservations (wrapper CameleonCalendar) |
| **RBAC** | Rôles et permissions (50+), désactivable pour les solo operators |
| **Staff** | Planning du personnel, assignation aux réservations |
| **Reviews** | Gestion des avis clients avec modération |
| **Analytics** | Dashboard statistiques adapté aux productables actifs |
| **Quote Builder** | Devis interactif multi-produits |

### Système de Productables

Architecture polymorphe : `Product → productable_type` (Activity, Room, Menu, Dish, Ingredient).

Chaque instance active uniquement les types pertinents. L'API, le backoffice et le frontend filtrent automatiquement selon la configuration.

## Stack technique

| Composant | Technologie |
|-----------|-------------|
| Backend | Laravel 12, API Platform 4, PHP 8.4 |
| Frontend | Vue.js 3 (Composition + Options API), Pinia |
| Base de données | PostgreSQL 17 |
| Paiement | Stripe (Checkout Sessions) |
| Facturation | DomPDF (acompte / solde / complète) |
| Calendrier | FullCalendar via wrapper CameleonCalendar |
| Conteneurisation | Docker Compose |
| Tests | Pest/PHPUnit (backend), Vitest (frontend) |
| Serveur | VPS OVH, Debian, Nginx |

## Structure du projet

```
CameleonCore/
├── backend/                 # Laravel 12 + API Platform 4
│   ├── app/
│   │   ├── Console/         # Commande instance:setup
│   │   ├── Http/            # Controllers, Middleware
│   │   ├── Models/          # Eloquent (polymorphe)
│   │   ├── Observers/       # Auto-création factures
│   │   ├── Services/        # InvoiceService, FactPulseService
│   │   └── State/           # Providers/Processors API Platform
│   ├── config/
│   │   └── instance.php     # Configuration par instance
│   └── tests/
│       ├── Feature/         # Tests API + instance config
│       └── Traits/          # ConfiguresInstance trait
├── frontend/                # Vue.js 3 SPA
│   ├── src/
│   │   ├── admin/           # Backoffice (sidebar dynamique)
│   │   ├── public/          # Site client + BookingModal modulaire
│   │   │   └── components/
│   │   │       └── booking/
│   │   │           ├── BookingModal.vue      # Orchestrateur
│   │   │           └── steps/
│   │   │               ├── StepDates.vue     # Dates + convives/heure
│   │   │               ├── StepProducts.vue  # Sélection par type
│   │   │               └── StepRecap.vue     # Récap + acompte
│   │   └── shared/
│   │       ├── components/
│   │       │   └── calendar/
│   │       │       └── CameleonCalendar.vue  # Wrapper FullCalendar
│   │       └── stores/
│   │           └── instance.js               # Config runtime
│   └── tests/
├── docker-compose.yaml
└── .env.example
```

## Installation

### Prérequis

- Docker & Docker Compose
- Git

### Nouvelle instance

```bash
# 1. Cloner
git clone https://github.com/lapace90/CameleonCore.git
cd CameleonCore

# 2. Copier l'environnement
cp backend/.env.example .env

# 3. Lancer les services
docker compose up -d db mailhog app scheduler pgadmin

# 4. Configuration interactive
docker exec -it myBackendCore php artisan instance:setup
```

La commande `instance:setup` :
- Collecte les informations (nom, type, modules, features)
- Propose des defaults intelligents selon le type d'activité
- Génère le `.env` complet
- Crée la base de données
- Lance les migrations
- Crée le compte administrateur
- Génère la clé d'application

### Développement frontend

```bash
cd frontend
npm install
npm run dev
# → http://localhost:5173
```

### Services Docker

| Service | Port | Description |
|---------|------|-------------|
| `myBackendCore` | 8000 | API Laravel |
| `myPostgresCore` | 5433 | PostgreSQL 17 |
| `myPgAdminCore` | 5050 | Interface BDD |
| `myMailhogCore` | 8025 | Capture emails (dev) |
| `mySchedulerCore` | — | Tâches CRON |

## API

### Endpoint de configuration publique

```
GET /api/config/public
```

Retourne la configuration de l'instance (modules, productables, features) sans authentification. Utilisé par le frontend au démarrage.

### Documentation

- OpenAPI/Swagger : `/api/docs`
- Authentification : Bearer token (Laravel Sanctum)

## Facturation

Système de facturation adaptatif :

- **Facture complète** — paiement intégral
- **Facture d'acompte** — pourcentage configurable, générée automatiquement au paiement Stripe
- **Facture de solde** — créée depuis le backoffice, liée à l'acompte

Les templates PDF s'adaptent au type de facture et aux informations de l'instance.

## Theming

Chaque instance personnalise son apparence via `_theme-override.scss` :

```scss
:root {
  --primary: #2c5f2d;
  --accent: #d4a373;
  --dark: #1a1a2e;
  --light: #f5f0eb;
}
```

Le système CSS utilise exclusivement des custom properties — un seul fichier à modifier par client.

## Tests

```bash
# Backend
docker exec -it myBackendCore php artisan test

# Frontend
cd frontend && npx vitest run
```

Le trait `ConfiguresInstance` permet de simuler différentes configurations d'instance dans les tests :

```php
$this->withTraiteurInstance();   // Config traiteur
$this->withFullInstance();       // Config hôtel complète
$this->withModule('rbac', false); // Toggle un module
```

## Roadmap

- [x] Phase 1 — Infrastructure modulaire (config, store, sidebar dynamique, productables filtrés)
- [x] Phase 2 — Facturation acompte/solde
- [x] Phase 3 — Parcours réservation modulaire (BookingModal adaptatif)
- [ ] Phase 4 — Theming par instance
- [x] Phase 5 — Commande `instance:setup` + tests
- [ ] Phase 6 — Module Staff (planning du personnel)
- [ ] Phase 7 — CameleonCalendar (remplacement FullCalendar)
- [ ] Phase 8 — Template repository (distribution)

## Démo

La version d'origine (CampCameleonX) est accessible en démo :

🌐 [campcameleonx.ipace.dev](https://campcameleonx.ipace.dev)

---

Développé par **Ilaria Pace** — [ipace.dev](https://ipace.dev)
