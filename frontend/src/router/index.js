import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const LoginView = () => import('@/views/LoginView.vue')
const SiswaDashboardView = () => import('@/views/siswa/SiswaDashboardView.vue')
const PengujiQueueView = () => import('@/views/penguji/PengujiQueueView.vue')
const PengujiAssessView = () => import('@/views/penguji/PengujiAssessView.vue')
const AdminDashboardView = () => import('@/views/admin/AdminDashboardView.vue')

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      redirect: '/login',
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { guestOnly: true },
    },
    {
      path: '/siswa/dashboard',
      name: 'siswa.dashboard',
      component: SiswaDashboardView,
      meta: { requiresAuth: true, roles: ['siswa'] },
    },
    {
      path: '/penguji/queue',
      name: 'penguji.queue',
      component: PengujiQueueView,
      meta: { requiresAuth: true, roles: ['penguji', 'admin'] },
    },
    {
      path: '/penguji/assess/:studentId',
      name: 'penguji.assess',
      component: PengujiAssessView,
      meta: { requiresAuth: true, roles: ['penguji', 'admin'] },
    },
    {
      path: '/admin/dashboard',
      name: 'admin.dashboard',
      component: AdminDashboardView,
      meta: { requiresAuth: true, roles: ['admin'] },
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: '/login',
    },
  ],
})

let hasHydratedUser = false

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (auth.token && !hasHydratedUser) {
    hasHydratedUser = true
    try {
      await auth.fetchMe()
    } catch {
      return { name: 'login' }
    }
  }

  if (to.meta.guestOnly && auth.isAuthenticated) {
    return auth.roleHomePath
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.roles && !to.meta.roles.includes(auth.userRole)) {
    return auth.roleHomePath
  }

  return true
})

export default router
