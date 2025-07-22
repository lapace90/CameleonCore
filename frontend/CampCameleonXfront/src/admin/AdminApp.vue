<template>
  <div class="admin-layout">
    <!-- Sidebar -->
    <AdminSidebar />
    
      <!-- Header -->
      <AdminHeader />
      
      <!-- Page content -->
      <main class="admin-content-wrapper">
        <router-view />
      </main>
      
      <!-- Footer -->
      <AdminFooter />
    </div>

</template>

<script>
import AdminSidebar from './components/layout/Sidebar.vue'
import AdminHeader from './components/layout/Header.vue'
import AdminFooter from './components/layout/Footer.vue'

export default {
  name: 'AdminApp',
  components: {
    AdminSidebar,
    AdminHeader,
    AdminFooter
  }
}
</script>
