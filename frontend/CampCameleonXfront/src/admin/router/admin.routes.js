// routes/admin.routes.js

export default [
  {
    path: '',
    component: () => import('../AdminApp.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '', redirect: { name: 'AdminDashboard' } },

      { path: 'dashboard', name: 'AdminDashboard', component: () => import('../views/Dashboard.vue') },

      { path: 'users', name: 'AdminUsers', component: () => import('../views/users/Users.vue') },
      { path: 'users/create', name: 'UserCreate', component: () => import('../views/users/UserForm.vue'), props: { action: 'create' } },
      { path: 'users/:id/edit', name: 'UserEdit', component: () => import('../views/users/UserForm.vue'), props: r => ({ id: r.params.id, action: 'edit' }) },
      { path: 'users/:id', name: 'UserDetail', component: () => import('../views/users/UserDetail.vue'), props: r => ({ id: r.params.id, action: 'view' }) },

      { path: 'roles', name: 'AdminRoles', component: () => import('../views/Roles.vue') },
      { path: 'roles/create', name: 'RoleCreate', component: () => import('../views/RoleForm.vue'), props: { action: 'create' } },
      { path: 'roles/:id/edit', name: 'RoleEdit', component: () => import('../views/RoleForm.vue'), props: r => ({ id: r.params.id, action: 'edit' }) },

      { path: 'permissions', name: 'AdminPermissions', component: () => import('../views/Permissions.vue') },

      { path: 'analytics', name: 'AdminAnalytics', component: () => import('../views/Analytics.vue') },
      { path: 'profile', name: 'AdminProfile', component: () => import('../views/Profile.vue') },
      { path: 'settings', name: 'AdminSettings', component: () => import('../views/Settings.vue') },

      { path: 'agenda', name: 'FullAgenda', component: () => import('@/shared/components/calendar/FullCalendar.vue') },

      // Réservations
      { path: 'reservations', name: 'AdminReservations', component: () => import('@/admin/views/reservations/ReservationsList.vue'), meta: { requiresAuth: true } },
      { path: 'reservations/:id', name: 'ReservationDetail', component: () => import('@/admin/views/reservations/ReservationDetail.vue'), props: true, meta: { requiresAuth: true } },
      { path: 'reservations/create', name: 'ReservationCreate', component: () => import('../views/reservations/ReservationForm.vue'), props: { action: 'create' } },
      { path: 'reservations/:id/edit', name: 'ReservationEdit', component: () => import('../views/reservations/ReservationForm.vue'), props: r => ({ action: 'edit' }) },

      // Redirection vers agenda
      { path: 'calendar', redirect: { name: 'FullAgenda' } },

      // 🆕 FACTURES - Routes ajoutées
      { path: 'invoices', name: 'InvoiceList', component: () => import('@/admin/views/invoices/InvoiceList.vue'), meta: { requiresAuth: true } },
      { path: 'invoices/:id', name: 'InvoiceDetail', component: () => import('@/admin/views/invoices/InvoiceDetail.vue'), props: true, meta: { requiresAuth: true } },

      // Avis clients
      {
        path: 'reviews',
        name: 'AdminReviews',
        component: () => import('@/admin/views/Reviews.vue'),
        meta: { requiresAuth: true, title: 'Gestion des Avis' }
      },
      
      // Catégories
      { path: 'categories', name: 'AdminCategories', component: () => import('../views/Categories.vue') },

      // Produits
      { path: 'products/:type', name: 'ProductsShow', component: () => import('../views/products/ProductsShow.vue'), props: true },
      { path: 'products/:type/create', name: 'ProductCreate', component: () => import('../views/products/ProductForm.vue'), props: r => ({ productType: r.params.type, action: 'create' }) },
      { path: 'products/:type/:id', name: 'ProductDetail', component: () => import('../views/products/ProductDetail.vue'), props: true },
      { path: 'products/:type/:id/edit', name: 'ProductEdit', component: () => import('../views/products/ProductForm.vue'), props: r => ({ productType: r.params.type, action: 'edit' }) },
    ],
  },
]