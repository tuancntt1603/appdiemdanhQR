<template>
  <div class="app-layout" :class="{ 'no-sidebar': isPublicRoute }">
    <!-- Backdrop Overlay on Mobile -->
    <div
      v-if="!isPublicRoute && mobileMenuOpen"
      class="sidebar-backdrop"
      @click="closeMobileMenu"
    ></div>

    <!-- Sidebar Navigation (Drawer on Mobile) -->
    <aside
      v-if="!isPublicRoute"
      class="sidebar"
      :class="{ 'mobile-open': mobileMenuOpen }"
    >
      <div class="sidebar-header">
        <div class="app-logo">
          <span class="logo-icon">🏭</span>
          <div>
            <h2 class="app-title">ĐIỂM DANH XƯỞNG</h2>
            <p class="app-subtitle">Hệ thống QR Code cá nhân</p>
          </div>
        </div>
        <!-- Mobile Close Drawer Button -->
        <button class="btn-close-drawer" @click="closeMobileMenu" aria-label="Đóng menu">
          &times;
        </button>
      </div>

      <nav class="sidebar-nav">
        <!-- Menu cho Admin & Cán bộ/Giảng viên -->
        <template v-if="userRole !== 'sinh_vien'">
          <router-link to="/dashboard" class="nav-item" active-class="active" @click="closeMobileMenu">
            <span class="nav-icon">📊</span>
            <span>Bảng điều khiển</span>
          </router-link>

          <router-link to="/scan" class="nav-item scan-highlight" active-class="active" @click="closeMobileMenu">
            <span class="nav-icon">📷</span>
            <span>Quét QR Điểm danh</span>
            <span class="pulse-dot"></span>
          </router-link>

          <router-link to="/practice-sessions" class="nav-item" active-class="active" @click="closeMobileMenu">
            <span class="nav-icon">📅</span>
            <span>Buổi thực hành & Lịch</span>
          </router-link>

          <router-link to="/students" class="nav-item" active-class="active" @click="closeMobileMenu">
            <span class="nav-icon">🎓</span>
            <span>Quản lý sinh viên</span>
          </router-link>

          <router-link to="/student-qr" class="nav-item" active-class="active" @click="closeMobileMenu">
            <span class="nav-icon">📱</span>
            <span>Xem QR Cá nhân (90s)</span>
          </router-link>

          <router-link to="/attendance" class="nav-item" active-class="active" @click="closeMobileMenu">
            <span class="nav-icon">🕒</span>
            <span>Lịch sử điểm danh</span>
          </router-link>

          <router-link to="/reports" class="nav-item" active-class="active" @click="closeMobileMenu">
            <span class="nav-icon">📈</span>
            <span>Chuyên cần & Báo cáo</span>
          </router-link>

          <router-link to="/workshops" class="nav-item" active-class="active" @click="closeMobileMenu">
            <span class="nav-icon">🏢</span>
            <span>Quản lý xưởng</span>
          </router-link>
        </template>

        <!-- Menu dành riêng cho Sinh viên khi đăng nhập -->
        <template v-else>
          <router-link to="/student-qr" class="nav-item" active-class="active" @click="closeMobileMenu">
            <span class="nav-icon">📱</span>
            <span>Mã QR Cá Nhân (90s)</span>
          </router-link>

          <router-link to="/attendance" class="nav-item" active-class="active" @click="closeMobileMenu">
            <span class="nav-icon">🕒</span>
            <span>Lịch sử điểm danh của tôi</span>
          </router-link>
        </template>
      </nav>

      <div class="sidebar-pwa-wrap">
        <InstallPWA :compact="true" />
      </div>

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
          <!-- Mobile Hamburger Toggle -->
          <button class="btn-hamburger" @click="mobileMenuOpen = !mobileMenuOpen" aria-label="Mở menu">
            <span class="hamburger-bar"></span>
            <span class="hamburger-bar"></span>
            <span class="hamburger-bar"></span>
          </button>
          <span class="page-crumb">{{ currentRouteName }}</span>
        </div>
        <div class="header-right">
          <router-link v-if="userRole !== 'sinh_vien'" to="/scan" class="btn btn-primary btn-sm btn-quick-scan">
            📷 <span class="btn-text">Quét QR</span>
          </router-link>
          <div class="clock-display">
            {{ currentTime }}
          </div>
        </div>
      </header>

      <main class="page-content" :class="{ 'full-page': isPublicRoute }">
        <router-view />
      </main>

      <!-- Mobile Bottom Navigation Bar (Dành cho điện thoại) -->
      <nav v-if="!isPublicRoute" class="mobile-bottom-nav">
        <template v-if="userRole === 'sinh_vien'">
          <router-link to="/student-qr" class="bottom-nav-item" active-class="active">
            <span class="bottom-icon">📱</span>
            <span class="bottom-label">QR của tôi</span>
          </router-link>
          <router-link to="/attendance" class="bottom-nav-item" active-class="active">
            <span class="bottom-icon">🕒</span>
            <span class="bottom-label">Lịch sử</span>
          </router-link>
          <button @click="logout" class="bottom-nav-item btn-bottom-logout">
            <span class="bottom-icon">🚪</span>
            <span class="bottom-label">Đăng xuất</span>
          </button>
        </template>
        <template v-else>
          <router-link to="/dashboard" class="bottom-nav-item" active-class="active">
            <span class="bottom-icon">📊</span>
            <span class="bottom-label">Thống kê</span>
          </router-link>
          <router-link to="/scan" class="bottom-nav-item highlight" active-class="active">
            <div class="scan-circle">📷</div>
            <span class="bottom-label">Quét QR</span>
          </router-link>
          <router-link to="/student-qr" class="bottom-nav-item" active-class="active">
            <span class="bottom-icon">📱</span>
            <span class="bottom-label">Mã QR</span>
          </router-link>
          <router-link to="/attendance" class="bottom-nav-item" active-class="active">
            <span class="bottom-icon">🕒</span>
            <span class="bottom-label">Lịch sử</span>
          </router-link>
          <button @click="mobileMenuOpen = true" class="bottom-nav-item">
            <span class="bottom-icon">☰</span>
            <span class="bottom-label">Menu</span>
          </button>
        </template>
      </nav>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import InstallPWA from './components/InstallPWA.vue'

