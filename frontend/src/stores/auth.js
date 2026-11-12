import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import api from '@/services/api'

const ROLE_HOME_MAP = {
  admin: '/admin/dashboard',
  penguji: '/penguji/queue',
  siswa: '/siswa/dashboard',
}

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('ujikom_token') || null)
  const user = ref(JSON.parse(localStorage.getItem('ujikom_user') || 'null'))
  const loading = ref(false)
  const verifiedPins = ref({})

  const isAuthenticated = computed(() => Boolean(token.value && user.value))
  const userRole = computed(() => user.value?.role || null)
  const studentProfile = computed(() => user.value?.student || null)
  const roleHomePath = computed(() => ROLE_HOME_MAP[userRole.value] || '/login')

  function persistSession(newToken, newUser) {
    token.value = newToken
    user.value = newUser
    localStorage.setItem('ujikom_token', newToken)
    localStorage.setItem('ujikom_user', JSON.stringify(newUser))
  }

  function clearSession() {
    token.value = null
    user.value = null
    verifiedPins.value = {}
    localStorage.removeItem('ujikom_token')
    localStorage.removeItem('ujikom_user')
  }

  async function login(username, password) {
    loading.value = true
    try {
      const { data } = await api.post('/login', { username, password })
      persistSession(data.token, data.user)
      return data
    } finally {
      loading.value = false
    }
  }

  async function fetchMe() {
    if (!token.value) return null
    try {
      const { data } = await api.get('/me')
      user.value = data.user
      localStorage.setItem('ujikom_user', JSON.stringify(data.user))
      return data.user
    } catch (err) {
      clearSession()
      throw err
    }
  }

  async function logout() {
    try {
      if (token.value) {
        await api.post('/logout')
      }
    } catch {
      // Abaikan error network saat logout agar sesi lokal tetap bersih
    } finally {
      clearSession()
    }
  }

  function setStudentRevisionPin(studentId, pin) {
    verifiedPins.value = {
      ...verifiedPins.value,
      [String(studentId)]: pin,
    }
  }

  function getStudentRevisionPin(studentId) {
    return verifiedPins.value[String(studentId)] || null
  }

  function clearStudentRevisionPin(studentId) {
    const copy = { ...verifiedPins.value }
    delete copy[String(studentId)]
    verifiedPins.value = copy
  }

  return {
    token,
    user,
    loading,
    isAuthenticated,
    userRole,
    studentProfile,
    roleHomePath,
    login,
    fetchMe,
    logout,
    clearSession,
    setStudentRevisionPin,
    getStudentRevisionPin,
    clearStudentRevisionPin,
  }
})
