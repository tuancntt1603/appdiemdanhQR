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
import StudentDashboard from '../views/StudentDashboard.vue'

const routes = [
  { path: '/login', name: 'Login', component: Login, meta: { public: true } },
  {
    path: '/',
    redirect: () => {
      const role = localStorage.getItem('user_role')
      return role === 'student' ? '/student' : '/dashboard'
    }
  },
  // Cổng dành riêng cho Sinh viên
  {
    path: '/student',
    name: 'StudentDashboard',
    component: StudentDashboard,
    meta: { role: 'student' }
  },
  // Cổng Quản trị dành cho Giảng viên & Admin
  { path: '/dashboard', name: 'Dashboard', component: Dashboard, meta: { role: 'lecturer' } },
  { path: '/scan', name: 'QrScanner', component: QrScanner, meta: { role: 'lecturer' } },
  { path: '/practice-sessions', name: 'PracticeSessionManagement', component: PracticeSessionManagement, meta: { role: 'lecturer' } },
  { path: '/students', name: 'StudentManagement', component: StudentManagement, meta: { role: 'lecturer' } },
  { path: '/students/:id', name: 'StudentDetail', component: StudentDetail, meta: { role: 'lecturer' } },
  { path: '/student-qr', name: 'StudentQr', component: StudentQr, meta: { role: 'lecturer' } },
  { path: '/attendance', name: 'AttendanceHistory', component: AttendanceHistory, meta: { role: 'lecturer' } },
  { path: '/reports', name: 'Reports', component: Reports, meta: { role: 'lecturer' } },
  { path: '/workshops', name: 'WorkshopManagement', component: WorkshopManagement, meta: { role: 'lecturer' } },
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Kiểm tra phiên đăng nhập và phân quyền Route Guards
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('auth_token')
  const role = localStorage.getItem('user_role')

  // 1. Chưa đăng nhập
  if (!to.meta.public && !token) {
    return next('/login')
  }

  // 2. Đã đăng nhập nhưng truy cập lại /login
  if (to.path === '/login' && token) {
    if (role === 'student') {
      return next('/student')
    }
    return next('/dashboard')
  }

  // 3. Phân quyền Sinh viên (Student Guard)
  // Sinh viên tuyệt đối không được truy cập các trang quản trị
  if (token && role === 'student') {
    const adminRoutes = [
      '/dashboard',
      '/students',
      '/reports',
      '/scan',
      '/practice-sessions',
      '/workshops',
      '/attendance',
      '/student-qr'
    ]
    const isTryingAdmin = adminRoutes.some(p => to.path === p || to.path.startsWith(p + '/'))
    if (isTryingAdmin) {
      return next('/student')
    }
  }

  // 4. Giảng viên / Admin truy cập /student -> chuyển về /dashboard
  if (token && (role === 'lecturer' || role === 'admin' || role === 'can_bo')) {
    if (to.path === '/student') {
      return next('/dashboard')
    }
  }

  next()
})

export default router