const route = useRoute()
const router = useRouter()

const mobileMenuOpen = ref(false)

const closeMobileMenu = () => {
  mobileMenuOpen.value = false
}

const isPublicRoute = computed(() => {
  return route.path === '/login' || (route.path === '/student-qr' && !localStorage.getItem('auth_token'))
})

const userName = ref(localStorage.getItem('user_name') || 'Cán bộ Xưởng')
const userRole = ref(localStorage.getItem('user_role') || 'can_bo')
const userInitial = computed(() => (userName.value ? userName.value.charAt(0).toUpperCase() : 'A'))

const currentRouteName = computed(() => {
  const map = {
    'Dashboard': 'Bảng điều khiển',
    'QrScanner': 'Quét mã QR Điểm danh',
    'PracticeSessionManagement': 'Buổi thực hành & Lịch',
    'StudentManagement': 'Quản lý sinh viên',
    'StudentDetail': 'Chi tiết sinh viên',
    'StudentQr': 'Mã QR cá nhân (90s)',
    'AttendanceHistory': 'Lịch sử lượt vào / ra',
    'Reports': 'Thống kê & Báo cáo',
    'WorkshopManagement': 'Quản lý xưởng',
  }
  return map[route.name] || 'Hệ thống Quản lý'
})

const getRoleName = (role) => {
  if (role === 'admin') return 'Quản trị viên'
  if (role === 'can_bo') return 'Cán bộ'
  if (role === 'sinh_vien') return 'Sinh viên'
  return 'Người dùng'
}

const currentTime = ref('')
let timer = null

const updateTime = () => {
  const now = new Date()
  currentTime.value = now.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
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
  position: relative;
  width: 100%;
  max-width: 100vw;
  overflow-x: hidden;
}

.app-layout.no-sidebar {
  display: block;
}

/* Sidebar Backdrop on Mobile */
.sidebar-backdrop {
  display: none;
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
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  z-index: 100;
}

.sidebar-header {
  padding: 1.25rem;
  border-bottom: 1px solid #334155;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.btn-close-drawer {
  display: none;
  background: none;
  border: none;
  color: #94a3b8;
  font-size: 1.75rem;
  cursor: pointer;
  line-height: 1;
  padding: 0.25rem 0.5rem;
}

