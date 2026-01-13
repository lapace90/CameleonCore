# CampCameleonX 

> **Application web de gestion d'un établissement touristique**  
> Plateforme complète pour la réservation d'activités, d'hébergements et de restauration

![Laravel](https://img.shields.io/badge/Laravel-v12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Vue.js](https://img.shields.io/badge/Vue.js-v3-4FC08D?style=for-the-badge&logo=vue.js&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-v17-336791?style=for-the-badge&logo=postgresql&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?style=for-the-badge&logo=docker&logoColor=white)
![API Platform](https://img.shields.io/badge/API_Platform-v4-67CDF0?style=for-the-badge)

---

## Vue d'ensemble

**CampCameleonX** est une application web complète de gestion pour une maison d'hôtes située dans le désert marocain. Elle permet aux visiteurs de découvrir et réserver des activités, hébergements et services de restauration, tout en offrant aux gestionnaires un back-office puissant pour administrer l'établissement.

### Contexte

Projet développé dans le cadre du Titre Professionnel Concepteur Développeur d'Applications (CDA), cette application répond aux besoins réels d'un établissement touristique avec une approche moderne et scalable.

### Objectifs principaux

- **Digitaliser** la gestion complète de l'établissement
- **Automatiser** les processus de réservation et facturation
- **Optimiser** l'expérience utilisateur avec une interface immersive
- **Centraliser** la gestion des activités, hébergements et restauration

---

## Fonctionnalités

### Site Public

- **Page d'accueil immersive** avec hero section évoquant le désert
- **Catalogue complet** : activités, hébergements, menus avec système de filtres
- **Devis personnalisé** multi-étapes avec calendrier interactif
- **Réservation en ligne** avec paiement sécurisé via Stripe
- **Espace client** pour suivre ses réservations
- **Design responsive** mobile-first

### Back-office Administration

- **Dashboard** avec statistiques en temps réel et widgets personnalisables
- **Gestion RBAC** : 6 rôles prédéfinis, 50+ permissions granulaires
- **Calendrier FullCalendar** avec drag & drop pour la planification
- **Gestion complète du catalogue** : CRUD pour tous les types de produits
- **Facturation automatisée** avec système de relances
- **Notifications temps réel** avec cache optimisé (TTL 7 jours)
- **Statistiques et analytics** pour le suivi de performance

### Fonctionnalités Techniques Avancées

- **Architecture API-first** avec API Platform pour Laravel
- **Relations polymorphes** intelligentes (Product → Activity/Room/Menu/Dish)
- **Système de cache** multi-niveaux avec ETag
- **Tâches planifiées** via conteneur Docker dédié
- **Documentation API** automatique (OpenAPI/Swagger)
- **Conformité RGPD** avec gestion des consentements

---

## Architecture Technique

### Stack Technologique

#### Backend
- **Laravel 12** - Framework PHP moderne
- **API Platform 4** - API REST auto-documentée
- **PostgreSQL 17** - Base de données relationnelle
- **Laravel Sanctum** - Authentification SPA
- **Eloquent ORM** - Mapping objet-relationnel

#### Frontend
- **Vue.js 3** - Framework JavaScript réactif
- **Vite** - Build tool ultra-rapide
- **Pinia** - Gestion d'état centralisée
- **FullCalendar.js** - Calendrier interactif
- **SCSS modulaire** - Styles organisés et maintenables

#### Infrastructure
- **Docker Compose** - Orchestration de conteneurs
- **4 conteneurs** : Backend, Frontend, PostgreSQL, Scheduler
- **Nginx** - Serveur web pour le frontend
- **GitHub Actions** - CI/CD automatisé

### Architecture des Conteneurs

```yaml
Services:
  ├── myBackendX    (Laravel API - Port 8000)
  ├── myFrontendX   (Vue.js SPA - Port 5173)
  ├── myPostgresX   (PostgreSQL - Port 5433)
  ├── mySchedulerX  (Tâches CRON isolées)
  ├── myPgAdminX    (Administration BDD - Port 5050)
  └── myMailhogX    (Capture emails dev - Port 8025)
```

### Pattern Polymorphe

```php
Product (abstraction commune)
    ├── Activity    (activités désert)
    ├── Room        (hébergements)
    ├── Menu        (formules repas)
    ├── Dish        (plats individuels)
    └── Ingredient  (composants)
```

---

## Installation

### Prérequis

- Docker & Docker Compose
- Git
- Node.js 20+ (pour le développement frontend)
- PHP 8.4+ (pour le développement backend local)

### Installation rapide

1. **Cloner le repository**
```bash
git clone https://github.com/lapace90/campcameleonx.git
cd campcameleonx
```

2. **Configuration environnement**
```bash
cp .env.example .env
# Éditer .env avec vos paramètres (DB, Stripe, Mail...)
```

3. **Lancer l'infrastructure Docker**
```bash
docker compose up -d --build
```

4. **Initialiser la base de données**
```bash
docker compose exec app php artisan migrate --seed
```

5. **Accéder à l'application**
- Frontend : http://localhost:5173
- API : http://localhost:8000
- Documentation API : http://localhost:8000/api/docs

### Mode développement

Pour le hot-reload en développement :

```bash
# Backend uniquement en Docker
docker compose up -d app db scheduler

# Frontend en local avec hot-reload
cd frontend/CampCameleonXfront
npm install
npm run dev
```

---

## API Documentation

L'API est auto-documentée grâce à API Platform :

- **OpenAPI/Swagger** : `/api/docs`
- **JSON-LD/Hydra** : `/api/contexts`
- **Formats supportés** : JSON, JSON-LD
- **Authentification** : Bearer token (Laravel Sanctum)

---

## Tests

### Tests automatisés

- **Backend** : 51 tests Pest/PHPUnit (164 assertions)
- **Frontend** : Tests composants avec Vitest
- **E2E** : Tests de parcours avec Playwright

### Lancer les tests

```bash
# Tests backend
docker compose exec app php artisan test

# Tests frontend
cd frontend/CampCameleonXfront
npm run test

# Tests E2E
npm run test:e2e
```

### Couverture de code

- Authentification et autorisation (RBAC)
- CRUD des entités principales
- Processus de réservation complet
- Paiements Stripe
- Tâches planifiées

---

## Déploiement

### Déploiement sur serveur

1. **Préparer le serveur**
```bash
# SSH vers le serveur
ssh user@server

# Cloner le projet
git clone https://github.com/yourusername/campcameleonx.git
cd campcameleonx
```

2. **Configuration production**
```bash
# Créer le fichier .env de production
nano .env
# Configurer toutes les variables (DB, Stripe, URLs...)
```

3. **Lancer les services**
```bash
docker compose up -d --build
docker compose exec app php artisan migrate --force
docker compose exec app php artisan optimize
```

4. **Vérifier le scheduler**
```bash
docker compose exec app php artisan schedule:list
docker compose logs -f scheduler
```

### Monitoring

```bash
# État des services
docker compose ps

# Logs temps réel
docker compose logs -f app       # API
docker compose logs -f frontend  # Nginx
docker compose logs -f db        # PostgreSQL
docker compose logs -f scheduler # Tâches CRON
```

---

## Sécurité

- **Authentification** : Laravel Sanctum avec tokens SPA
- **Autorisation** : RBAC avec 50+ permissions granulaires
- **Protection** : CSRF, XSS, SQL injection
- **HTTPS** : SSL/TLS en production
- **RGPD** : Conformité avec gestion des consentements
- **Paiements** : Intégration Stripe sécurisée (PCI-DSS)

---

## Contribution

Les contributions sont les bienvenues ! Voici comment participer :

1. Fork le projet
2. Créer une branche (`git checkout -b feature/AmazingFeature`)
3. Commit les changements (`git commit -m 'Add: nouvelle fonctionnalité'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

### Conventions de code

- **Backend** : PSR-12 pour PHP
- **Frontend** : ESLint + Prettier pour JavaScript/Vue
- **Commits** : Convention Conventional Commits
- **Documentation** : Commentaires en français, code en anglais

---

## Équipe

**Développement** : Ilaria Pace  
**Formation** : Titre Professionnel CDA  
**Période** : Novembre 2024 - Octobre 2025

---

<div align="center">
### Démo en ligne

🌐 **[campcameleonx.ipace.dev](https://campcameleonx.ipace.dev)**
  <br>
  <strong>CampCameleonX - L'expérience du désert marocain à portée de clic</strong>
  <br>
  <sub>Développé pour le Titre Professionnel CDA</sub>
</div>
