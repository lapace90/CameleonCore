# Guide Utilisateur - CampCameleonX

## Back-Office Administration

### Connexion

1. Accéder à l'URL du site : `https://campcameleonx.ipace.dev/admin/`
2. Entrer vos identifiants (email + mot de passe)
3. Cliquer sur **Connexion**

---

## Dashboard

Le tableau de bord affiche en temps réel :

- **Statistiques** : Réservations, revenus, taux d'occupation
- **Notifications** : Nouvelles réservations, avis en attente, alertes
- **Calendrier** : Vue hebdomadaire des réservations à venir

---

## Gestion du Catalogue

### Produits (Activités, Hébergements, Menus, Plats)

**Créer un produit :**

1. Menu **Catalogue** → Type de produit souhaité
2. Cliquer sur **+ Nouveau**
3. Remplir les informations :
   - Nom, description, prix
   - Catégorie
   - Image (optionnel)
   - Détails spécifiques selon le type
4. Choisir le statut : **Actif**, **Inactif**, ou **Brouillon**
5. **Enregistrer**

**Modifier un produit :**

1. Cliquer sur le produit dans la liste
2. Modifier les champs souhaités
3. **Enregistrer**

**Supprimer un produit :**

1. Cliquer sur l'icône corbeille
2. Confirmer la suppression

> ⚠️ Un produit lié à des réservations ne peut pas être supprimé.

---

## Gestion des Réservations

### Liste des réservations

- **Filtres** : Par statut, date, client
- **Recherche** : Par nom ou numéro de réservation
- **Export** : Télécharger en CSV

### Statuts de réservation

| Statut | Description | Actions possibles |
|--------|-------------|-------------------|
| En attente | Paiement non reçu | Annuler |
| Confirmée | Paiement validé | Check-in, Annuler |
| Checked-in | Client arrivé | Check-out |
| Checked-out | Séjour terminé | - |
| Annulée | Réservation annulée | - |
| No-show | Client absent | - |

### Effectuer un Check-in

1. Ouvrir la réservation **Confirmée**
2. Cliquer sur **Check-in**
3. Confirmer l'heure d'arrivée (par défaut : maintenant)

### Effectuer un Check-out

1. Ouvrir la réservation **Checked-in**
2. Cliquer sur **Check-out**
3. Confirmer l'heure de départ

---

## Calendrier (Agenda)

### Navigation

- **Vue mois/semaine/jour** : Boutons en haut à droite
- **Aujourd'hui** : Revenir à la date actuelle
- **Flèches** : Naviguer dans le temps

### Informations affichées

- Couleur par type d'hébergement
- Nom du client
- Dates de séjour

### Actions

- **Clic sur une réservation** : Ouvrir les détails
- **Drag & Drop** : Modifier les dates (si activé)

---

## Facturation

### Générer une facture

Les factures sont générées automatiquement lors de la confirmation d'une réservation.

### Actions sur une facture

| Action | Description |
|--------|-------------|
| **Voir** | Afficher le détail |
| **Télécharger PDF** | Télécharger la facture |
| **Envoyer par email** | Envoyer au client |
| **Marquer payée** | Changer le statut |

### Statuts de facture

- **En attente** : Non payée
- **Payée** : Paiement reçu
- **En retard** : Échéance dépassée (marqué automatiquement chaque nuit)

---

## Gestion des Utilisateurs

### Créer un utilisateur

1. Menu **Utilisateurs** → **+ Nouveau**
2. Remplir : Nom, email, mot de passe
3. Assigner un ou plusieurs **rôles**
4. **Enregistrer**

### Rôles disponibles

| Rôle | Permissions |
|------|-------------|
| **Super Admin** | Accès total |
| **Admin** | Gestion complète sauf config système |
| **Manager** | Catalogue, réservations, équipe |
| **Réceptionniste** | Réservations, check-in/out |
| **Chef** | Menus, plats, ingrédients |
| **Comptabilité** | Factures, statistiques financières |

### Modifier les permissions

1. Menu **Rôles & Permissions**
2. Sélectionner un rôle
3. Cocher/décocher les permissions
4. **Enregistrer**

---

## Notifications

### Types de notifications

- 🔔 **Nouvelle réservation**
- 💳 **Paiement reçu**
- ⭐ **Nouvel avis**
- ⚠️ **Facture en retard**
- 📅 **Arrivée prévue aujourd'hui**

### Actions

- **Clic** : Ouvrir l'élément concerné
- **Marquer comme lu** : Icône ✓
- **Tout marquer comme lu** : Bouton en haut du panneau

---

## Site Public (Client)

### Parcours de réservation

1. **Choix des dates** : Sélection arrivée/départ
2. **Nombre de personnes** : Adultes, enfants
3. **Sélection des prestations** :
   - Hébergement (obligatoire)
   - Activités (optionnel)
   - Options repas (optionnel)
4. **Récapitulatif** : Vérification du devis
5. **Identification** : Email du client
6. **Paiement** : Redirection Stripe
7. **Confirmation** : Email avec détails

### Validation email

- Le client reçoit un email avec un lien de validation
- Le lien permet d'accéder au devis pour modification ou paiement

---

## FAQ

### Comment annuler une réservation ?

1. Ouvrir la réservation
2. Cliquer sur **Annuler**
3. Sélectionner le motif
4. Confirmer

### Comment modifier le prix d'un devis ?

1. Ouvrir le devis en attente
2. Modifier les éléments ou appliquer une remise
3. **Enregistrer**
4. Le client recevra le devis mis à jour

### Comment voir les statistiques ?

Menu **Dashboard** → Section **Statistiques**

Données disponibles :
- Chiffre d'affaires (jour/semaine/mois)
- Taux d'occupation
- Réservations par type de produit
- Top produits

### Comment contacter le support technique ?

Email : support@campcameleonx.com

---

*Guide rédigé pour CampCameleonX v1.0*