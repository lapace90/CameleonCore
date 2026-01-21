<template>
  <Teleport to="body">
    <div v-if="isMobile" class="mobile-blocker">
      <div class="mobile-blocker-content">
        <div class="mobile-blocker-icon">
          <i class="fas fa-desktop"></i>
        </div>
        <h2>Interface Desktop requise</h2>
        <p>Le back-office nécessite un <strong>écran large</strong>.</p>

        <div class="credentials-box">
          <h3><i class="fas fa-key"></i> Identifiants de test</h3>
          <div class="cred-item" @click="copy('admin@campcanteloup.fr')">
            <span>👑 Propriétaire</span>
            <code>admin@campcanteloup.fr</code>
          </div>
          <div class="cred-item" @click="copy('fatima@campcanteloup.ma')">
            <span>🛎️ Réceptionniste</span>
            <code>fatima@campcanteloup.ma</code>
          </div>
          <p class="pwd-hint"><i class="fas fa-lock"></i> Mot de passe : <code>password</code></p>
        </div>

        <div v-if="copied" class="copy-toast"><i class="fas fa-check"></i> Copié !</div>

        <a href="/" class="btn-public"><i class="fas fa-home"></i> Voir le site public</a>
      </div>
    </div>
  </Teleport>
</template>

<script>
export default {
  name: 'MobileBlocker',
  props: {
    minWidth: { type: Number, default: 1024 }
  },
  data() {
    return {
      windowWidth: typeof window !== 'undefined' ? window.innerWidth : 1200,
      copied: false
    }
  },
  computed: {
    isMobile() {
      // Vérifie si démo désactivée
      if (import.meta.env.VITE_DEMO_MODE === 'false') return false
      if (typeof window !== 'undefined') {
        const params = new URLSearchParams(window.location.search)
        if (params.get('demo') === 'off') return false
        if (localStorage.getItem('campcameleon-demo-disabled') === 'true') return false
      }
      return this.windowWidth < this.minWidth
    }
  },
  methods: {
    onResize() {
      this.windowWidth = window.innerWidth
    },
    async copy(text) {
      try {
        await navigator.clipboard.writeText(text)
        this.copied = true
        setTimeout(() => this.copied = false, 2000)
      } catch (e) { /* ignore */ }
    }
  },
  mounted() {
    window.addEventListener('resize', this.onResize)
  },
  beforeUnmount() {
    window.removeEventListener('resize', this.onResize)
  }
}
</script>

<style>
.mobile-blocker {
  position: fixed;
  inset: 0;
  z-index: 99999;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #f5e6d3, #e8d4b8);
  padding: 1rem;
}
.mobile-blocker-content {
  max-width: 400px;
  text-align: center;
}
.mobile-blocker-icon {
  width: 70px;
  height: 70px;
  margin: 0 auto 1.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fff;
  border-radius: 50%;
  box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}
.mobile-blocker-icon i {
  font-size: 1.75rem;
  color: #c65d3b;
}
.mobile-blocker-content h2 {
  font-size: 1.5rem;
  color: #1a2332;
  margin-bottom: 0.75rem;
}
.mobile-blocker-content > p {
  color: #6b7280;
  margin-bottom: 1.5rem;
}
.mobile-blocker-content > p strong {
  color: #c65d3b;
}
.credentials-box {
  background: #fff;
  border-radius: 12px;
  padding: 1rem;
  margin-bottom: 1.5rem;
  text-align: left;
}
.credentials-box h3 {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
  color: #1a2332;
  margin-bottom: 0.75rem;
}
.credentials-box h3 i {
  color: #c65d3b;
}
.cred-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem;
  margin-bottom: 0.5rem;
  background: rgba(245,230,211,0.5);
  border-radius: 6px;
  cursor: pointer;
}
.cred-item:hover {
  background: rgba(198,93,59,0.1);
}
.cred-item span {
  font-size: 0.8rem;
}
.cred-item code {
  font-size: 0.75rem;
  color: #1a2332;
}
.pwd-hint {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0.75rem 0 0;
  padding-top: 0.75rem;
  border-top: 1px dashed rgba(26,35,50,0.1);
  font-size: 0.8rem;
  color: #6b7280;
}
.pwd-hint code {
  background: rgba(26,35,50,0.08);
  padding: 0.15rem 0.4rem;
  border-radius: 4px;
}
.copy-toast {
  position: fixed;
  bottom: 2rem;
  left: 50%;
  transform: translateX(-50%);
  background: #4a7c59;
  color: #fff;
  padding: 0.5rem 1rem;
  border-radius: 50px;
  font-size: 0.85rem;
}
.btn-public {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background: #fff;
  color: #c65d3b;
  text-decoration: none;
  border-radius: 50px;
  font-weight: 600;
  border: 2px solid #c65d3b;
}
.btn-public:hover {
  background: #c65d3b;
  color: #fff;
}
</style>