<template>
  <Transition name="slide-up">
    <div v-if="showBanner" class="cookie-banner">
      <div class="cookie-container">
        <div class="cookie-content">
          <div class="cookie-icon">🍪</div>
          <div class="cookie-text">
            <p>
              Nous utilisons des cookies essentiels pour le fonctionnement du site et des cookies fonctionnels pour améliorer votre expérience.
              <router-link to="/privacy" class="cookie-link">En savoir plus</router-link>
            </p>
          </div>
        </div>
        <div class="cookie-actions">
          <button @click="acceptAll" class="btn-accept">
            Tout accepter
          </button>
          <button @click="acceptEssential" class="btn-essential">
            Essentiels uniquement
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script>
import { useCookieConsent } from '@/shared/composables/useCookieConsent'

export default {
  name: 'CookieBanner',
  setup() {
    const { showBanner, acceptAll, acceptEssential } = useCookieConsent()
    
    return {
      showBanner,
      acceptAll,
      acceptEssential
    }
  }
}
</script>

<style scoped lang="scss">
@import '@/assets/styles/variables';

.cookie-banner {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: rgba(white, 0.98);
  backdrop-filter: blur(10px);
  border-top: 1px solid $border-color;
  box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
  z-index: 9999;
  padding: 1.5rem;

  @media (max-width: 768px) {
    padding: 1rem;
  }
}

.cookie-container {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 2rem;

  @media (max-width: 768px) {
    flex-direction: column;
    gap: 1rem;
  }
}

.cookie-content {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  flex: 1;
}

.cookie-icon {
  font-size: 2rem;
  flex-shrink: 0;
}

.cookie-text {
  p {
    color: $text-secondary;
    font-size: $font-size-sm;
    line-height: $line-height-relaxed;
    margin: 0;
  }

  .cookie-link {
    color: $terracotta;
    text-decoration: underline;
    font-weight: 500;
    
    &:hover {
      color: darken($terracotta, 10%);
    }
  }
}

.cookie-actions {
  display: flex;
  gap: 1rem;
  flex-shrink: 0;

  @media (max-width: 768px) {
    width: 100%;
    flex-direction: column;
  }
}

.btn-accept,
.btn-essential {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: $border-radius;
  font-weight: 600;
  font-size: $font-size-sm;
  cursor: pointer;
  transition: all 0.3s;
  white-space: nowrap;

  @media (max-width: 768px) {
    width: 100%;
  }
}

.btn-accept {
  background: $terracotta;
  color: white;

  &:hover {
    background: darken($terracotta, 10%);
    transform: translateY(-2px);
  }
}

.btn-essential {
  background: transparent;
  color: $text-secondary;
  border: 1px solid $border-color;

  &:hover {
    background: $bg-secondary;
    border-color: $text-secondary;
  }
}

// Animations
.slide-up-enter-active,
.slide-up-leave-active {
  transition: transform 0.3s ease-out;
}

.slide-up-enter-from {
  transform: translateY(100%);
}

.slide-up-leave-to {
  transform: translateY(100%);
}
</style>