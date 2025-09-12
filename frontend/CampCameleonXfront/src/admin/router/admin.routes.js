// Import des vues admin
import AdminApp from '../AdminApp.vue'
import Dashboard from '../views/Dashboard.vue'
import Users from '../views/users/Users.vue'
import UserForm from '../views/users/UserForm.vue'
import UserDetail from '../views/users/UserDetail.vue'
import Categories from '../views/Categories.vue'
import Settings from '../views/Settings.vue'
import Analytics from '../views/Analytics.vue'
import FullCalendar from '@/shared/components/calendar/FullCalendar.vue'
import RoleForm from '../views/RoleForm.vue'
import AdminRoles from '../views/Roles.vue'
import Profile from '../views/Profile.vue'

// Import des composants produits
import ProductsShow from '../views/products/ProductsShow.vue'
import ProductDetail from '../views/products/ProductDetail.vue'
import ProductForm from '../views/products/ProductForm.vue'


export default [
  {
    path: '',
    component: AdminApp,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        redirect: '/admin/dashboard'
      },
      {
        path: 'dashboard',
        name: 'AdminDashboard',
        component: Dashboard
      },
      {
        path: 'users',
        name: 'AdminUsers',
        component: Users
      },
      {
        path: 'roles',
        name: 'AdminRoles',
        component: AdminRoles
      },
      {
        path: 'users/create',
        name: 'UserCreate',
        component: UserForm,
        props: { action: 'create' }
      },
      {
        path: 'users/:id/edit',
        name: 'UserEdit',
        component: UserForm,
        props: route => ({ id: route.params.id, action: 'edit' })
      },
      {
        path: 'users/:id',
        name: 'UserDetail',
        component: UserDetail,
        props: route => ({ id: route.params.id, action: 'view' })
      },
      {
        path: 'roles/create',
        name: 'RoleCreate',
        component: RoleForm,
        props: { action: 'create' }
      },
      {
        path: 'roles/:id/edit',
        name: 'RoleEdit',
        component: RoleForm,
        props: route => ({ id: route.params.id, action: 'edit' })
      },
      {
        path: 'permissions',
        name: 'AdminPermissions',
        component: () => import('../views/Permissions.vue')
      },
      {
        path: 'analytics',
        name: 'AdminAnalytics',
        component: Analytics
      },
      {
        path: 'profile',
        name: 'AdminProfile',
        component: Profile
      },
      {
        path: 'settings',
        name: 'AdminSettings',
        component: Settings
      },
      {
        path: 'agenda',
        name: 'FullAgenda',
        component: FullCalendar
      },
      // Routes catégories
      {
        path: 'categories',
        name: 'AdminCategories',
        component: Categories
      },
      // Routes produits
      {
        path: 'products/:type',
        name: 'ProductsShow',
        component: ProductsShow,
        props: true
      },
      {
        path: 'products/:type/create',
        name: 'ProductCreate',
        component: ProductForm,
        props: route => ({
          productType: route.params.type,
          action: 'create'
        })
      },
      {
        path: 'products/:type/:id',
        name: 'ProductDetail',
        component: ProductDetail,
        props: true
      },
      {
        path: 'products/:type/:id/edit',
        name: 'ProductEdit',
        component: ProductForm,
        props: route => ({
          productType: route.params.type,
          action: 'edit'
        })
      }
    ]
  }
]