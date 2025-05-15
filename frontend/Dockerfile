# Dockerfile - frontend
FROM node:20

# Dossier de travail dans le container
WORKDIR /app

# Copie uniquement les fichiers de dépendances pour profiter du cache Docker
COPY package*.json ./

# Installation des dépendances
RUN npm install

# Puis copie le reste du projet
COPY . .

# Expose le port utilisé par Vite
EXPOSE 5173

# Lancement du serveur Vite (important : --host)

CMD ["npm", "run", "dev", "--", "--host"]
