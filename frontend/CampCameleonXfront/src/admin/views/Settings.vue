<template>
  <div class="settings-page">
    <div class="page-header">
    </div>
    
    <div class="settings-grid">
      <div class="settings-card">
        <h3>Général</h3>
        <div class="settings-section">
          <label>Nom du site</label>
          <input type="text" value="CampCameleonX" class="form-input">
        </div>
        <div class="settings-section">
          <label>Email contact</label>
          <input type="email" value="contact@campcameleonx.com" class="form-input">
        </div>
        <div class="settings-section">
          <label>Mode maintenance</label>
          <label class="toggle-switch">
            <input type="checkbox">
            <span class="toggle-slider"></span>
          </label>
        </div>
      </div>
      
      <div class="settings-card">
        <h3>Notifications</h3>
        <div class="settings-section">
          <label class="checkbox-label">
            <input type="checkbox" checked>
            <span>Nouvelles réservations</span>
          </label>
        </div>
        <div class="settings-section">
          <label class="checkbox-label">
            <input type="checkbox" checked>
            <span>Paiements reçus</span>
          </label>
        </div>
        <div class="settings-section">
          <label class="checkbox-label">
            <input type="checkbox">
            <span>Messages de contact</span>
          </label>
        </div>
      </div>
      
      <div class="settings-card">
        <h3>Sécurité</h3>
        <div class="settings-section">
          <label>Changer le mot de passe</label>
          <button class="btn btn-outline">Modifier</button>
        </div>
        <div class="settings-section">
          <label>Authentification à deux facteurs</label>
          <button class="btn btn-primary">Activer 2FA</button>
        </div>
      </div>
    </div>
    
    <div class="settings-actions">
      <button class="btn btn-success">Sauvegarder</button>
      <button class="btn btn-outline">Annuler</button>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AdminSettings'
}
</script>

<style scoped>
.settings-page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.settings-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.settings-card {
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
  border: 1px solid #e9ecef;
  padding: 1.5rem;
}

.settings-card h3 {
  color: #32325d;
  margin: 0 0 1.5rem 0;
  font-size: 1.125rem;
  border-bottom: 1px solid #f8f9fe;
  padding-bottom: 0.75rem;
}

.settings-section {
  margin-bottom: 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.settings-section label {
  color: #32325d;
  font-weight: 500;
}

.form-input {
  padding: 0.5rem 1rem;
  border: 1px solid #dee2e6;
  border-radius: 0.375rem;
  background-color: #f8f9fe;
  width: 200px;
}

.toggle-switch {
  position: relative;
  width: 50px;
  height: 24px;
}

.toggle-switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.toggle-slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #ccc;
  border-radius: 24px;
  transition: 0.4s;
}

.toggle-slider:before {
  position: absolute;
  content: "";
  height: 18px;
  width: 18px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  border-radius: 50%;
  transition: 0.4s;
}

input:checked + .toggle-slider {
  background-color: #5e72e4;
}

input:checked + .toggle-slider:before {
  transform: translateX(26px);
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
}

.settings-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}
</style>