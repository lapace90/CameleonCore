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

    <!-- Cookie Banner -->
    <CookieBanner />
  </div>
</template>

<script>
import PublicHeader from './components/layout/Header.vue'
import PublicFooter from './components/layout/Footer.vue'
import CookieBanner from '@/shared/components/CookieBanner.vue'

export default {
  name: 'PublicApp',
  components: {
    PublicHeader,
    PublicFooter,
    CookieBanner
  }
}
</script>

