# 🦎 CampCameleonX Frontend

Interface moderne et responsive pour CampCameleonX - Solution complète de gestion de camping avec dashboard administrateur et site public.

## 🌟 Fonctionnalités

### 🔐 Interface Administrateur
- **Dashboard analytique** avec statistiques en temps réel
- **Gestion des utilisateurs** et réservations  
- **Interface moderne** inspirée d'Argon Dashboard
- **Navigation intuitive** avec sidebar responsive
- **Notifications** et alertes en temps réel

### 🌐 Site Public
- **Page d'accueil** attractive avec hero section
- **Présentation des services** et tarifs
- **Formulaire de contact** avec validation
- **Design responsive** mobile-first
- **Navigation fluide** entre les sections

## 🛠️ Technologies

- **Vue 3** avec Composition API
- **Vite** pour le build et le dev server
- **Pinia** pour la gestion d'état
- **Vue Router 4** pour le routing
- **SCSS** avec variables personnalisées
- **FontAwesome** pour les icônes
- **Chart.js** pour les graphiques (à venir)

## 🏗️ Architecture

```
src/
├── admin/              # Interface administrateur
│   ├── components/     # Composants spécifiques admin
│   ├── views/         # Pages admin (Dashboard, Users, etc.)
│   └── router/        # Routes admin
├── public/            # Site public
│   ├── components/    # Composants site public
│   ├── views/        # Pages publiques (Home, About, etc.)
│   └── router/       # Routes publiques
├── shared/           # Code partagé
│   ├── components/   # Composants réutilisables
│   ├── stores/      # Stores Pinia
│   └── utils/       # Utilitaires et helpers
└── assets/          # Styles, images, etc.
```

## 🚀 Installation

### Prérequis
- Node.js 18+
- npm 8+

### Setup
```bash
# Cloner le projet
git clone [repo-url]
cd CampCameleonXfront

# Installer les dépendances
npm install

# Démarrer le serveur de développement
npm run dev
```

## 📱 Routes

### 🌐 Routes Publiques
- `/home` - Page d'accueil
- `/about` - À propos de nous
- `/services` - Nos services et tarifs
- `/contact` - Nous contacter

### 🔐 Routes Admin
- `/admin/dashboard` - Tableau de bord
- `/admin/users` - Gestion des utilisateurs
- `/admin/analytics` - Analyses et statistiques
- `/admin/settings` - Paramètres système

## 🎨 Design System

### Couleurs Principales
- **Primary**: `#5e72e4` (Bleu Argon)
- **Success**: `#2dce89` (Vert)
- **Warning**: `#fb6340` (Orange)
- **Danger**: `#f5365c` (Rouge)

### Composants UI
- **BaseButton** - Boutons réutilisables avec variants
- **BaseInput** - Champs de saisie avec validation
- **BaseModal** - Modales responsives
- **Loading** - Composant de chargement

## 📦 Scripts Disponibles

```bash
npm run dev          # Serveur de développement
npm run build        # Build pour production
npm run preview      # Prévisualise le build
npm run lint         # Vérifie le code
npm run format       # Formate le code
npm run clean        # Nettoie les caches
```

## 🏪 Stores Pinia

### AuthStore
- Gestion de l'authentification
- État utilisateur connecté
- Tokens et sessions

### UserStore  
- Profil utilisateur
- Préférences
- Historique des réservations

### AppStore
- État global de l'application
- Notifications
- Configuration UI

## 🔧 Configuration

### Variables d'environnement
```env
VITE_API_URL=http://localhost:3000/api
VITE_APP_NAME=CampCameleonX
VITE_APP_VERSION=1.0.0
```

### Vite Config
- Alias pour imports simplifiés (`@`, `@admin`, `@public`, `@shared`)
- Optimisation des dépendances
- Configuration SCSS avec variables globales
- Proxy API pour le développement

## 📱 Responsive Design

- **Mobile First** approach
- Breakpoints: 768px (tablet), 992px (desktop)
- Navigation mobile avec menu hamburger
- Sidebar collapsible en admin
- Grilles adaptatives

## 🎯 Roadmap

### Phase 1 ✅ (Actuelle)
- [x] Structure de base
- [x] Layouts admin et public
- [x] Navigation et routing
- [x] Design system de base
- [x] Pages principales

### Phase 2 🚧 (En cours)
- [ ] Intégration API backend
- [ ] Authentification complète
- [ ] Gestion des réservations
- [ ] Upload d'images

### Phase 3 📋 (À venir)
- [ ] Graphiques avec Chart.js
- [ ] Système de notifications push
- [ ] Mode sombre
- [ ] PWA (Progressive Web App)
- [ ] Tests unitaires

### Phase 4 🔮 (Futur)
- [ ] Multi-langue (i18n)
- [ ] Chat en temps réel
- [ ] Géolocalisation
- [ ] Paiements en ligne

## 🤝 Contribution

### Structure des commits
```
feat: nouvelle fonctionnalité
fix: correction de bug
docs: documentation
style: formatage
refactor: refactoring
test: tests
chore: maintenance
```

### Guidelines
1. Utiliser la structure de dossiers établie
2. Respecter les conventions de nommage
3. Ajouter des commentaires pour les fonctions complexes
4. Tester sur mobile et desktop
5. Suivre les règles ESLint/Prettier

## 📄 Licence

© 2025 CampCameleonX. Tous droits réservés.

## 👥 Équipe

- **Frontend** - Interface utilisateur et expérience
- **Backend** - API et base de données  
- **Design** - UI/UX et direction artistique

## 🆘 Support

- 📧 Email: dev@campcameleonx.com
- 📱 Issues: [GitHub Issues]
- 📖 Documentation: [Wiki]

---

⭐ **Star ce projet si il t'a aidé !**