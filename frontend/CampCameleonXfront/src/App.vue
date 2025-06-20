<!-- src/App.vue -->
<template>
  <div id="app" class="public-layout">
       
    <!-- Main content -->
    <main class="main-content">
      <RouterView />
    </main>
   
  </div>
</template>

<script>
import PublicHeader from '@/public/components/layout/Header.vue'
import PublicFooter from '@/public/components/layout/Footer.vue'

export default {
  name: 'App',
  components: {
    PublicHeader,
    PublicFooter
  }
}
</script>

<style lang="scss">
// Import de vos styles globaux
@import '@/assets/styles/shared.scss';

#app {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

</style>