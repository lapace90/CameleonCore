<template>
  <div class="public-layout">
    <!-- Header/Navigation -->
    <PublicHeader />
    
    <!-- Main content -->
    <main class="main-content">
      <router-view />
    </main>
    
    <!-- Footer -->
    <PublicFooter />
  </div>
</template>

<script>
import PublicHeader from './components/layout/Header.vue'
import PublicFooter from './components/layout/Footer.vue'

export default {
  name: 'PublicApp',
  components: {
    PublicHeader,
    PublicFooter
  }
}
</script>

