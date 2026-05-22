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

    <!-- Demo Guide -->
    <DemoGuide v-if="isDemoEnabled" />
  </div>
</template>

<script>
import PublicHeader from './components/layout/Header.vue'
import PublicFooter from './components/layout/Footer.vue'
import CookieBanner from '@/shared/components/CookieBanner.vue'
import DemoGuide from '@/demo-tour/components/DemoGuide.vue'

export default {
  name: 'PublicApp',
  components: {
    PublicHeader,
    PublicFooter,
    CookieBanner,
    DemoGuide
  },

  computed: {
    isDemoEnabled() {
      if (import.meta.env.VITE_DEMO_MODE === 'false') return false
      if (typeof window === 'undefined') return false
      const params = new URLSearchParams(window.location.search)
      if (params.get('demo') === 'off') return false
      if (localStorage.getItem('campcameleon-demo-disabled') === 'true') return false
      return true
    }
  }
}
</script>