import { createRouter, createWebHistory } from 'vue-router'

import Login from '../views/Login.vue'
import Dashboard from '../views/Dashboard.vue'
import QrScanner from '../views/QrScanner.vue'
import StudentManagement from '../views/StudentManagement.vue'
import StudentDetail from '../views/StudentDetail.vue'
import StudentQr from '../views/StudentQr.vue'
import AttendanceHistory from '../views/AttendanceHistory.vue'
import Reports from '../views/Reports.vue'
import WorkshopManagement from '../views/WorkshopManagement.vue'
import PracticeSessionManagement from '../views/PracticeSessionManagement.vue'

const routes = [
  { path: '/login', name: 'Login', component: Login, meta: { public: true } },
  { path: '/', redirect: '/dashboard' },
  { path: '/dashboard', name: 'Dashboard', component: Dashboard },
  { path: '/scan', name: 'QrScanner', component: QrScanner },
  { path: '/practice-sessions', name: 'PracticeSessionManagement', component: PracticeSessionManagement },
  { path: '/students', name: 'StudentManagement', component: StudentManagement },
  { path: '/students/:id', name: 'StudentDetail', component: StudentDetail },
  { path: '/student-qr', name: 'StudentQr', component: StudentQr, meta: { public: true } },
  { path: '/attendance', name: 'AttendanceHistory', component: AttendanceHistory },
  { path: '/reports', name: 'Reports', component: Reports },
  { path: '/workshops', name: 'WorkshopManagement', component: WorkshopManagement },
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Kiểm tra phiên đăng nhập cho các trang nội bộ
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('auth_token')
  if (!to.meta.public && !token) {
    next('/login')
  } else {
    next()
  }
})

export default router
