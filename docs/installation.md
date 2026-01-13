# Guide d'Installation - CampCameleonX

## Développement Local

### Prérequis

- **Docker Desktop** (v24+) avec Docker Compose
- **Node.js** (v20+) et npm
- **Git**

### 1. Cloner le projet

```bash
git clone https://github.com/votre-repo/CampCameleonX.git
cd CampCameleonX
```

### 2. Configuration environnement

```bash
# Copier le fichier d'environnement
cp .env.example .env

# Éditer les variables essentielles
nano .env
```

**Variables minimales à configurer :**

```env
# Base de données
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=campcameleonx
DB_USERNAME=postgres
DB_PASSWORD=votre_mot_de_passe_securise

# Application
APP_URL=http://localhost:8000
APP_ENV=local
APP_DEBUG=true

# Frontend
VITE_API_URL=http://localhost:8000/api

# PgAdmin (optionnel)
PGADMIN_EMAIL=admin@campcameleonx.com
PGADMIN_PASSWORD=votre_mot_de_passe

# Mail (MailHog en dev)
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
```

### 3. Lancer les services Docker

```bash
# Démarrer la base de données et le backend
docker compose up -d app db

# Vérifier que tout est UP
docker compose ps
```

**Services disponibles :**

| Service | URL | Description |
|---------|-----|-------------|
| Backend API | http://localhost:8000 | Laravel API |
| API Docs | http://localhost:8000/api/docs | Swagger UI |
| PgAdmin | http://localhost:5050 | Admin PostgreSQL |
| MailHog | http://localhost:8025 | Capture emails |

### 4. Initialiser la base de données

```bash
# Exécuter les migrations
docker compose exec app php artisan migrate

# Charger les données de test (seeders)
docker compose exec app php artisan db:seed

# Ou tout en une commande
docker compose exec app php artisan migrate:fresh --seed
```

**Données créées par les seeders :**
- 6 rôles avec 50 permissions
- 9 utilisateurs de test
- 30+ produits (activités, chambres, menus, plats)
- Catégories et tags

### 5. Lancer le frontend (développement)

```bash
# Se placer dans le dossier frontend
cd frontend

# Installer les dépendances
npm install

# Lancer le serveur de développement
npm run dev
```

Le frontend sera accessible sur **http://localhost:5173**

### 6. Comptes de test

| Email | Mot de passe | Rôle |
|-------|--------------|------|
| `admin@campcameleonx.com` | `password` | Super Admin |
| `manager@campcameleonx.com` | `password` | Manager |
| `reception@campcameleonx.com` | `password` | Réceptionniste |

---

## Commandes utiles

### Docker

```bash
# Voir les logs en temps réel
docker compose logs -f app          # Backend
docker compose logs -f db           # PostgreSQL
docker compose logs -f scheduler    # Tâches CRON

# Accéder au container
docker compose exec app bash

# Redémarrer un service
docker compose restart app
```

### Laravel (Artisan)

```bash
# Vider les caches
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear
docker compose exec app php artisan route:clear

# Voir les routes API
docker compose exec app php artisan route:list --path=api

# Lancer les tests
docker compose exec app php artisan test

# Tinker (REPL)
docker compose exec app php artisan tinker
```

### Frontend

```bash
# Build de production
npm run build

# Lancer les tests
npm run test

# Linting
npm run lint
```

---

## Dépannage

### La base de données ne démarre pas

```bash
# Vérifier le healthcheck
docker compose ps

# Si "unhealthy", vérifier les logs
docker compose logs db

# Supprimer le volume et recommencer
docker compose down -v
docker compose up -d db
```

### Erreur de connexion API depuis le frontend

1. Vérifier que `VITE_API_URL` pointe vers `http://localhost:8000/api`
2. Vérifier que le backend est accessible : `curl http://localhost:8000/api`
3. Vérifier la configuration CORS dans `config/cors.php`

### Migrations échouent

```bash
# Voir l'état des migrations
docker compose exec app php artisan migrate:status

# Rollback et retry
docker compose exec app php artisan migrate:rollback
docker compose exec app php artisan migrate
```

### Permissions / RBAC ne fonctionnent pas

```bash
# Reseed les rôles et permissions
docker compose exec app php artisan db:seed --class=RolePermissionSeeder
```

---

## Structure du projet

```
CampCameleonX/
├── app/
│   ├── Data/           # DTOs (Spatie Laravel Data)
│   ├── Models/         # Modèles Eloquent
│   ├── Observers/      # Observers (tags, notifications)
│   ├── State/          # Providers & Processors API Platform
│   └── Services/       # Services métier
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
├── frontend/           # Application Vue.js 3
│   ├── src/
│   │   ├── admin/      # Back-office
│   │   ├── public/     # Site client
│   │   └── shared/     # Composants partagés
│   └── package.json
├── tests/
│   ├── Feature/        # Tests d'intégration
│   └── Unit/           # Tests unitaires
├── docker-compose.yaml
├── Dockerfile
└── .env.example
```