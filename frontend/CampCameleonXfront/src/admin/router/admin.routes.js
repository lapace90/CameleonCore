// Import des vues admin
import AdminApp from '../AdminApp.vue'
import Dashboard from '../views/Dashboard.vue'
import Users from '../views/Users.vue'
import Settings from '../views/Settings.vue'
import Analytics from '../views/Analytics.vue'
import FullCalendar from '@/shared/components/calendar/FullCalendar.vue'

// Import des composants produits
import ProductsShow from '../views/products/ProductsShow.vue'
import ProductDetail from '../views/products/ProductDetail.vue'
import ProductForm from '../views/products/ProductForm.vue'

export default [
  {
    path: '',
    component: AdminApp,
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
        path: 'analytics',
        name: 'AdminAnalytics',
        component: Analytics
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
        component: () => import('../views/Categories.vue')
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