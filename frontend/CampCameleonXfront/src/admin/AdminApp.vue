<template>
  <div class="admin-layout">
    <!-- Sidebar -->
    <AdminSidebar />

    <!-- Header -->
    <AdminHeader />

    <!-- Page content -->
    <div class="admin-container">
      <main class="admin-content-wrapper">
        <router-view :key="$route.fullPath"/>
      </main>
    </div>
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
<style>
@import './src/assets/styles/shared.scss';
</style>