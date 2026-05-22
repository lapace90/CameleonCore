<template>
  <div class="legal-page">
    <section class="legal-hero">
      <div class="container">
        <h1>Mentions Légales</h1>
        <p class="update-date">Dernière mise à jour : {{ updateDate }}</p>
      </div>
    </section>

    <section class="legal-content">
      <div class="container">
        <div class="content-wrapper">
          
          <div class="legal-section">
            <h2>1. Éditeur du site</h2>
            <p><strong>Raison sociale :</strong> CampCameleonX SARL</p>
            <p><strong>Siège social :</strong> Désert de Merzouga, Erg Chebbi, Maroc</p>
            <p><strong>Email :</strong> contact@campcameleonx.ma</p>
            <p><strong>Téléphone :</strong> +212 6 12 34 56 78</p>
            <p><strong>Directeur de publication :</strong> [Nom du responsable]</p>
          </div>

          <div class="legal-section">
            <h2>2. Hébergement</h2>
            <p><strong>Hébergeur :</strong> [Nom hébergeur]</p>
            <p><strong>Adresse :</strong> [Adresse hébergeur]</p>
            <p><strong>Contact :</strong> [Contact hébergeur]</p>
          </div>

          <div class="legal-section">
            <h2>3. Propriété intellectuelle</h2>
            <p>L'ensemble du contenu de ce site (textes, images, vidéos, logos) est la propriété exclusive de CampCameleonX, sauf mention contraire.</p>
            <p>Toute reproduction, représentation, modification, publication ou adaptation totale ou partielle du site est interdite sans autorisation écrite préalable.</p>
          </div>

          <div class="legal-section">
            <h2>4. Protection des données personnelles</h2>
            <p>Les informations recueillies font l'objet d'un traitement informatique destiné à la gestion des réservations et à la relation client.</p>
            <p>Conformément à la loi, vous disposez d'un droit d'accès, de rectification et de suppression des données vous concernant.</p>
            <p>Pour plus d'informations, consultez notre <router-link to="/privacy" class="link">Politique de confidentialité</router-link>.</p>
          </div>

          <div class="legal-section">
            <h2>5. Cookies</h2>
            <p>Ce site utilise des cookies pour améliorer l'expérience utilisateur. En naviguant sur ce site, vous acceptez l'utilisation de cookies conformément à notre politique de confidentialité.</p>
          </div>

          <div class="legal-section">
            <h2>6. Limitation de responsabilité</h2>
            <p>CampCameleonX s'efforce d'assurer l'exactitude des informations diffusées sur ce site, mais ne peut garantir l'absence d'erreurs ou d'omissions.</p>
            <p>CampCameleonX décline toute responsabilité pour les dommages directs ou indirects pouvant résulter de l'utilisation de ce site.</p>
          </div>

          <div class="legal-section">
            <h2>7. Droit applicable</h2>
            <p>Les présentes mentions légales sont régies par le droit marocain.</p>
            <p>Tout litige relatif à l'utilisation de ce site sera soumis aux tribunaux compétents du Maroc.</p>
          </div>

          <div class="legal-section">
            <h2>8. Contact</h2>
            <p>Pour toute question concernant ces mentions légales :<br>
            <strong>Email :</strong> legal@campcameleonx.ma<br>
            <strong>Formulaire :</strong> <router-link to="/contact" class="link">Page contact</router-link></p>
          </div>

        </div>
      </div>
    </section>
  </div>
</template>

<script>
export default {
  name: 'Mentions',
  computed: {
    updateDate() {
      return new Date().toLocaleDateString('fr-FR', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
      })
    }
  }
}
</script>

<style scoped lang="scss">
@use '@/assets/styles/variables' as *;

.legal-page {
  min-height: 100vh;
  background-color: $bg-secondary;
}

.legal-hero {
  background: $gradient-hero;
  color: white;
  padding: 8rem 0 3rem;
  text-align: center;
  margin-top: -90px;

  h1 {
    font-size: $font-size-5xl;
    font-family: $font-family-display;
    margin-bottom: 1rem;
  }

  .update-date {
    font-size: $font-size-sm;
    opacity: 0.9;
  }
}

.legal-content {
  padding: 4rem 0;
}

.content-wrapper {
  max-width: 900px;
  margin: 0 auto;
  background: white;
  padding: 3rem;
  border-radius: $border-radius-xl;
  box-shadow: $shadow-soft;

  @media (max-width: 768px) {
    padding: 2rem 1.5rem;
  }
}

.legal-section {
  margin-bottom: 3rem;

  &:last-child {
    margin-bottom: 0;
  }

  h2 {
    color: $text-primary;
    font-size: $font-size-2xl;
    font-family: $font-family-display;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid $terracotta;
  }

  p {
    color: $text-secondary;
    line-height: $line-height-relaxed;
    margin-bottom: 1rem;

    strong {
      color: $text-primary;
      font-weight: 600;
    }
  }

  .link {
    color: $terracotta;
    text-decoration: none;
    border-bottom: 1px solid transparent;
    transition: border-color 0.3s;

    &:hover {
      border-bottom-color: $terracotta;
    }
  }
}
</style>