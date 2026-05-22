<template>
       
    <!-- Main content -->
    <main class="main-content">
      <RouterView />
    </main>

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
@use '@/assets/styles/shared.scss' as *;

#app {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

</style>