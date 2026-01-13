# Guide de Déploiement - CampCameleonX

## Déploiement Production (VPS/VM)

### Prérequis serveur

- **Ubuntu 22.04+** ou Debian 12+
- **Docker** (v24+) et Docker Compose
- **Git**
- Accès SSH avec clé
- Domaine configuré (DNS pointant vers l'IP du serveur)

### 1. Connexion et préparation

```bash
# Connexion SSH
ssh user@votre-serveur

# Installer Docker (si pas déjà fait)
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER

# Se reconnecter pour appliquer les droits
exit
ssh user@votre-serveur

# Vérifier Docker
docker --version
docker compose version
```

### 2. Cloner le projet

```bash
# Créer le répertoire
sudo mkdir -p /var/www/campcameleonx
sudo chown $USER:$USER /var/www/campcameleonx
cd /var/www/campcameleonx

# Cloner
git clone https://github.com/votre-repo/CampCameleonX.git .
```

### 3. Configuration production

```bash
# Créer le fichier .env
nano .env
```

**Configuration production :**

```env
# Application
APP_NAME=CampCameleonX
APP_ENV=production
APP_DEBUG=false
APP_URL=https://campcameleonx.votre-domaine.com

# Clé (générer une nouvelle)
APP_KEY=

# Base de données
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=campcameleonx
DB_USERNAME=postgres
DB_PASSWORD=MOT_DE_PASSE_TRES_SECURISE

# Frontend
VITE_API_URL=https://campcameleonx.votre-domaine.com/api

# Mail (exemple avec Gmail)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre-email@gmail.com
MAIL_PASSWORD=votre-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@votre-domaine.com
MAIL_FROM_NAME="CampCameleonX"

# Stripe (production)
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...

# Cache & Session
CACHE_DRIVER=file
SESSION_DRIVER=file

# PgAdmin
PGADMIN_EMAIL=admin@votre-domaine.com
PGADMIN_PASSWORD=MOT_DE_PASSE_PGADMIN

# Timezone
APP_TIMEZONE=Africa/Casablanca
```

### 4. Build et déploiement

```bash
# Générer la clé Laravel
docker compose run --rm app php artisan key:generate

# Builder et lancer tous les services
docker compose up -d --build

# Vérifier l'état
docker compose ps
```

**Tous les services doivent être "Up" :**

```
NAME            STATUS          PORTS
myBackendX      Up              0.0.0.0:8000->8000/tcp
myFrontendX     Up              0.0.0.0:5173->80/tcp
myPostgresX     Up (healthy)    0.0.0.0:5433->5432/tcp
mySchedulerX    Up              
myPgAdminX      Up              0.0.0.0:5050->80/tcp
```

### 5. Initialiser la base de données

```bash
# Migrations
docker compose exec app php artisan migrate --force

# Seeders (première installation uniquement)
docker compose exec app php artisan db:seed --force

# Optimisations Laravel
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

### 6. Configuration Nginx (Reverse Proxy)

Si vous utilisez Nginx comme reverse proxy avec SSL :

```nginx
# /etc/nginx/sites-available/campcameleonx
server {
    listen 80;
    server_name campcameleonx.votre-domaine.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name campcameleonx.votre-domaine.com;

    ssl_certificate /etc/letsencrypt/live/campcameleonx.votre-domaine.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/campcameleonx.votre-domaine.com/privkey.pem;

    # Frontend
    location / {
        proxy_pass http://127.0.0.1:5173;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }

    # API Backend
    location /api {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # Swagger UI
    location /api/docs {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
    }
}
```

```bash
# Activer le site
sudo ln -s /etc/nginx/sites-available/campcameleonx /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

# Certificat SSL (Let's Encrypt)
sudo certbot --nginx -d campcameleonx.votre-domaine.com
```

---

## Mises à jour

### Procédure de mise à jour

```bash
cd /var/www/campcameleonx

# Récupérer les changements
git pull origin main

# Rebuild si nécessaire
docker compose up -d --build

# Migrations
docker compose exec app php artisan migrate --force

# Vider les caches
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
```

### Rollback d'urgence

```bash
# Revenir au commit précédent
git log --oneline -5
git checkout <commit-hash>

# Rebuild
docker compose up -d --build
```

---

## Monitoring & Maintenance

### Logs

```bash
# Logs en temps réel
docker compose logs -f app          # API Laravel
docker compose logs -f scheduler    # Tâches CRON
docker compose logs -f db           # PostgreSQL
docker compose logs -f frontend     # Nginx frontend

# Logs Laravel
docker compose exec app tail -f storage/logs/laravel.log
```

### Vérifier les tâches planifiées

```bash
# Liste des tâches configurées
docker compose exec app php artisan schedule:list

# Exécuter manuellement
docker compose exec app php artisan schedule:run --verbose
```

### Sauvegardes base de données

```bash
# Dump manuel
docker compose exec db pg_dump -U postgres campcameleonx > backup_$(date +%Y%m%d).sql

# Restauration
docker compose exec -T db psql -U postgres campcameleonx < backup_20250113.sql
```

### Script de backup automatique

```bash
# /var/www/campcameleonx/scripts/backup.sh
#!/bin/bash
BACKUP_DIR="/var/backups/campcameleonx"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR

# Backup DB
docker compose exec -T db pg_dump -U postgres campcameleonx | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Garder seulement les 7 derniers jours
find $BACKUP_DIR -name "*.gz" -mtime +7 -delete

echo "Backup terminé: db_$DATE.sql.gz"
```

```bash
# Ajouter au crontab
crontab -e
# Ajouter : 0 2 * * * /var/www/campcameleonx/scripts/backup.sh
```

---

## Sécurité

### Checklist production

- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] Mots de passe forts (DB, PgAdmin)
- [ ] HTTPS activé
- [ ] Clés Stripe en mode production
- [ ] Firewall configuré (ports 80, 443 uniquement)
- [ ] Backups automatiques configurés

### Firewall (UFW)

```bash
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS
sudo ufw enable
```

### Désactiver PgAdmin en production (optionnel)

Commenter le service dans `docker-compose.yaml` ou le rendre accessible uniquement en local.

---

## Dépannage production

### Container qui redémarre en boucle

```bash
# Voir les logs du container
docker compose logs --tail=100 app

# Vérifier les ressources
docker stats
```

### Erreur 502 Bad Gateway

1. Vérifier que les containers tournent : `docker compose ps`
2. Vérifier les logs Nginx : `sudo tail -f /var/log/nginx/error.log`
3. Vérifier la connectivité : `curl http://127.0.0.1:8000/api`

### Base de données inaccessible

```bash
# Vérifier le healthcheck
docker compose ps db

# Tester la connexion
docker compose exec db psql -U postgres -c "SELECT 1;"

# Redémarrer si nécessaire
docker compose restart db
```