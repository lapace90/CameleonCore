<template>
  <footer class="admin-footer">
    <div class="footer-content">
      <div class="footer-left">
        <span class="copyright">
          © {{ currentYear }} <strong>CampCameleonX</strong>. Tous droits réservés.
        </span>
      </div>
      
      <div class="footer-right">
        <nav class="footer-nav">
          <a href="#" class="footer-link">Support</a>
          <a href="#" class="footer-link">Documentation</a>
          <a href="#" class="footer-link">Licence</a>
        </nav>
        
        <div class="footer-version">
          <span class="version-badge">v{{ appVersion }}</span>
        </div>
      </div>
    </div>
  </footer>
</template>

<script>
export default {
  name: 'AdminFooter',
  data() {
    return {
      appVersion: '1.0.0'
    }
  },
  computed: {
    currentYear() {
      return new Date().getFullYear();
    }
  }
}
</script>

<style scoped>
.admin-footer {
  background-color: #fff;
  border-top: 1px solid #e9ecef;
  margin-left: -0.6rem;
  padding: 1rem 2rem;
  box-shadow: 0 -1px 3px rgba(0, 0, 0, 0.05);
}

.footer-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  max-width: none;
}

.footer-left .copyright {
  color: #8898aa;
  font-size: 0.875rem;
}

.copyright strong {
  color: #32325d;
  font-weight: 600;
}

.footer-right {
  display: flex;
  align-items: center;
  gap: 2rem;
}

.footer-nav {
  display: flex;
  gap: 1.5rem;
}

.footer-link {
  color: #8898aa;
  text-decoration: none;
  font-size: 0.875rem;
  transition: color 0.15s ease;
}

.footer-link:hover {
  color: #5e72e4;
}

.version-badge {
  background-color: #f8f9fe;
  color: #5e72e4;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.75rem;
  font-weight: 500;
  border: 1px solid #e9ecef;
}

/* Responsive */
@media (max-width: 768px) {
  .admin-footer {
    margin-left: 0;
    padding: 1rem;
  }
  
  .footer-content {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }
  
  .footer-nav {
    order: -1;
  }
}
</style>