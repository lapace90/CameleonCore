# Guide de Tests - CampCameleonX

## Vue d'ensemble

Suite de tests ciblée sur les parties critiques de l'application : authentification, RBAC, flux métier.

**Stratégie** : Filet de sécurité ciblé, pas couverture exhaustive. On teste ce qui est complexe, répétitif, et risqué à vérifier manuellement.

### Métriques actuelles

| Domaine | Tests | Assertions |
|---------|-------|------------|
| Backend (Pest/PHPUnit) | 51 | 164 |
| Frontend (Vitest) | 31 | - |

---

## Backend (Pest/PHPUnit)

### Couverture

- **Authentification** : login, logout, vérification token, cas d'erreur
- **RBAC** : héritage permissions entre rôles, déduplication multi-rôles, hiérarchie
- **Réservations** : création customer, création réservation, validation champs, relations
- **Profil utilisateur** : mise à jour, permissions

### Exécution

```bash
# Tous les tests backend
php artisan test

# Test spécifique
php artisan test --filter=RolePermissionTest

# Avec coverage
php artisan test --coverage-html coverage/backend
```

### Structure

```
tests/
├── Feature/
│   ├── Auth/
│   │   ├── AuthenticationTest.php
│   │   ├── LoginTest.php
│   │   └── ProfileManagementTest.php
│   ├── RBAC/
│   │   └── RolePermissionTest.php
│   └── ReservationFlowTest.php
├── Unit/
│   └── ExampleTest.php
├── Traits/
│   ├── AuthenticatesUsers.php
│   ├── CreatesTestData.php
│   └── AssertsApiResponses.php
└── TestCase.php
```

### Limite connue

Le test de check-out complet est skipped : l'isolation transactionnelle entre API Platform et PHPUnit empêche de simuler le flux complet (création réservation → check-in → check-out). Documenté comme limite technique.

---

## Frontend (Vitest)

### Couverture

- **Composables** : `useQuotePricing` (calcul prix devis)
- **Composants calendrier** : `EventModal`, `FullCalendar`
- **Composants public** : `QuoteModal`
- **Intégration** : `ReservationFlow`

### Exécution

```bash
# Tous les tests frontend
cd frontend/CampCameleonXfront && npx vitest run

# Mode watch (développement)
cd frontend/CampCameleonXfront && npx vitest

# Avec UI
cd frontend/CampCameleonXfront && npx vitest --ui

# Test spécifique
cd frontend/CampCameleonXfront && npx vitest run tests/components/calendar/FullCalendar.test.js
```

### Structure

```
frontend/CampCameleonXfront/tests/
├── setup.js                              # Config globale (mocks matchMedia, ResizeObserver)
├── composables/
│   └── useQuotePricing.test.js          # 10 tests
├── components/
│   ├── calendar/
│   │   ├── EventModal.test.js           # 5 tests
│   │   └── FullCalendar.test.js         # 9 tests
│   └── public/
│       └── QuoteModal.test.js           # 5 tests
└── integration/
    └── ReservationFlow.test.js          # 2 tests
```

### Approche

Les appels réseau sont **mockés** pour contrôler le contrat JSON attendu, indépendamment de l'état du backend. Si le format de réponse API change, les tests cassent immédiatement.

---

## Bonnes pratiques

1. **Tests isolés** : Chaque test est indépendant
2. **Mocks appropriés** : APIs et dépendances externes mockées
3. **Focus critique** : Auth, RBAC, flux métier en priorité
4. **Pragmatisme** : On ne teste pas pour le coverage, on teste ce qui compte