<template>
    <div class="privacy-page">
        <section class="privacy-hero">
            <div class="container">
                <h1>Politique de Confidentialité</h1>
                <p class="update-date">Dernière mise à jour : {{ updateDate }}</p>
            </div>
        </section>

        <section class="privacy-content">
            <div class="container">
                <div class="content-wrapper">

                    <div class="privacy-section">
                        <h2>1. Responsable du traitement</h2>
                        <p>CampCameleonX, maison d'hôtes située au Maroc.<br>
                            Contact : contact@campcameleonx.ma</p>
                    </div>

                    <div class="privacy-section">
                        <h2>2. Données collectées</h2>
                        <h3>Lors de la réservation :</h3>
                        <ul>
                            <li>Identité : nom, prénom</li>
                            <li>Contact : email, téléphone</li>
                            <li>Réservation : dates, nombre de personnes, préférences</li>
                            <li>Paiement : via Stripe (aucune donnée bancaire stockée)</li>
                        </ul>
                        <h3>Formulaire de contact :</h3>
                        <ul>
                            <li>Nom, prénom, email, téléphone, message</li>
                        </ul>
                    </div>

                    <div class="privacy-section">
                        <h2>3. Finalités du traitement</h2>
                        <ul>
                            <li>Traitement et suivi de vos réservations</li>
                            <li>Communication commerciale (avec consentement)</li>
                            <li>Amélioration de nos services</li>
                            <li>Respect des obligations légales</li>
                        </ul>
                    </div>

                    <div class="privacy-section">
                        <h2>4. Base légale</h2>
                        <p>Le traitement de vos données repose sur :</p>
                        <ul>
                            <li>L'exécution du contrat (réservation)</li>
                            <li>Votre consentement (newsletter, cookies non essentiels)</li>
                            <li>Nos obligations légales (facturation)</li>
                        </ul>
                    </div>

                    <div class="privacy-section">
                        <h2>5. Durée de conservation</h2>
                        <ul>
                            <li>Données clients : 3 ans après dernière interaction</li>
                            <li>Données comptables : 10 ans (obligation légale)</li>
                            <li>Notifications système : 7 jours</li>
                            <li>Cookies : 13 mois maximum</li>
                        </ul>
                    </div>

                    <div class="privacy-section">
                        <h2>6. Vos droits</h2>
                        <p>Conformément au RGPD, vous disposez des droits suivants :</p>
                        <ul>
                            <li><strong>Accès :</strong> obtenir une copie de vos données</li>
                            <li><strong>Rectification :</strong> corriger vos données inexactes</li>
                            <li><strong>Effacement :</strong> supprimer vos données (droit à l'oubli)</li>
                            <li><strong>Opposition :</strong> refuser certains traitements</li>
                            <li><strong>Portabilité :</strong> récupérer vos données dans un format structuré</li>
                            <li><strong>Limitation :</strong> restreindre le traitement de vos données</li>
                        </ul>
                        <p>Pour exercer vos droits, contactez-nous à : <strong>privacy@campcameleonx.ma</strong></p>
                    </div>

                    <div class="privacy-section" id="cookies">
                        <h2>7. Cookies</h2>
                        <h3>Cookies essentiels (obligatoires) :</h3>
                        <ul>
                            <li>Session utilisateur</li>
                            <li>Sécurité (CSRF)</li>
                        </ul>
                        <h3>Cookies fonctionnels (avec consentement) :</h3>
                        <ul>
                            <li>Préférences utilisateur</li>
                            <li>Langue</li>
                        </ul>
                        <p>Vous pouvez gérer vos préférences cookies via la bannière affichée lors de votre première
                            visite.</p>
                    </div>

                    <div class="privacy-section">
                        <h2>8. Sécurité</h2>
                        <p>Nous mettons en œuvre des mesures techniques et organisationnelles appropriées :</p>
                        <ul>
                            <li>Chiffrement des données (HTTPS)</li>
                            <li>Authentification sécurisée (Laravel Sanctum)</li>
                            <li>Aucun stockage de données bancaires (paiement via Stripe)</li>
                            <li>Accès restreint aux données (système RBAC)</li>
                        </ul>
                    </div>

                    <div class="privacy-section">
                        <h2>9. Transferts de données</h2>
                        <p>Vos données peuvent être transmises à :</p>
                        <ul>
                            <li><strong>Stripe :</strong> traitement des paiements (certifié PCI-DSS)</li>
                            <li><strong>Hébergeur :</strong> stockage sécurisé des données</li>
                        </ul>
                        <p>Ces prestataires respectent le RGPD et garantissent la sécurité de vos données.</p>
                    </div>

                    <div class="privacy-section">
                        <h2>10. Contact</h2>
                        <p>Pour toute question concernant vos données personnelles :<br>
                            <strong>Email :</strong> privacy@campcameleonx.ma<br>
                            <strong>Formulaire :</strong> <router-link to="/contact" class="link">Page
                                contact</router-link>
                        </p>
                    </div>

                </div>
            </div>
        </section>
    </div>
</template>

<script>
export default {
    name: 'Privacy',
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

.privacy-page {
    min-height: 100vh;
    background-color: $bg-secondary;
}

.privacy-hero {
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

.privacy-content {
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

.privacy-section {
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

            strong {
                color: $text-primary;
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