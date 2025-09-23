<template>
  <div>
    <h2>Réservation {{ $route.params.id }}</h2>
    <p>Détails de la réservation (composant temporaire)</p>
    <button @click="$router.go(-1)">Retour</button>
  </div>
</template>

<script>
export default {
  name: 'ReservationDetail'
}
</script>