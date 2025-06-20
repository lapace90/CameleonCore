# Passo 1 - DB in docker

Mettere database su docker-compose e assicurarsi che sia raggiungibile da fuori docker.

Cercare col backend di fare una query sul DB per vedere se si riesce ad accederci.

# Passo 2 - Backed in docker

Fare il 2-stage build in un Dockerfile per il backend laravel

Mettere la Dockerimage dentro il file docker-compose.

Fare una chiamata ad una API backend: l'API dovrebbe ritornare un risultato che provi che sia il DB che il backend sono raggiungibili da fuori docker.

# Passo 3 - Frontend chiama backend

Cercare di fare una chiamata al backend tramite l'app VueJS che gira in locale (NON in docker).

# Passo 4 - Frontend in docker

Fare il 2-stage build in un Dockerfile per il frontend Vue: utilizzare `npm run build`

Mettere la Dockerimage dentro il file docker-compose.

Fare una chiamata al frontend per assicurarsi che tutto funzioni. Ovvero:

    Browser chiama frontend su porta a scelta
    Frontend in docker chiama Backend in docker
    Backend in docker chiama DB in docker
    DB risponde

# Passo 5 - Sviluppare il resto del progetto

  - Sviluppare backend
  - Sviluppare frontend
  - Profit

# Info

Durante lo sviluppo, NON utilizzare docker per il frontend ma lanciare direttamente con `npm run dev` da dentro la cartella `frontend/CampCameleonXfront`.

Scommentare dal `docker-compose.yml` la parte frontend

Una volta che lo sviluppo è finito, forzare il rebuild dell'immagine del frontend con `docker compose up --force-recreate --build frontend`