.app-logo {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.logo-icon {
  font-size: 1.6rem;
  background: #334155;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--radius-md);
}

.app-title {
  font-size: 0.95rem;
  font-weight: 700;
  letter-spacing: 0.02em;
  color: #ffffff;
}

.app-subtitle {
  font-size: 0.7rem;
  color: #94a3b8;
}

.sidebar-nav {
  padding: 1rem 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  flex: 1;
  overflow-y: auto;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  padding: 0.7rem 0.9rem;
  color: #94a3b8;
  border-radius: var(--radius-sm);
  font-size: 0.88rem;
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

.sidebar-pwa-wrap {
  padding: 0 0.75rem;
  margin-top: auto;
  margin-bottom: 0.5rem;
}

.sidebar-footer {
  padding: 0.85rem 1.25rem;
  border-top: 1px solid #334155;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.avatar {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: var(--primary);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.85rem;
}

.user-meta {
  display: flex;
  flex-direction: column;
}

.user-name {
  font-size: 0.82rem;
  font-weight: 600;
  color: #f1f5f9;
}

.user-role {
  font-size: 0.7rem;
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
  width: 100%;
}

.top-header {
  height: 60px;
  background: #ffffff;
  border-bottom: 1px solid var(--gray-200);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 1.5rem;
  position: sticky;
  top: 0;
  z-index: 40;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.btn-hamburger {
  display: none;
  flex-direction: column;
  justify-content: space-around;
  width: 32px;
  height: 32px;
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 4px;
}

.hamburger-bar {
  width: 100%;
  height: 2.5px;
  background-color: var(--gray-700);
  border-radius: 2px;
}

.page-crumb {
  font-weight: 700;
  font-size: 1rem;
  color: var(--gray-800);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 200px;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.clock-display {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--gray-600);
  background: var(--gray-100);
  padding: 0.35rem 0.65rem;
  border-radius: var(--radius-sm);
  white-space: nowrap;
}

.btn-sm {
  padding: 0.4rem 0.75rem;
  font-size: 0.8rem;
}

.page-content {
  padding: 1.5rem;
  flex: 1;
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
}

.page-content.full-page {
  padding: 0;
}

/* Mobile Bottom Nav */
.mobile-bottom-nav {
  display: none;
}

/* =========================================================
   RESPONSIVE DESIGN (TABLET & MOBILE)
   ========================================================= */
@media (max-width: 768px) {
  /* Turn Sidebar into Off-Canvas Drawer */
  .sidebar-backdrop {
    display: block;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(2px);
    z-index: 998;
  }

  .sidebar {
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    width: 280px;
    z-index: 999;
    transform: translateX(-100%);
    box-shadow: var(--shadow-lg);
  }

  .sidebar.mobile-open {
    transform: translateX(0);
  }

  .btn-close-drawer {
    display: block;
  }

  /* Header adjustments */
  .top-header {
    padding: 0 0.85rem;
    height: 54px;
  }

  .btn-hamburger {
    display: flex;
  }

  .page-crumb {
    font-size: 0.92rem;
    max-width: 160px;
  }

  .btn-quick-scan .btn-text {
    display: none;
  }

  .clock-display {
    display: none;
  }

  /* Page Content mobile padding */
  .page-content {
    padding: 0.85rem 0.75rem 4.5rem; /* bottom padding for mobile bar */
  }

  .page-content.full-page {
    padding: 0;
  }

  /* Mobile Bottom Navigation Bar */
  .mobile-bottom-nav {
    display: flex;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: 60px;
    background: #ffffff;
    border-top: 1px solid var(--gray-200);
    z-index: 50;
    justify-content: space-around;
    align-items: center;
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.06);
    padding-bottom: env(safe-area-inset-bottom, 0);
  }

  .bottom-nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2px;
    color: var(--gray-500);
    font-size: 0.68rem;
    font-weight: 500;
    flex: 1;
    height: 100%;
    background: transparent;
    border: none;
    cursor: pointer;
    text-decoration: none;
    transition: color 0.15s ease;
  }

  .bottom-nav-item.active {
    color: var(--primary);
    font-weight: 700;
  }

  .bottom-icon {
    font-size: 1.25rem;
    line-height: 1;
  }

  .bottom-nav-item.highlight {
    position: relative;
  }

  .scan-circle {
    width: 44px;
    height: 44px;
    background: var(--primary);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    margin-top: -18px;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
    border: 3px solid #ffffff;
  }

  .btn-bottom-logout {
    color: var(--danger);
  }
}
</style>
