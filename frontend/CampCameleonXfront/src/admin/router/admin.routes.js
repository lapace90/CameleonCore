// Import des vues admin
import AdminApp from '../AdminApp.vue'
import Dashboard from '../views/Dashboard.vue'
import Users from '../views/Users.vue'
import Settings from '../views/Settings.vue'
import Analytics from '../views/Analytics.vue'

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
      }
    ]
  }
]