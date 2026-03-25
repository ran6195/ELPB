import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/authStore'
import PageList from '../views/PageList.vue'
import ArchivedPages from '../views/ArchivedPages.vue'
import PageEditor from '../views/PageEditor.vue'
import PagePreview from '../views/PagePreview.vue'
import PublicPage from '../views/PublicPage.vue'
import ThankYouPage from '../views/ThankYouPage.vue'
import Login from '../views/Login.vue'
import Register from '../views/Register.vue'
import AdminPanel from '../views/AdminPanel.vue'
import CompanyPanel from '../views/CompanyPanel.vue'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { requiresGuest: true }
  },
  {
    path: '/register',
    name: 'Register',
    component: Register,
    meta: { requiresGuest: true }
  },
  {
    path: '/',
    name: 'PageList',
    component: PageList,
    meta: { requiresAuth: true }
  },
  {
    path: '/archived',
    name: 'ArchivedPages',
    component: ArchivedPages,
    meta: { requiresAuth: true }
  },
  {
    path: '/admin',
    name: 'AdminPanel',
    component: AdminPanel,
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/company',
    name: 'CompanyPanel',
    component: CompanyPanel,
    meta: { requiresAuth: true, requiresCompany: true }
  },
  {
    path: '/editor/:id?',
    name: 'PageEditor',
    component: PageEditor,
    meta: { requiresAuth: true }
  },
  {
    path: '/preview/:id',
    name: 'PagePreview',
    component: PagePreview,
    meta: { requiresAuth: true }
  },
  {
    path: '/thank-you',
    name: 'ThankYou',
    component: ThankYouPage
  },
  {
    path: '/p/:slug',
    name: 'PublicPage',
    component: PublicPage
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (to.hash) {
      return { el: to.hash, behavior: 'smooth' }
    }
    if (savedPosition) {
      return savedPosition
    }
    return { top: 0 }
  }
})

// Navigation guard
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  // Se la route richiede autenticazione
  if (to.meta.requiresAuth) {
    if (!authStore.isAuthenticated) {
      // Redirect a login se non autenticato
      next({ name: 'Login' })
    } else if (to.meta.requiresAdmin && !authStore.isAdmin) {
      // Redirect a home se non è admin
      next({ name: 'PageList' })
    } else if (to.meta.requiresCompany && !authStore.isCompany) {
      // Redirect a home se non è company
      next({ name: 'PageList' })
    } else {
      next()
    }
  }
  // Se la route è solo per guest (login/register)
  else if (to.meta.requiresGuest) {
    if (authStore.isAuthenticated) {
      // Redirect a home se già autenticato
      next({ name: 'PageList' })
    } else {
      next()
    }
  }
  // Route pubblica
  else {
    next()
  }
})

export default router
