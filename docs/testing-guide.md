
// Makefile - Commands pour développeurs
.PHONY: test test-backend test-frontend test-e2e test-all install setup

# Installation complète
install:
	composer install
	cd frontend && npm install
	npx playwright install

# Configuration de l'environnement de test
setup:
	cp .env.testing .env
	php artisan key:generate
	php artisan migrate:fresh --seed
	cd frontend && npm run dev &

# Tests backend
test-backend:
	php artisan test

test-backend-coverage:
	php artisan test --coverage-html coverage/backend

# Tests frontend
test-frontend:
	cd frontend && npm run test

test-frontend-coverage:
	cd frontend && npm run test:coverage

test-frontend-watch:
	cd frontend && npm run test:watch

# Tests E2E
test-e2e:
	cd frontend && npm run test:e2e

test-e2e-debug:
	cd frontend && npm run test:e2e:debug

# Tests par catégorie
test-integration:
	cd frontend && npm run test:integration

test-stores:
	cd frontend && npm run test:stores

test-components:
	cd frontend && npm run test:components

# Tous les tests
test-all:
	make test-backend
	make test-frontend
	make test-e2e

# Tests CI
test-ci:
	make test-backend-coverage
	make test-frontend-coverage
	make test-e2e

# Nettoyage
clean:
	rm -rf frontend/node_modules
	rm -rf vendor
	rm -rf frontend/dist
	rm -rf coverage

# docs/testing-guide.md - Guide pour les développeurs
# Guide de Tests - CampCameleonX

## Vue d'ensemble

Cette suite de tests couvre tous les aspects de l'application :

### Backend (PHP/Laravel)
- **Tests unitaires** : Models, Services, Providers
- **Tests d'intégration** : API endpoints complets
- **Tests de validation** : Règles métier et sécurité

### Frontend (Vue.js)
- **Tests de composants** : Rendu et interactions
- **Tests de stores** : Logique Pinia
- **Tests d'intégration** : Flux complets
- **Tests E2E** : Parcours utilisateur

## Exécution des tests

### Développement local
```bash
# Tests en mode watch
make test-frontend-watch

# Tests spécifiques
npm run test:stores
npm run test:components
php artisan test --filter=ReservationTest

# Coverage complet
make test-ci
```

### Debugging
```bash
# E2E avec interface
npm run test:e2e:ui

# Tests frontend avec UI
npm run test:ui

# Debug test spécifique
npx vitest run tests/components/QuoteModal.test.js
```

## Structure des tests

```
tests/
├── backend/
│   ├── Feature/          # Tests d'intégration API
│   ├── Unit/            # Tests unitaires models/services
│   └── Integration/     # Tests flux complets
├── components/          # Tests composants Vue
├── stores/             # Tests stores Pinia
├── integration/        # Tests flux frontend
├── e2e/               # Tests end-to-end
└── setup.js           # Configuration globale
```

## Bonnes pratiques

1. **Tests isolés** : Chaque test est indépendant
2. **Mocks appropriés** : APIs et dépendances externes mockées
3. **Assertions claires** : Messages d'erreur explicites
4. **Coverage élevé** : >80% pour le code critique
5. **Tests rapides** : <3s pour la suite complète

## Métriques ciblées

- **Backend** : >85% coverage
- **Frontend composants** : >90% coverage
- **Stores** : 100% coverage
- **E2E** : Flux critiques couverts
- **Performance** : Temps de réponse <2s