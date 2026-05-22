<template>
  <div class="legal-page">
    <section class="legal-hero">
      <div class="container">
        <h1>Conditions Générales d'Utilisation</h1>
        <p class="update-date">Dernière mise à jour : {{ updateDate }}</p>
      </div>
    </section>

    <section class="legal-content">
      <div class="container">
        <div class="content-wrapper">
          
          <div class="legal-section">
            <h2>1. Objet</h2>
            <p>Les présentes Conditions Générales d'Utilisation (CGU) régissent l'utilisation du site web CampCameleonX et de ses services de réservation en ligne.</p>
            <p>En accédant et en utilisant ce site, vous acceptez sans réserve les présentes CGU.</p>
          </div>

          <div class="legal-section">
            <h2>2. Accès au site</h2>
            <p>Le site est accessible gratuitement à tout utilisateur disposant d'un accès Internet.</p>
            <p>CampCameleonX se réserve le droit de suspendre, modifier ou interrompre l'accès au site à tout moment, sans préavis.</p>
            <p>Les frais d'accès et d'utilisation du réseau sont à la charge de l'utilisateur.</p>
          </div>

          <div class="legal-section">
            <h2>3. Compte utilisateur</h2>
            <h3>Création de compte</h3>
            <p>La création d'un compte peut être nécessaire pour effectuer une réservation. L'utilisateur s'engage à fournir des informations exactes et à jour.</p>
            <h3>Sécurité</h3>
            <p>L'utilisateur est responsable de la confidentialité de ses identifiants de connexion. Toute utilisation frauduleuse doit être signalée immédiatement.</p>
          </div>

          <div class="legal-section">
            <h2>4. Réservations</h2>
            <h3>Processus de réservation</h3>
            <p>Les réservations s'effectuent via le formulaire en ligne. Une confirmation par email est envoyée après validation du paiement.</p>
            <h3>Paiement</h3>
            <p>Les paiements sont sécurisés via Stripe. CampCameleonX ne conserve aucune donnée bancaire.</p>
            <h3>Annulation et modification</h3>
            <ul>
              <li>Annulation gratuite jusqu'à 7 jours avant l'arrivée</li>
              <li>Entre 7 et 3 jours : 50% du montant remboursé</li>
              <li>Moins de 3 jours : aucun remboursement</li>
            </ul>
            <p>Les modifications sont soumises à disponibilité et peuvent entraîner des frais.</p>
          </div>

          <div class="legal-section">
            <h2>5. Obligations de l'utilisateur</h2>
            <p>L'utilisateur s'engage à :</p>
            <ul>
              <li>Utiliser le site conformément à sa destination</li>
              <li>Ne pas porter atteinte aux droits de propriété intellectuelle</li>
              <li>Ne pas perturber le fonctionnement du site</li>
              <li>Ne pas diffuser de contenu illicite, diffamatoire ou inapproprié</li>
              <li>Respecter les règles de l'établissement lors de son séjour</li>
            </ul>
          </div>

          <div class="legal-section">
            <h2>6. Responsabilité</h2>
            <h3>CampCameleonX</h3>
            <p>CampCameleonX met tout en œuvre pour assurer la fiabilité et la disponibilité du site, mais ne garantit pas l'absence d'interruptions ou d'erreurs.</p>
            <p>CampCameleonX ne peut être tenu responsable :</p>
            <ul>
              <li>Des dommages directs ou indirects résultant de l'utilisation du site</li>
              <li>Des virus ou programmes malveillants</li>
              <li>Des interruptions de service indépendantes de sa volonté</li>
            </ul>
            <h3>Utilisateur</h3>
            <p>L'utilisateur est seul responsable de l'utilisation qu'il fait du site et des conséquences de ses réservations.</p>
          </div>

          <div class="legal-section">
            <h2>7. Propriété intellectuelle</h2>
            <p>Tous les éléments du site (textes, images, logos, vidéos) sont protégés par le droit d'auteur et le droit des marques.</p>
            <p>Toute reproduction ou utilisation non autorisée constitue une contrefaçon sanctionnée par la loi.</p>
          </div>

          <div class="legal-section">
            <h2>8. Données personnelles</h2>
            <p>Le traitement des données personnelles est régi par notre <router-link to="/privacy" class="link">Politique de confidentialité</router-link>.</p>
            <p>En utilisant le site, vous consentez à la collecte et au traitement de vos données conformément à cette politique.</p>
          </div>

          <div class="legal-section">
            <h2>9. Réclamations</h2>
            <p>Toute réclamation doit être adressée par email à : <strong>reclamation@campcameleonx.ma</strong></p>
            <p>CampCameleonX s'engage à traiter toute réclamation dans un délai de 7 jours ouvrés.</p>
          </div>

          <div class="legal-section">
            <h2>10. Modification des CGU</h2>
            <p>CampCameleonX se réserve le droit de modifier les présentes CGU à tout moment.</p>
            <p>Les modifications sont applicables dès leur publication sur le site. Il appartient à l'utilisateur de consulter régulièrement les CGU.</p>
          </div>

          <div class="legal-section">
            <h2>11. Droit applicable et juridiction</h2>
            <p>Les présentes CGU sont régies par le droit marocain.</p>
            <p>En cas de litige, et à défaut de résolution amiable, les tribunaux compétents du Maroc seront seuls compétents.</p>
          </div>

          <div class="legal-section">
            <h2>12. Contact</h2>
            <p>Pour toute question concernant ces CGU :<br>
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
  name: 'CGU',
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

  h3 {
    color: $text-primary;
    font-size: $font-size-lg;
    margin: 1.5rem 0 1rem;
    font-weight: 600;
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

  ul {
    list-style: none;
    padding-left: 0;

    li {
      color: $text-secondary;
      line-height: $line-height-relaxed;
      margin-bottom: 0.75rem;
      padding-left: 1.5rem;
      position: relative;

      &::before {
        content: '•';
        color: $terracotta;
        font-weight: bold;
        position: absolute;
        left: 0;
      }
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