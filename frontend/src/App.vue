<template>
  <div class="app-layout" :class="{ 'no-sidebar': isPublicRoute }">
    <!-- Sidebar Navigation -->
    <aside v-if="!isPublicRoute" class="sidebar">
      <div class="sidebar-header">
        <div class="app-logo">
          <span class="logo-icon">🏭</span>
          <div>
            <h2 class="app-title">ĐIỂM DANH XƯỞNG</h2>
            <p class="app-subtitle">Hệ thống QR Code cá nhân</p>
          </div>
        </div>
      </div>

      <nav class="sidebar-nav">
        <!-- Menu cho Admin & Cán bộ/Giảng viên -->
        <template v-if="userRole !== 'sinh_vien'">
          <router-link to="/dashboard" class="nav-item" active-class="active">
            <span class="nav-icon">📊</span>
            <span>Bảng điều khiển</span>
          </router-link>

          <router-link to="/scan" class="nav-item scan-highlight" active-class="active">
            <span class="nav-icon">📷</span>
            <span>Quét QR Điểm danh</span>
            <span class="pulse-dot"></span>
          </router-link>

          <router-link to="/practice-sessions" class="nav-item" active-class="active">
            <span class="nav-icon">📅</span>
            <span>Buổi thực hành & Lịch</span>
          </router-link>

          <router-link to="/students" class="nav-item" active-class="active">
            <span class="nav-icon">🎓</span>
            <span>Quản lý sinh viên</span>
          </router-link>

          <router-link to="/student-qr" class="nav-item" active-class="active">
            <span class="nav-icon">📱</span>
            <span>Xem QR Cá nhân (90s)</span>
          </router-link>

          <router-link to="/attendance" class="nav-item" active-class="active">
            <span class="nav-icon">🕒</span>
            <span>Lịch sử điểm danh</span>
          </router-link>

          <router-link to="/reports" class="nav-item" active-class="active">
            <span class="nav-icon">📈</span>
            <span>Chuyên cần & Báo cáo</span>
          </router-link>

          <router-link to="/workshops" class="nav-item" active-class="active">
            <span class="nav-icon">🏢</span>
            <span>Quản lý xưởng</span>
          </router-link>
        </template>

        <!-- Menu dành riêng cho Sinh viên khi đăng nhập -->
        <template v-else>
          <router-link to="/student-qr" class="nav-item" active-class="active">
            <span class="nav-icon">📱</span>
            <span>Mã QR Cá Nhân (90s)</span>
          </router-link>

          <router-link to="/attendance" class="nav-item" active-class="active">
            <span class="nav-icon">🕒</span>
            <span>Lịch sử điểm danh của tôi</span>
          </router-link>
        </template>
      </nav>

      <div class="sidebar-footer">
        <div class="user-info">
          <div class="avatar">{{ userInitial }}</div>
          <div class="user-meta">
            <span class="user-name">{{ userName }}</span>
            <span class="user-role">{{ getRoleName(userRole) }}</span>
          </div>
        </div>
        <button @click="logout" class="btn-logout" title="Đăng xuất">
          🚪
        </button>
      </div>
    </aside>

    <!-- Main Content Area -->
    <div class="main-wrapper">
      <header v-if="!isPublicRoute" class="top-header">
        <div class="header-left">
          <span class="page-crumb">{{ currentRouteName }}</span>
        </div>
        <div class="header-right">
          <router-link to="/scan" class="btn btn-primary btn-sm">
            📷 Quét QR Ngay
          </router-link>
          <div class="clock-display">
            {{ currentTime }}
          </div>
        </div>
      </header>

      <main class="page-content" :class="{ 'full-page': isPublicRoute }">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

const isPublicRoute = computed(() => {
  return route.path === '/login' || route.path === '/student-qr' && !localStorage.getItem('auth_token')
})

const userName = ref(localStorage.getItem('user_name') || 'Cán bộ Xưởng')
const userRole = ref(localStorage.getItem('user_role') || 'can_bo')
const userInitial = computed(() => (userName.value ? userName.value.charAt(0).toUpperCase() : 'A'))

