<template>
    <section class="desert-hero">
        <div class="hero-content">
            <h1 class="hero-title">
                Vivez une expérience unique<br>
                au cœur du désert marocain.
            </h1>
            <p class="hero-subtitle">
                Reposez-vous, explorez, et laissez-vous charmer par le Camp Caméléon.
            </p>
        </div>
    </section>
</template>

<script>
export default {
    name: 'HeroSection'
}
</script>

<style lang="scss" scoped>
// Import de vos variables
@import '@/assets/styles/variables';

.desert-hero {
    height: 100vh;
    background: linear-gradient(135deg,
            rgba($coffee, 0.3) 0%,
            rgba($terracotta, 0.2) 50%,
            rgba($coffee, 0.4) 100%),
        url('@/assets/images/site/desert1.jpg') center/cover;

    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    color: white;
    position: relative;
    overflow: hidden;


    // Effet overlay subtil
    &::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: radial-gradient(ellipse at center,
                transparent 0%,
                rgba($coffee, 0.1) 70%,
                rgba($coffee, 0.3) 100%);
        pointer-events: none;
    }

    .hero-content {
        position: relative;
        z-index: 2;
        max-width: 1200px;
        padding: 0 2rem;
        animation: heroFadeIn 1.5s ease-out;

        @media (max-width: 768px) {
            padding: 0 1rem;
        }
    }

    .hero-title {
        margin-top: 10rem;
        font-size: 7rem;
        font-weight: $font-weight-light;
        line-height: $line-height-tight;
        margin-bottom: 2rem;
        font-family: $font-family-display;
        text-shadow: 0 2px 10px rgba($coffee, 0.5);

        .highlight {
            font-weight: $font-weight-semibold;
            background: $gradient-warm;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: inline-block;
        }
    }


    .hero-subtitle {
        font-weight: $font-weight-light;
        font-style: italic;
        opacity: 0.95;
        line-height: $line-height-relaxed;
        max-width: 700px;
        margin: 0 auto;
        text-shadow: 0 1px 5px rgba($coffee, 0.3);
        font-family: $font-family-primary;
    }
}

@keyframes heroFadeIn {
    from {
        opacity: 0;
        transform: translateY(30px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

// Responsive
@media (max-width: 768px) {
    .desert-hero {
        height: 90vh;

        .hero-title {
            margin-bottom: 1.5rem;
        }

        .hero-subtitle {
            font-size: $font-size-lg;
        }
    }
}
</style>