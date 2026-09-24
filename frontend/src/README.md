# CameleonCore — Frontend

Interface Vue.js 3 du backoffice et du site public de CameleonCore. La sidebar, les routes et le parcours de réservation s'adaptent dynamiquement à la configuration de l'instance active.

## Technologies

- **Vue 3** (Composition API)
- **Vite 6** — build et dev server
- **Pinia** — état global
- **Vue Router 4** — routing
- **Bootstrap 5** — grille et utilitaires
- **SCSS** avec CSS custom properties (theming par instance)
- **Lucide Vue** — icônes
- **FullCalendar 6** — calendrier (wrapper `CameleonCalendar`)
- **Chart.js 4** — graphiques admin
- **vue-flatpickr** — sélecteur de dates
- **Quill** — éditeur de texte riche
- **Driver.js** — tour guidé (démo)

## Structure

```
src/
├── admin/                    # Backoffice
│   ├── components/
│   │   ├── charts/           # Graphiques Chart.js
│   │   ├── forms/            # Formulaires admin
│   │   ├── layout/           # Sidebar dynamique, Header
│   │   ├── modals/           # Modales CRUD
│   │   └── ui/               # Composants UI admin
│   ├── router/               # Routes admin (filtrées par modules)
│   └── views/
│       ├── invoices/         # Facturation acompte/solde
│       ├── products/         # Gestion des productables
│       ├── reservations/     # Gestion des réservations
│       └── users/            # Gestion des utilisateurs
├── public/                   # Site client
│   ├── components/
│   │   ├── booking/          # Parcours de réservation modulaire
│   │   │   ├── BookingModal.vue   # Orchestrateur
│   │   │   └── steps/
│   │   │       ├── StepDates.vue
│   │   │       ├── StepProducts.vue
│   │   │       └── StepRecap.vue
│   │   ├── forms/
│   │   ├── layout/
│   │   └── ui/
│   ├── router/
│   └── views/
├── shared/                   # Code commun admin + public
│   ├── components/
│   │   ├── calendar/         # CameleonCalendar.vue (wrapper FullCalendar)
│   │   └── ui/               # Composants réutilisables
│   ├── composables/
│   ├── configs/
│   ├── stores/
│   │   └── instance.js       # Config runtime (modules, productables, features)
│   └── utils/
├── demo-tour/                # Tour guidé Driver.js
├── plugins/
├── router/                   # Router racine
├── services/                 # Appels API
├── stores/                   # Stores globaux
└── assets/
    ├── images/
    └── styles/
        ├── admin/
        ├── admin-section/
        ├── components/
        ├── public/
        └── public-sections/
```

## Theming par instance

Les couleurs et variables visuelles sont définies via CSS custom properties dans `_theme-override.scss` :

```scss
:root {
  --primary: #2c5f2d;
  --accent: #d4a373;
}
```

Un seul fichier à modifier par client — aucune modification du code applicatif.

## Configuration d'instance au runtime

Le store `shared/stores/instance.js` charge la config publique de l'API au démarrage :

```
GET /api/config/public
```

Les modules activés, productables et features sont ensuite consommés par :
- la sidebar admin (items visibles)
- le router (routes enregistrées)
- `BookingModal` (étapes affichées)
- les vues admin (colonnes et filtres)

## Scripts

```bash
npm run dev            # Serveur de développement → http://localhost:5173
npm run build          # Build production
npm run preview        # Prévisualise le build
npm run lint           # Corrige ESLint
npm run format         # Formate avec Prettier
npm run test           # Tests Vitest (watch)
npm run test:frontend  # Vitest en one-shot
npm run test:e2e       # Tests Playwright
```

## Alias d'import (Vite)

- `@` → `src/`
- `@admin` → `src/admin/`
- `@public` → `src/public/`
- `@shared` → `src/shared/`