const currentRouteName = computed(() => {
  const map = {
    'Dashboard': 'Bảng điều khiển thống kê',
    'QrScanner': 'Quét mã QR Điểm danh tại cửa xưởng',
    'PracticeSessionManagement': 'Quản lý Buổi thực hành & Lịch điểm danh',
    'StudentManagement': 'Quản lý danh sách sinh viên',
    'StudentDetail': 'Thông tin chi tiết sinh viên',
    'StudentQr': 'Mã QR cá nhân 90 giây',
    'AttendanceHistory': 'Lịch sử lượt vào / ra xưởng',
    'Reports': 'Thống kê chuyên cần & Xuất Excel',
    'WorkshopManagement': 'Quản lý xưởng thực hành',
  }
  return map[route.name] || 'Hệ thống Quản lý'
})

const getRoleName = (role) => {
  if (role === 'admin') return 'Quản trị viên'
  if (role === 'can_bo') return 'Giảng viên / Cán bộ'
  if (role === 'sinh_vien') return 'Sinh viên'
  return 'Người dùng'
}

const currentTime = ref('')
let timer = null

const updateTime = () => {
  const now = new Date()
  currentTime.value = now.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' - ' + now.toLocaleDateString('vi-VN')
}

onMounted(() => {
  updateTime()
  timer = setInterval(updateTime, 1000)
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
})

const logout = () => {
  localStorage.removeItem('auth_token')
  localStorage.removeItem('user_name')
  localStorage.removeItem('user_role')
  router.push('/login')
}
</script>

<style scoped>
.app-layout {
  display: flex;
  min-height: 100vh;
}

.app-layout.no-sidebar {
  display: block;
}

/* Sidebar */
.sidebar {
  width: 260px;
  background-color: #1e293b;
  color: #f8fafc;
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
  border-right: 1px solid #334155;
}

.sidebar-header {
  padding: 1.5rem 1.25rem;
  border-bottom: 1px solid #334155;
}

.app-logo {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.logo-icon {
  font-size: 1.8rem;
  background: #334155;
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--radius-md);
}

.app-title {
  font-size: 1rem;
  font-weight: 700;
  letter-spacing: 0.02em;
  color: #ffffff;
}

.app-subtitle {
  font-size: 0.72rem;
  color: #94a3b8;
}

.sidebar-nav {
  padding: 1.2rem 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  flex: 1;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  padding: 0.75rem 1rem;
  color: #94a3b8;
  border-radius: var(--radius-sm);
  font-size: 0.9rem;
  font-weight: 500;
  transition: all 0.2s ease;
  position: relative;
}

.nav-item:hover {
  background: #334155;
  color: #ffffff;
}

.nav-item.active {
  background: var(--primary);
  color: #ffffff;
  font-weight: 600;
}

.scan-highlight {
  background: rgba(37, 99, 235, 0.15);
  color: #60a5fa;
  border: 1px dashed rgba(96, 165, 250, 0.4);
}

.pulse-dot {
  width: 8px;
  height: 8px;
  background-color: #10b981;
  border-radius: 50%;
  margin-left: auto;
  box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
  animation: pulse 1.8s infinite;
}

@keyframes pulse {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
  70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

.nav-icon {
  font-size: 1.15rem;
}

.sidebar-footer {
  padding: 1rem 1.25rem;
  border-top: 1px solid #334155;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--primary);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.9rem;
}

.user-meta {
  display: flex;
  flex-direction: column;
}

.user-name {
  font-size: 0.85rem;
  font-weight: 600;
  color: #f1f5f9;
}

.user-role {
  font-size: 0.72rem;
  color: #94a3b8;
}

.btn-logout {
  background: none;
  border: none;
  font-size: 1.2rem;
  cursor: pointer;
  padding: 0.4rem;
  border-radius: var(--radius-sm);
  transition: 0.2s;
}

.btn-logout:hover {
  background: #334155;
}

/* Main Wrapper */
.main-wrapper {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.top-header {
  height: 64px;
  background: #ffffff;
  border-bottom: 1px solid var(--gray-200);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 2rem;
}

.page-crumb {
  font-weight: 600;
  font-size: 1.05rem;
  color: var(--gray-800);
}

.header-right {
  display: flex;
  align-items: center;
  gap: 1.25rem;
}

.clock-display {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--gray-600);
  background: var(--gray-100);
  padding: 0.4rem 0.8rem;
  border-radius: var(--radius-sm);
}

.btn-sm {
  padding: 0.45rem 0.9rem;
  font-size: 0.82rem;
}

.page-content {
  padding: 1.75rem 2rem;
  flex: 1;
}

.page-content.full-page {
  padding: 0;
}
</style>
