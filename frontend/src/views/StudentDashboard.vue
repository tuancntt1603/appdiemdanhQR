<template>
  <div class="student-dashboard-layout">
    <!-- Ambient Background Accents -->
    <div class="bg-accent-blob blob-1"></div>
    <div class="bg-accent-blob blob-2"></div>

    <!-- Top Navigation Bar for Student -->
    <header class="student-top-nav">
      <div class="nav-brand">
        <div class="brand-icon-box">
          <span class="brand-icon">🎓</span>
        </div>
        <div class="brand-text">
          <div class="brand-title-row">
            <span class="brand-title">CỔNG SINH VIÊN</span>
            <span class="version-badge">v2.0</span>
          </div>
          <span class="brand-subtitle">Hệ thống Điểm danh QR Xưởng Thực Hành</span>
        </div>
      </div>

      <!-- Navigation Tabs (Desktop) -->
      <nav class="nav-tabs-desktop">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          class="tab-btn"
          :class="{ active: activeTab === tab.id }"
          @click="activeTab = tab.id"
        >
          <span class="tab-icon">{{ tab.icon }}</span>
          <span>{{ tab.label }}</span>
          <span v-if="tab.id === 'qr'" class="tab-pulse-dot"></span>
        </button>
      </nav>

      <!-- User Chip & Logout -->
      <div class="nav-user-actions">
        <div class="user-chip" @click="activeTab = 'profile'" title="Xem thông tin cá nhân">
          <div class="avatar-gradient">{{ userInitial }}</div>
          <div class="chip-info">
            <span class="chip-name">{{ studentInfo.ho_ten || studentInfo.name || 'Sinh viên' }}</span>
            <span class="chip-mssv">{{ studentInfo.ma_sinh_vien || studentInfo.username || '' }}</span>
          </div>
        </div>
        <button @click="handleLogout" class="btn-logout-header" title="Đăng xuất khỏi hệ thống">
          <span class="logout-icon">🚪</span>
          <span class="logout-text">Đăng xuất</span>
        </button>
      </div>
    </header>

    <!-- Main Container -->
    <main class="student-main-content">
      <!-- Loading State Skeleton -->
      <div v-if="loadingInitial" class="loading-state-card card">
        <div class="spinner-modern"></div>
        <p class="loading-text">Đang đồng bộ dữ liệu điểm danh và thông tin cá nhân...</p>
      </div>

      <template v-else>
        <!-- TAB 1: TRANG CHỦ / TỔNG QUAN -->
        <div v-show="activeTab === 'home'" class="tab-content animate-fade">
          <!-- Hero Welcome Card -->
          <div class="welcome-hero-card">
            <div class="hero-glow-circle"></div>
            <div class="welcome-left">
              <div class="greeting-badge">
                <span class="pulse-indicator"></span>
                <span>{{ greetingTimeText }}</span>
              </div>
              <h1 class="welcome-heading">
                Xin chào: <span>{{ studentInfo.ho_ten || studentInfo.name }}</span>
              </h1>
              <div class="student-meta-tags">
                <div class="tag-card">
                  <span class="tag-label">MÃ SINH VIÊN</span>
                  <span class="tag-val">🆔 {{ studentInfo.ma_sinh_vien || studentInfo.username }}</span>
                </div>
                <div class="tag-card">
                  <span class="tag-label">LỚP HỌC</span>
                  <span class="tag-val">🏫 {{ studentInfo.lop || 'CNTT16-03' }}</span>
                </div>
                <div class="tag-card">
                  <span class="tag-label">EMAIL TÀI KHOẢN</span>
                  <span class="tag-val">📧 {{ studentInfo.email }}</span>
                </div>
              </div>
            </div>
            <div class="welcome-right">
              <button @click="activeTab = 'qr'" class="btn-hero-qr">
                <div class="btn-hero-icon-box">
                  <span class="hero-qr-icon">📱</span>
                  <span class="scan-ring-pulse"></span>
                </div>
                <div class="hero-qr-text">
                  <b>MỞ MÃ QR CÁ NHÂN</b>
                  <span>Mã token bảo mật 90 giây</span>
                </div>
              </button>
            </div>
          </div>

          <!-- Quick Stats Grid -->
          <div class="stats-overview-grid">
            <!-- Card 1: Trạng thái hôm nay -->
            <div class="stat-card" :class="todayStatusBorderClass">
              <div class="stat-icon-wrapper" :class="todayStatusBgClass">
                {{ todayStatusIcon }}
              </div>
              <div class="stat-meta">
                <span class="stat-title">Trạng thái điểm danh hôm nay</span>
                <div class="stat-value" :class="todayStatusTextClass">{{ todayStatusText }}</div>
                <p class="stat-desc">{{ todayStatusDesc }}</p>
              </div>
            </div>

            <!-- Card 2: Giờ vào hôm nay -->
            <div class="stat-card border-blue-glow">
              <div class="stat-icon-wrapper bg-soft-blue">📥</div>
              <div class="stat-meta">
                <span class="stat-title">Giờ vào xưởng (Check-In)</span>
                <div class="stat-value text-primary font-mono">{{ todayCheckInText }}</div>
                <p class="stat-desc">
                  {{ todayAttendance ? ('Địa điểm: ' + (todayAttendance.workshop?.ten_xuong || 'Xưởng thực hành')) : 'Chưa ghi nhận giờ vào' }}
                </p>
              </div>
            </div>

            <!-- Card 3: Giờ ra hôm nay -->
            <div class="stat-card border-green-glow">
              <div class="stat-icon-wrapper bg-soft-green">📤</div>
              <div class="stat-meta">
                <span class="stat-title">Giờ ra xưởng (Check-Out)</span>
                <div class="stat-value text-success font-mono">{{ todayCheckOutText }}</div>
                <p class="stat-desc">
                  {{ todayAttendance?.check_out ? 'Đã hoàn thành lượt ra' : 'Quét QR lần 2 khi ra về' }}
                </p>
              </div>
            </div>

            <!-- Card 4: Tổng số buổi đã tham gia -->
            <div class="stat-card border-purple-glow">
              <div class="stat-icon-wrapper bg-soft-purple">📊</div>
              <div class="stat-meta">
                <span class="stat-title">Tổng lượt điểm danh</span>
                <div class="stat-value text-indigo font-mono">
                  {{ attendanceList.length }} <small class="unit">lượt</small>
                </div>
                <p class="stat-desc">{{ completedCount }} lượt hoàn thành đầy đủ</p>
              </div>
            </div>
          </div>

          <!-- Home Two Columns (QR Preview + Timeline & Recent History) -->
          <div class="home-two-columns">
            <!-- Left: QR Code Widget -->
            <div class="card qr-widget-card card-hoverable">
              <div class="card-header-clean">
                <div class="card-title-group">
                  <h3>📱 Mã QR Điểm danh cá nhân</h3>
                  <span class="badge-status-pill badge-green">Tự động gia hạn 90s</span>
                </div>
              </div>
              <p class="widget-subtitle">Xuất trình mã này trước webcam tại cửa xưởng để điểm danh VÀO hoặc RA.</p>

              <!-- QR Code Canvas with Scanning Frame -->
              <div class="qr-canvas-outer" :class="{ 'expired-dim': remainingSeconds <= 0 }">
                <div class="corner-bracket top-left"></div>
                <div class="corner-bracket top-right"></div>
                <div class="corner-bracket bottom-left"></div>
                <div class="corner-bracket bottom-right"></div>

                <div class="qr-inner-frame">
                  <qrcode-vue
                    v-if="qrDataString"
                    :value="qrDataString"
                    :size="210"
                    level="M"
                    render-as="svg"
                  />
                  <div v-else class="qr-placeholder">
                    <div class="spinner-modern"></div>
                  </div>
                </div>

                <div v-if="remainingSeconds <= 0" class="expired-overlay">
                  <div class="expired-badge-box">
                    <span>⚠️ MÃ ĐÃ HẾT HẠN</span>
                    <small>Bấm nút bên dưới để tạo mã mới</small>
                  </div>
                </div>
              </div>

              <!-- Countdown Timer & Progress Bar -->
              <div class="countdown-widget-wrap">
                <div class="countdown-row">
                  <span class="cd-label">Thời hạn mã:</span>
                  <span class="cd-timer" :class="{ danger: remainingSeconds <= 15 }">
                    <template v-if="remainingSeconds > 0">
                      ⏳ <b>{{ remainingSeconds }} giây</b>
                    </template>
                    <template v-else>
                      ❌ Hết hạn
                    </template>
                  </span>
                </div>
                <div class="progress-bar-bg">
                  <div
                    class="progress-bar-fill"
                    :style="{ width: (remainingSeconds / 90) * 100 + '%' }"
                    :class="{ 'bar-danger': remainingSeconds <= 20, 'bar-warn': remainingSeconds <= 40 && remainingSeconds > 20 }"
                  ></div>
                </div>
              </div>

              <button
                @click="generateMyQr"
                class="btn btn-primary btn-renew-qr"
                :disabled="loadingQr"
              >
                <span v-if="loadingQr" class="spinner-mini"></span>
                <span v-if="loadingQr">Đang tạo mã mới...</span>
                <span v-else>🔄 Tạo mã QR mới (Gia hạn 90s)</span>
              </button>
            </div>

            <!-- Right: Today details & Recent History -->
            <div class="card history-widget-card card-hoverable">
              <div class="card-header-clean">
                <div class="card-title-group">
                  <h3>🕒 Lịch sử điểm danh gần đây</h3>
                </div>
                <button @click="activeTab = 'history'" class="btn-text-link">
                  <span>Xem tất cả</span>
                  <span>&rarr;</span>
                </button>
              </div>

              <div v-if="attendanceList.length === 0" class="empty-list-box">
                <div class="empty-icon-circle">📂</div>
                <h4>Chưa có lượt điểm danh nào</h4>
                <p>Mã QR của bạn đã sẵn sàng. Hãy quét mã khi tới xưởng thực hành.</p>
              </div>

              <div v-else class="recent-att-list">
                <div
                  v-for="item in recentAttendances"
                  :key="item.id"
                  class="att-record-item"
                >
                  <div class="att-record-left">
                    <div class="att-record-icon" :class="item.status === 'muon' ? 'bg-warn' : 'bg-ok'">
                      {{ item.check_out ? '✅' : '📥' }}
                    </div>
                    <div class="att-record-meta">
                      <b class="session-name">
                        {{ item.practice_session?.ten_buoi || item.workshop?.ten_xuong || 'Buổi thực hành xưởng' }}
                      </b>
                      <div class="time-meta-row">
                        <span>📥 Vào: {{ formatDateTime(item.check_in) }}</span>
                        <template v-if="item.check_out">
                          <span>• 📤 Ra: {{ formatDateTime(item.check_out) }}</span>
                        </template>
                      </div>
                    </div>
                  </div>
                  <div class="att-record-right">
                    <span :class="['status-badge', getStatusBadgeClass(item.status, item.check_out)]">
                      {{ getStatusText(item.status, item.check_out) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 2: QR CÁ NHÂN FULL VIEW -->
        <div v-show="activeTab === 'qr'" class="tab-content animate-fade">
          <div class="card qr-fullscreen-card">
            <div class="qr-full-header">
              <span class="badge-role">MÃ QR ĐIỂM DANH SINH VIÊN</span>
              <h2>Mã QR Cá Nhân Bảo Mật 90 Giây</h2>
              <p>Mã chỉ có hiệu lực duy nhất cho tài khoản của bạn. Token tự động xoay vòng để chống chụp ảnh điểm danh hộ.</p>
            </div>

            <!-- Student Profile Badge -->
            <div class="qr-student-info-box">
              <div class="qr-student-name">{{ studentInfo.ho_ten || studentInfo.name }}</div>
              <div class="qr-student-meta">
                <span class="meta-item">Mã SV: <b>{{ studentInfo.ma_sinh_vien || studentInfo.username }}</b></span>
                <span class="meta-dot">•</span>
                <span class="meta-item">Lớp: <b>{{ studentInfo.lop }}</b></span>
                <span class="meta-dot">•</span>
                <span class="meta-item">Email: <b>{{ studentInfo.email }}</b></span>
              </div>
            </div>

            <!-- QR Canvas Wrapper -->
            <div class="qr-frame-outer" :class="{ 'expired-blur': remainingSeconds <= 0 }">
              <div class="corner-bracket top-left"></div>
              <div class="corner-bracket top-right"></div>
              <div class="corner-bracket bottom-left"></div>
              <div class="corner-bracket bottom-right"></div>

              <div class="qr-canvas-box">
                <qrcode-vue
                  v-if="qrDataString"
                  :value="qrDataString"
                  :size="260"
                  level="M"
                  render-as="svg"
                />
                <div v-if="remainingSeconds <= 0" class="qr-expired-overlay">
                  <div class="expired-alert-badge">⚠️ MÃ ĐÃ HẾT HẠN</div>
                  <p>Bấm nút bên dưới để tạo mã mới có hiệu lực 90 giây</p>
                </div>
              </div>
            </div>

            <!-- Timer & Progress -->
            <div class="qr-timer-container">
              <div class="qr-timer-pill" :class="{ expired: remainingSeconds <= 0, warning: remainingSeconds <= 20 }">
                <span v-if="remainingSeconds > 0">
                  ⏳ Mã còn hiệu lực: <b>{{ remainingSeconds }} giây</b>
                </span>
                <span v-else>
                  ❌ Mã đã hết hạn lúc: {{ expiredTimeStr }}
                </span>
              </div>

              <div class="qr-progress-track">
                <div
                  class="qr-progress-bar"
                  :style="{ width: (remainingSeconds / 90) * 100 + '%' }"
                  :class="{ danger: remainingSeconds <= 20, warn: remainingSeconds <= 40 && remainingSeconds > 20 }"
                ></div>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="qr-action-buttons">
              <button
                @click="generateMyQr"
                class="btn btn-primary btn-lg btn-renew"
                :disabled="loadingQr"
              >
                <span v-if="loadingQr" class="spinner-mini"></span>
                <span v-if="loadingQr">Đang tạo mã mới...</span>
                <span v-else>🔄 Tạo mã QR mới (Gia hạn 90s)</span>
              </button>
            </div>

            <!-- Instructions -->
            <div class="qr-guide-box">
              <h4>📋 Quy trình điểm danh tại xưởng:</h4>
              <div class="guide-steps">
                <div class="guide-step-item">
                  <div class="step-num">1</div>
                  <div class="step-content">
                    <b>Xuất trình mã</b>
                    <span>Mở màn hình này và đưa trước webcam tại cửa xưởng.</span>
                  </div>
                </div>
                <div class="guide-step-item">
                  <div class="step-num">2</div>
                  <div class="step-content">
                    <b>Ghi nhận Giờ Vào</b>
                    <span>Hệ thống phát tiếng bíp và hiển thị Giờ Vào thành công.</span>
                  </div>
                </div>
                <div class="guide-step-item">
                  <div class="step-num">3</div>
                  <div class="step-content">
                    <b>Ghi nhận Giờ Ra</b>
                    <span>Khi hết ca thực hành, quét lại mã để ghi nhận Giờ Ra.</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 3: LỊCH SỬ ĐIỂM DANH -->
        <div v-show="activeTab === 'history'" class="tab-content animate-fade">
          <div class="card history-full-card">
            <div class="history-header-row">
              <div>
                <h2>🕒 Lịch sử điểm danh của tôi</h2>
                <p>Toàn bộ danh sách lượt vào xưởng và ra xưởng cá nhân</p>
              </div>
              <button @click="fetchAttendance" class="btn btn-secondary btn-sm" :disabled="loadingAttendance">
                <span v-if="loadingAttendance">🔄 Đang tải...</span>
                <span v-else>🔄 Làm mới</span>
              </button>
            </div>

            <!-- Search & Filter bar -->
            <div class="history-filter-bar">
              <div class="search-input-wrap">
                <span class="search-icon">🔍</span>
                <input
                  v-model="historySearch"
                  type="text"
                  class="form-control"
                  placeholder="Tìm kiếm theo tên xưởng, buổi học, ngày..."
                />
              </div>
            </div>

            <!-- Table Container -->
            <div class="table-container">
              <table class="custom-table">
                <thead>
                  <tr>
                    <th>STT</th>
                    <th>Ngày học</th>
                    <th>Xưởng / Buổi thực hành</th>
                    <th>Giờ Vào (Check-In)</th>
                    <th>Giờ Ra (Check-Out)</th>
                    <th>Thời lượng</th>
                    <th>Trạng thái</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="filteredAttendance.length === 0">
                    <td colspan="7" class="text-center py-5 text-muted">
                      Không tìm thấy dữ liệu điểm danh nào phù hợp.
                    </td>
                  </tr>
                  <tr v-for="(item, index) in filteredAttendance" :key="item.id">
                    <td><b>{{ index + 1 }}</b></td>
                    <td><b>{{ formatDateOnly(item.check_in) }}</b></td>
                    <td>
                      <div class="session-info-cell">
                        <b>{{ item.practice_session?.ten_buoi || item.workshop?.ten_xuong || 'Xưởng thực hành' }}</b>
                        <small v-if="item.workshop?.dia_diem" class="text-muted">📍 {{ item.workshop.dia_diem }}</small>
                      </div>
                    </td>
                    <td>
                      <span class="time-badge in">📥 {{ formatTimeOnly(item.check_in) }}</span>
                    </td>
                    <td>
                      <span v-if="item.check_out" class="time-badge out">
                        📤 {{ formatTimeOnly(item.check_out) }}
                      </span>
                      <span v-else class="time-badge empty">
                        --:-- (Chưa check-out)
                      </span>
                    </td>
                    <td>
                      <span class="duration-pill">{{ calculateDuration(item.check_in, item.check_out) }}</span>
                    </td>
                    <td>
                      <span :class="['status-badge', getStatusBadgeClass(item.status, item.check_out)]">
                        {{ getStatusText(item.status, item.check_out) }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- TAB 4: THÔNG TIN CÁ NHÂN -->
        <div v-show="activeTab === 'profile'" class="tab-content animate-fade">
          <div class="card profile-card">
            <!-- Student ID Card Visual -->
            <div class="student-id-card-visual">
              <div class="id-card-top">
                <span class="id-uni-name">TRƯỜNG ĐẠI HỌC • KHOA CNTT</span>
                <span class="id-badge-chip">THẺ SINH VIÊN SỐ</span>
              </div>
              <div class="id-card-body">
                <div class="id-avatar-circle">{{ userInitial }}</div>
                <div class="id-info-block">
                  <h3 class="id-student-name">{{ studentInfo.ho_ten || studentInfo.name }}</h3>
                  <p class="id-student-mssv">MSSV: <b>{{ studentInfo.ma_sinh_vien || studentInfo.username }}</b></p>
                  <p class="id-student-class">Lớp: <b>{{ studentInfo.lop || 'CNTT16-03' }}</b></p>
                </div>
              </div>
              <div class="id-card-bottom">
                <span>Hệ thống Điểm danh QR 90s</span>
                <span class="id-status-dot">● Đang hoạt động</span>
              </div>
            </div>

            <!-- Detailed Grid -->
            <div class="profile-details-grid">
              <div class="profile-field">
                <span class="field-label">Mã sinh viên (Username)</span>
                <span class="field-value font-mono">{{ studentInfo.ma_sinh_vien || studentInfo.username }}</span>
              </div>
              <div class="profile-field">
                <span class="field-label">Họ và tên</span>
                <span class="field-value font-semibold">{{ studentInfo.ho_ten || studentInfo.name }}</span>
              </div>
              <div class="profile-field">
                <span class="field-label">Lớp sinh hoạt</span>
                <span class="field-value">{{ studentInfo.lop || 'CNTT16-03' }}</span>
              </div>
              <div class="profile-field">
                <span class="field-label">Email tài khoản</span>
                <span class="field-value">{{ studentInfo.email }}</span>
              </div>
              <div class="profile-field">
                <span class="field-label">Vai trò trong hệ thống</span>
                <span class="field-value">
                  <span class="badge-role-tag">Sinh viên (Student)</span>
                </span>
              </div>
              <div class="profile-field">
                <span class="field-label">Bảo mật mật khẩu</span>
                <span class="field-value font-mono">•••••••• (Đã mã hóa Bcrypt)</span>
              </div>
            </div>

            <div class="profile-actions-row">
              <button @click="handleLogout" class="btn btn-danger">
                🚪 Đăng xuất khỏi tài khoản
              </button>
            </div>
          </div>
        </div>
      </template>
    </main>

    <!-- Mobile Bottom Navigation Bar -->
    <nav class="student-bottom-nav">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        class="bottom-nav-btn"
        :class="{ active: activeTab === tab.id }"
        @click="activeTab = tab.id"
      >
        <span class="bottom-icon">{{ tab.icon }}</span>
        <span class="bottom-text">{{ tab.label }}</span>
      </button>
      <button @click="handleLogout" class="bottom-nav-btn btn-logout-mobile">
        <span class="bottom-icon">🚪</span>
        <span class="bottom-text">Đăng xuất</span>
      </button>
    </nav>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import QrcodeVue from 'qrcode.vue'
import api from '../services/api'

const router = useRouter()

// Tabs
const activeTab = ref('home')
const tabs = [
  { id: 'home', label: 'Trang chủ', icon: '🏠' },
  { id: 'qr', label: 'QR cá nhân', icon: '📱' },
  { id: 'history', label: 'Lịch sử điểm danh', icon: '🕒' },
  { id: 'profile', label: 'Thông tin cá nhân', icon: '👤' },
]

// State
const loadingInitial = ref(true)
const loadingQr = ref(false)
const loadingAttendance = ref(false)
const studentInfo = ref({})
const todayAttendance = ref(null)
const attendanceList = ref([])
const historySearch = ref('')

// QR code & Countdown
const qrDataString = ref('')
const remainingSeconds = ref(90)
const expiredTimeStr = ref('')
let countdownInterval = null

// Initial letter
const userInitial = computed(() => {
  const name = studentInfo.value.ho_ten || studentInfo.value.name || 'S'
  const parts = name.trim().split(' ')
  return parts[parts.length - 1].charAt(0).toUpperCase()
})

// Dynamic Greeting based on time of day
const greetingTimeText = computed(() => {
  const hour = new Date().getHours()
  if (hour < 12) return 'Chào buổi sáng'
  if (hour < 18) return 'Chào buổi chiều'
  return 'Chào buổi tối'
})

// Today Status
const todayStatusBorderClass = computed(() => {
  if (!todayAttendance.value) return 'border-gray-glow'
  if (todayAttendance.value.check_out) return 'border-green-glow'
  if (todayAttendance.value.check_in) return 'border-blue-glow'
  return 'border-gray-glow'
})

const todayStatusBgClass = computed(() => {
  if (!todayAttendance.value) return 'bg-soft-gray'
  if (todayAttendance.value.check_out) return 'bg-soft-green'
  if (todayAttendance.value.check_in) return 'bg-soft-blue'
  return 'bg-soft-gray'
})

const todayStatusTextClass = computed(() => {
  if (!todayAttendance.value) return 'text-muted'
  if (todayAttendance.value.check_out) return 'text-success'
  if (todayAttendance.value.check_in) return 'text-primary'
  return 'text-muted'
})

const todayStatusIcon = computed(() => {
  if (!todayAttendance.value) return '⏳'
  if (todayAttendance.value.check_out) return '🎉'
  if (todayAttendance.value.check_in) return '🚪'
  return '⏳'
})

const todayStatusText = computed(() => {
  if (!todayAttendance.value) return 'Chưa điểm danh hôm nay'
  if (todayAttendance.value.check_out) return 'Đã hoàn thành lượt xưởng'
  if (todayAttendance.value.check_in) {
    return todayAttendance.value.status === 'muon' ? 'Đã vào (Đi muộn)' : 'Đang trong xưởng (Đã vào)'
  }
  return 'Chưa điểm danh'
})

const todayStatusDesc = computed(() => {
  if (!todayAttendance.value) return 'Vui lòng mở mã QR để điểm danh khi đến xưởng'
  if (todayAttendance.value.check_out) return 'Bạn đã quét đủ giờ vào và giờ ra hôm nay.'
  return 'Đừng quên quét QR lần nữa khi rời khỏi xưởng.'
})

const todayCheckInText = computed(() => {
  if (!todayAttendance.value?.check_in) return '--:--'
  const d = new Date(todayAttendance.value.check_in)
  return d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
})

const todayCheckOutText = computed(() => {
  if (!todayAttendance.value?.check_out) return '--:--'
  const d = new Date(todayAttendance.value.check_out)
  return d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
})

const completedCount = computed(() => {
  return attendanceList.value.filter(a => a.check_out).length
})

const recentAttendances = computed(() => {
  return attendanceList.value.slice(0, 5)
})

const filteredAttendance = computed(() => {
  if (!historySearch.value) return attendanceList.value
  const q = historySearch.value.toLowerCase()
  return attendanceList.value.filter(item => {
    const session = item.practice_session?.ten_buoi || ''
    const workshop = item.workshop?.ten_xuong || ''
    const dateStr = formatDateOnly(item.check_in)
    return session.toLowerCase().includes(q) ||
           workshop.toLowerCase().includes(q) ||
           dateStr.toLowerCase().includes(q)
  })
})

// API Calls
const fetchProfile = async () => {
  try {
    const res = await api.get('/me')
    if (res.data.success) {
      studentInfo.value = res.data.user || res.data.data
      if (studentInfo.value.ho_ten) {
        localStorage.setItem('user_name', studentInfo.value.ho_ten)
      }
    }
  } catch (err) {
    console.error('Lỗi tải thông tin sinh viên:', err)
  }
}

const fetchAttendance = async () => {
  loadingAttendance.value = true
  try {
    const res = await api.get('/my-attendance')
    if (res.data.success) {
      attendanceList.value = res.data.data || []
      todayAttendance.value = res.data.today || null
    }
  } catch (err) {
    console.error('Lỗi tải lịch sử điểm danh:', err)
  } finally {
    loadingAttendance.value = false
  }
}

const generateMyQr = async () => {
  if (countdownInterval) clearInterval(countdownInterval)
  loadingQr.value = true
  try {
    const res = await api.get('/my-qr')
    if (res.data.success) {
      qrDataString.value = res.data.data.qr_data
      remainingSeconds.value = res.data.data.expires_in_seconds || 90

      countdownInterval = setInterval(() => {
        if (remainingSeconds.value > 0) {
          remainingSeconds.value--
        } else {
          clearInterval(countdownInterval)
          const now = new Date()
          expiredTimeStr.value = now.toLocaleTimeString('vi-VN')
        }
      }, 1000)
    }
  } catch (err) {
    console.error('Lỗi tạo mã QR cá nhân:', err)
  } finally {
    loadingQr.value = false
  }
}

const handleLogout = async () => {
  try {
    await api.post('/logout')
  } catch (err) {
    console.error('Lỗi khi đăng xuất:', err)
  } finally {
    localStorage.removeItem('auth_token')
    localStorage.removeItem('user_role')
    localStorage.removeItem('user_name')
    localStorage.removeItem('username')
    localStorage.removeItem('user_id')
    localStorage.removeItem('student_id')
    localStorage.removeItem('user_info')
    router.push('/login')
  }
}

// Helpers
const formatDateTime = (dateStr) => {
  if (!dateStr) return '--'
  const d = new Date(dateStr)
  return d.toLocaleString('vi-VN', {
    hour: '2-digit',
    minute: '2-digit',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

const formatDateOnly = (dateStr) => {
  if (!dateStr) return '--'
  const d = new Date(dateStr)
  return d.toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

const formatTimeOnly = (dateStr) => {
  if (!dateStr) return '--:--'
  const d = new Date(dateStr)
  return d.toLocaleTimeString('vi-VN', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

const calculateDuration = (inStr, outStr) => {
  if (!inStr || !outStr) return 'Đang diễn ra'
  const diffMs = new Date(outStr) - new Date(inStr)
  if (diffMs <= 0) return '0 phút'
  const mins = Math.floor(diffMs / 60000)
  const hours = Math.floor(mins / 60)
  const remMins = mins % 60
  if (hours > 0) {
    return `${hours}h ${remMins}m`
  }
  return `${mins} phút`
}

const getStatusBadgeClass = (status, checkOut) => {
  if (checkOut) return 'badge-success'
  if (status === 'muon') return 'badge-warning'
  return 'badge-primary'
}

const getStatusText = (status, checkOut) => {
  if (checkOut) return 'Hoàn thành'
  if (status === 'muon') return 'Đi muộn'
  return 'Đúng giờ'
}

onMounted(async () => {
  loadingInitial.value = true
  await Promise.all([fetchProfile(), fetchAttendance()])
  await generateMyQr()
  loadingInitial.value = false
})

onUnmounted(() => {
  if (countdownInterval) clearInterval(countdownInterval)
})
</script>

<style scoped>
.student-dashboard-layout {
  min-height: 100vh;
  background-color: #f8fafc;
  color: #0f172a;
  display: flex;
  flex-direction: column;
  position: relative;
  overflow-x: hidden;
}

/* Ambient Blobs */
.bg-accent-blob {
  position: absolute;
  border-radius: 50%;
  filter: blur(120px);
  pointer-events: none;
  z-index: 0;
  opacity: 0.45;
}

.blob-1 {
  width: 500px;
  height: 500px;
  background: rgba(37, 99, 235, 0.12);
  top: -150px;
  left: -150px;
}

.blob-2 {
  width: 450px;
  height: 450px;
  background: rgba(99, 102, 241, 0.1);
  top: 30%;
  right: -100px;
}

/* Top Navigation Bar */
.student-top-nav {
  height: 68px;
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border-bottom: 1px solid rgba(226, 232, 240, 0.8);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 1.75rem;
  position: sticky;
  top: 0;
  z-index: 50;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
}

.nav-brand {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.brand-icon-box {
  width: 42px;
  height: 42px;
  background: linear-gradient(135deg, #eff6ff, #dbeafe);
  border: 1px solid #bfdbfe;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 10px rgba(37, 99, 235, 0.12);
}

.brand-icon {
  font-size: 1.5rem;
}

.brand-title-row {
  display: flex;
  align-items: center;
  gap: 0.45rem;
}

.brand-title {
  font-weight: 800;
  font-size: 1.05rem;
  color: #0f172a;
  letter-spacing: -0.01em;
}

.version-badge {
  font-size: 0.65rem;
  font-weight: 700;
  background: #eff6ff;
  color: #2563eb;
  padding: 0.1rem 0.45rem;
  border-radius: 999px;
  border: 1px solid #bfdbfe;
}

.brand-subtitle {
  font-size: 0.74rem;
  color: #64748b;
  display: block;
}

.nav-tabs-desktop {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  background: #f1f5f9;
  padding: 0.3rem;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.tab-btn {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.5rem 1rem;
  background: transparent;
  border: none;
  border-radius: 8px;
  font-size: 0.86rem;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
}

.tab-btn:hover {
  color: #0f172a;
}

.tab-btn.active {
  background: #ffffff;
  color: #2563eb;
  font-weight: 700;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.tab-pulse-dot {
  width: 6px;
  height: 6px;
  background-color: #10b981;
  border-radius: 50%;
  box-shadow: 0 0 6px #10b981;
}

.nav-user-actions {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.user-chip {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  background: white;
  border: 1px solid #e2e8f0;
  padding: 0.35rem 0.85rem 0.35rem 0.4rem;
  border-radius: 999px;
  cursor: pointer;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
  transition: all 0.2s;
}

.user-chip:hover {
  border-color: #cbd5e1;
  transform: translateY(-1px);
  box-shadow: 0 3px 8px rgba(0, 0, 0, 0.08);
}

.avatar-gradient {
  width: 32px;
  height: 32px;
  background: linear-gradient(135deg, #2563eb, #7c3aed);
  color: white;
  border-radius: 50%;
  font-weight: 800;
  font-size: 0.85rem;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 6px rgba(37, 99, 235, 0.3);
}

.chip-info {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
}

.chip-name {
  font-size: 0.82rem;
  font-weight: 700;
  color: #1e293b;
}

.chip-mssv {
  font-size: 0.7rem;
  color: #64748b;
  font-family: monospace;
}

.btn-logout-header {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.45rem 0.85rem;
  background: #fff1f2;
  color: #e11d48;
  border: 1px solid #fecdd3;
  border-radius: 8px;
  font-size: 0.82rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-logout-header:hover {
  background: #ffe4e6;
  border-color: #fda4af;
  transform: translateY(-1px);
}

/* Main Content */
.student-main-content {
  position: relative;
  z-index: 10;
  flex: 1;
  max-width: 1240px;
  width: 100%;
  margin: 0 auto;
  padding: 1.75rem 1.25rem 4.5rem;
}

.animate-fade {
  animation: fadeIn 0.25s ease-in-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(6px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Welcome Hero Card */
.welcome-hero-card {
  position: relative;
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #1e1b4b 100%);
  color: white;
  border-radius: 20px;
  padding: 2.25rem 2.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 15px 35px -5px rgba(15, 23, 42, 0.3);
  margin-bottom: 1.5rem;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.hero-glow-circle {
  position: absolute;
  width: 320px;
  height: 320px;
  background: radial-gradient(circle, rgba(59, 130, 246, 0.3) 0%, transparent 70%);
  top: -100px;
  right: -50px;
  pointer-events: none;
}

.greeting-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(8px);
  color: #93c5fd;
  font-size: 0.78rem;
  font-weight: 700;
  padding: 0.3rem 0.85rem;
  border-radius: 999px;
  margin-bottom: 0.75rem;
  border: 1px solid rgba(255, 255, 255, 0.15);
}

.pulse-indicator {
  width: 6px;
  height: 6px;
  background: #38bdf8;
  border-radius: 50%;
  box-shadow: 0 0 6px #38bdf8;
}

.welcome-heading {
  font-size: 1.85rem;
  font-weight: 800;
  color: #ffffff;
  margin-bottom: 1rem;
  letter-spacing: -0.02em;
}

.welcome-heading span {
  background: linear-gradient(135deg, #60a5fa, #93c5fd);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.student-meta-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.65rem;
}

.tag-card {
  background: rgba(255, 255, 255, 0.07);
  backdrop-filter: blur(6px);
  border: 1px solid rgba(255, 255, 255, 0.12);
  padding: 0.4rem 0.85rem;
  border-radius: 10px;
  display: flex;
  flex-direction: column;
}

.tag-label {
  font-size: 0.64rem;
  font-weight: 700;
  color: #94a3b8;
  letter-spacing: 0.05em;
}

.tag-val {
  font-size: 0.85rem;
  font-weight: 600;
  color: #ffffff;
}

.btn-hero-qr {
  display: flex;
  align-items: center;
  gap: 1rem;
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.2);
  padding: 1.1rem 1.6rem;
  border-radius: 16px;
  cursor: pointer;
  box-shadow: 0 10px 25px -4px rgba(37, 99, 235, 0.5);
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  text-align: left;
}

.btn-hero-qr:hover {
  transform: translateY(-3px);
  box-shadow: 0 16px 32px -4px rgba(37, 99, 235, 0.65);
}

.btn-hero-icon-box {
  position: relative;
  width: 44px;
  height: 44px;
  background: rgba(255, 255, 255, 0.15);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.hero-qr-icon {
  font-size: 1.8rem;
}

.hero-qr-text b {
  display: block;
  font-size: 1rem;
  letter-spacing: 0.02em;
}

.hero-qr-text span {
  font-size: 0.75rem;
  color: #bfdbfe;
}

/* Stats Overview Grid */
.stats-overview-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.15rem;
  margin-bottom: 1.5rem;
}

.stat-card {
  background: white;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  padding: 1.35rem 1.45rem;
  box-shadow: var(--shadow-card);
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-card-hover);
}

.border-blue-glow { border-left: 4px solid #2563eb; }
.border-green-glow { border-left: 4px solid #10b981; }
.border-purple-glow { border-left: 4px solid #6366f1; }
.border-gray-glow { border-left: 4px solid #cbd5e1; }

.stat-icon-wrapper {
  width: 46px;
  height: 46px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  flex-shrink: 0;
}

.bg-soft-blue { background: #eff6ff; color: #2563eb; }
.bg-soft-green { background: #ecfdf5; color: #10b981; }
.bg-soft-purple { background: #eef2ff; color: #6366f1; }
.bg-soft-gray { background: #f1f5f9; color: #64748b; }

.stat-meta {
  flex: 1;
}

.stat-title {
  font-size: 0.78rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  display: block;
  margin-bottom: 0.2rem;
}

.stat-value {
  font-size: 1.45rem;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.25;
  margin-bottom: 0.25rem;
}

.font-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
}

.stat-desc {
  font-size: 0.75rem;
  color: #94a3b8;
  line-height: 1.35;
}

/* Home Two Columns */
.home-two-columns {
  display: grid;
  grid-template-columns: 440px 1fr;
  gap: 1.5rem;
}

.card-header-clean {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.card-title-group h3 {
  font-size: 1.15rem;
  font-weight: 800;
  color: #0f172a;
}

.badge-status-pill {
  display: inline-block;
  font-size: 0.72rem;
  font-weight: 700;
  padding: 0.2rem 0.65rem;
  border-radius: 999px;
}

.badge-green {
  background: #dcfce7;
  color: #15803d;
  border: 1px solid #bbf7d0;
}

.widget-subtitle {
  font-size: 0.82rem;
  color: #64748b;
  margin-bottom: 1.25rem;
}

/* QR Canvas Frame */
.qr-canvas-outer {
  position: relative;
  background: #ffffff;
  padding: 1.25rem;
  border-radius: 16px;
  border: 1.5px solid #e2e8f0;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
  width: fit-content;
  margin: 0 auto 1.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s;
}

.qr-canvas-outer.expired-dim {
  filter: blur(2px) grayscale(90%);
  opacity: 0.6;
}

/* High-tech Corner Brackets */
.corner-bracket {
  position: absolute;
  width: 14px;
  height: 14px;
  border-color: #2563eb;
  border-style: solid;
  pointer-events: none;
}
.top-left { top: 6px; left: 6px; border-width: 3px 0 0 3px; border-top-left-radius: 4px; }
.top-right { top: 6px; right: 6px; border-width: 3px 3px 0 0; border-top-right-radius: 4px; }
.bottom-left { bottom: 6px; left: 6px; border-width: 0 0 3px 3px; border-bottom-left-radius: 4px; }
.bottom-right { bottom: 6px; right: 6px; border-width: 0 3px 3px 0; border-bottom-right-radius: 4px; }

.qr-inner-frame {
  background: white;
  padding: 0.5rem;
}

.expired-overlay {
  position: absolute;
  inset: 0;
  background: rgba(254, 242, 242, 0.88);
  backdrop-filter: blur(2px);
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 16px;
}

.expired-badge-box {
  background: white;
  border: 1px solid #fecaca;
  padding: 0.85rem 1.25rem;
  border-radius: 12px;
  box-shadow: var(--shadow-md);
  text-align: center;
}

.expired-badge-box span {
  display: block;
  font-weight: 800;
  color: #dc2626;
  font-size: 0.95rem;
}

.expired-badge-box small {
  color: #64748b;
  font-size: 0.75rem;
}

/* Countdown */
.countdown-widget-wrap {
  margin-bottom: 1.25rem;
}

.countdown-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.4rem;
  font-size: 0.85rem;
}

.cd-label {
  color: #64748b;
  font-weight: 500;
}

.cd-timer {
  font-weight: 700;
  color: #2563eb;
}

.cd-timer.danger {
  color: #dc2626;
}

.progress-bar-bg {
  height: 8px;
  background: #e2e8f0;
  border-radius: 999px;
  overflow: hidden;
}

.progress-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #3b82f6, #2563eb);
  border-radius: 999px;
  transition: width 1s linear;
}

.progress-bar-fill.bar-warn {
  background: linear-gradient(90deg, #f59e0b, #d97706);
}

.progress-bar-fill.bar-danger {
  background: linear-gradient(90deg, #ef4444, #dc2626);
}

.btn-renew-qr {
  width: 100%;
  padding: 0.8rem;
  font-weight: 700;
}

/* History Card Widget */
.btn-text-link {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  background: none;
  border: none;
  color: #2563eb;
  font-weight: 700;
  font-size: 0.85rem;
  cursor: pointer;
}

.btn-text-link:hover {
  text-decoration: underline;
}

.empty-list-box {
  text-align: center;
  padding: 3rem 1.5rem;
}

.empty-icon-circle {
  width: 60px;
  height: 60px;
  background: #f1f5f9;
  border-radius: 50%;
  font-size: 1.8rem;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 0.85rem;
}

.empty-list-box h4 {
  font-size: 1rem;
  font-weight: 700;
  color: #0f172a;
}

.empty-list-box p {
  font-size: 0.82rem;
  color: #64748b;
  margin-top: 0.25rem;
}

.recent-att-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-top: 0.75rem;
}

.att-record-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.85rem 1rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  transition: all 0.2s;
}

.att-record-item:hover {
  background: #ffffff;
  border-color: #cbd5e1;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
  transform: translateX(2px);
}

.att-record-left {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.att-record-icon {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.15rem;
}

.att-record-icon.bg-ok { background: #dcfce7; }
.att-record-icon.bg-warn { background: #fef3c7; }

.att-record-meta {
  display: flex;
  flex-direction: column;
}

.session-name {
  font-size: 0.9rem;
  font-weight: 700;
  color: #0f172a;
}

.time-meta-row {
  font-size: 0.76rem;
  color: #64748b;
  margin-top: 0.15rem;
}

/* Status Badges */
.status-badge {
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.25rem 0.65rem;
  border-radius: 999px;
  display: inline-block;
  white-space: nowrap;
}

/* TAB 2: QR FULLSCREEN */
.qr-fullscreen-card {
  max-width: 620px;
  margin: 0 auto;
  text-align: center;
  padding: 2.5rem 2rem;
}

.badge-role {
  display: inline-block;
  background: #eff6ff;
  color: #2563eb;
  font-size: 0.75rem;
  font-weight: 800;
  padding: 0.3rem 0.85rem;
  border-radius: 999px;
  margin-bottom: 0.65rem;
  border: 1px solid #bfdbfe;
}

.qr-full-header h2 {
  font-size: 1.5rem;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.02em;
}

.qr-full-header p {
  font-size: 0.85rem;
  color: #64748b;
  margin-top: 0.35rem;
}

.qr-student-info-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 1rem 1.5rem;
  margin: 1.5rem 0;
}

.qr-student-name {
  font-size: 1.35rem;
  font-weight: 800;
  color: #2563eb;
}

.qr-student-meta {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  font-size: 0.82rem;
  color: #64748b;
  margin-top: 0.35rem;
  flex-wrap: wrap;
}

.meta-dot {
  color: #cbd5e1;
}

.qr-frame-outer {
  position: relative;
  display: inline-block;
  background: #ffffff;
  padding: 1.5rem;
  border-radius: 20px;
  border: 1.5px solid #e2e8f0;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06);
  margin-bottom: 1.5rem;
  transition: all 0.3s ease;
}

.qr-frame-outer.expired-blur {
  filter: blur(3px) grayscale(90%);
  opacity: 0.5;
}

.qr-expired-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: rgba(254, 242, 242, 0.92);
  border-radius: 20px;
  padding: 1rem;
}

.expired-alert-badge {
  background: #dc2626;
  color: white;
  font-weight: 800;
  font-size: 1.05rem;
  padding: 0.4rem 1rem;
  border-radius: 8px;
  margin-bottom: 0.5rem;
}

.qr-expired-overlay p {
  font-size: 0.82rem;
  color: #991b1b;
}

.qr-timer-container {
  max-width: 400px;
  margin: 0 auto 1.5rem;
}

.qr-timer-pill {
  display: inline-block;
  padding: 0.45rem 1.25rem;
  background: #eff6ff;
  color: #1e40af;
  border-radius: 999px;
  font-size: 0.95rem;
  font-weight: 700;
  margin-bottom: 0.65rem;
  border: 1px solid #bfdbfe;
}

.qr-timer-pill.warning {
  background: #fffbeb;
  color: #b45309;
  border-color: #fde68a;
}

.qr-timer-pill.expired {
  background: #fef2f2;
  color: #b91c1c;
  border-color: #fecaca;
}

.qr-progress-track {
  height: 8px;
  background: #e2e8f0;
  border-radius: 999px;
  overflow: hidden;
}

.qr-progress-bar {
  height: 100%;
  background: linear-gradient(90deg, #3b82f6, #2563eb);
  border-radius: 999px;
  transition: width 1s linear;
}

.qr-progress-bar.warn {
  background: linear-gradient(90deg, #f59e0b, #d97706);
}

.qr-progress-bar.danger {
  background: linear-gradient(90deg, #ef4444, #dc2626);
}

.btn-renew {
  padding: 0.95rem 2rem;
  font-size: 1rem;
  font-weight: 800;
}

.qr-guide-box {
  margin-top: 2rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 1.5rem;
  text-align: left;
}

.qr-guide-box h4 {
  font-size: 0.95rem;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 1rem;
}

.guide-steps {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.guide-step-item {
  display: flex;
  align-items: flex-start;
  gap: 0.85rem;
}

.step-num {
  width: 26px;
  height: 26px;
  background: #2563eb;
  color: white;
  border-radius: 50%;
  font-size: 0.78rem;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.step-content b {
  display: block;
  font-size: 0.86rem;
  color: #0f172a;
}

.step-content span {
  font-size: 0.78rem;
  color: #64748b;
}

/* TAB 3: HISTORY */
.history-full-card {
  padding: 2rem;
}

.history-header-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
}

.history-header-row h2 {
  font-size: 1.4rem;
  font-weight: 800;
  color: #0f172a;
}

.history-header-row p {
  font-size: 0.85rem;
  color: #64748b;
  margin-top: 0.2rem;
}

.history-filter-bar {
  margin-bottom: 1.25rem;
}

.search-input-wrap {
  position: relative;
  max-width: 450px;
}

.search-icon {
  position: absolute;
  left: 0.85rem;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  pointer-events: none;
}

.search-input-wrap input {
  padding-left: 2.4rem;
}

.time-badge {
  display: inline-block;
  padding: 0.3rem 0.6rem;
  border-radius: 6px;
  font-size: 0.82rem;
  font-weight: 700;
  font-family: monospace;
}

.time-badge.in {
  background: #eff6ff;
  color: #1e40af;
}

.time-badge.out {
  background: #ecfdf5;
  color: #065f46;
}

.time-badge.empty {
  background: #f1f5f9;
  color: #94a3b8;
}

.duration-pill {
  font-size: 0.82rem;
  color: #475569;
  font-weight: 600;
}

.session-info-cell {
  display: flex;
  flex-direction: column;
}

/* TAB 4: PROFILE */
.profile-card {
  max-width: 720px;
  margin: 0 auto;
  padding: 2.25rem;
}

/* Digital Student ID Card */
.student-id-card-visual {
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
  color: white;
  border-radius: 18px;
  padding: 1.5rem 1.75rem;
  box-shadow: 0 15px 30px -5px rgba(15, 23, 42, 0.4);
  margin-bottom: 2rem;
  border: 1px solid rgba(255, 255, 255, 0.15);
}

.id-card-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  padding-bottom: 0.85rem;
  margin-bottom: 1.25rem;
}

.id-uni-name {
  font-size: 0.74rem;
  font-weight: 800;
  color: #93c5fd;
  letter-spacing: 0.06em;
}

.id-badge-chip {
  font-size: 0.68rem;
  font-weight: 700;
  background: rgba(255, 255, 255, 0.15);
  padding: 0.2rem 0.55rem;
  border-radius: 999px;
}

.id-card-body {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  margin-bottom: 1.25rem;
}

.id-avatar-circle {
  width: 64px;
  height: 64px;
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  color: white;
  border-radius: 50%;
  font-size: 1.8rem;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
  border: 2px solid rgba(255, 255, 255, 0.3);
}

.id-student-name {
  font-size: 1.35rem;
  font-weight: 800;
  color: #ffffff;
}

.id-student-mssv, .id-student-class {
  font-size: 0.85rem;
  color: #cbd5e1;
  margin-top: 0.15rem;
}

.id-card-bottom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.75rem;
  color: #94a3b8;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  padding-top: 0.75rem;
}

.id-status-dot {
  color: #34d399;
  font-weight: 700;
}

.profile-details-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.25rem;
  margin-bottom: 2rem;
}

.profile-field {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.field-label {
  font-size: 0.78rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.field-value {
  font-size: 0.95rem;
  color: #0f172a;
  background: #f8fafc;
  padding: 0.65rem 0.95rem;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
}

.badge-role-tag {
  background: #eff6ff;
  color: #2563eb;
  font-weight: 700;
  padding: 0.2rem 0.55rem;
  border-radius: 6px;
}

.profile-actions-row {
  display: flex;
  justify-content: flex-end;
  padding-top: 1.25rem;
  border-top: 1px solid #e2e8f0;
}

/* Spinner Modern */
.spinner-modern {
  width: 44px;
  height: 44px;
  border: 3.5px solid #e2e8f0;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 1.25rem;
}

.spinner-mini {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.4);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  display: inline-block;
}

.loading-state-card {
  text-align: center;
  padding: 3.5rem 1.5rem;
}

.loading-text {
  font-size: 0.9rem;
  color: #64748b;
}

/* Mobile Bottom Nav */
.student-bottom-nav {
  display: none;
}

/* Responsive */
@media (max-width: 960px) {
  .home-two-columns {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .nav-tabs-desktop {
    display: none;
  }
  .btn-logout-header {
    display: none;
  }
  .welcome-hero-card {
    flex-direction: column;
    align-items: flex-start;
    padding: 1.6rem 1.35rem;
  }
  .welcome-heading {
    font-size: 1.4rem;
  }
  .btn-hero-qr {
    width: 100%;
    justify-content: center;
    margin-top: 1rem;
  }
  .profile-details-grid {
    grid-template-columns: 1fr;
  }
  .student-main-content {
    padding: 1rem 0.85rem 4.5rem;
  }
  .student-bottom-nav {
    display: flex;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: 62px;
    background: rgba(255, 255, 255, 0.96);
    backdrop-filter: blur(16px);
    border-top: 1px solid #e2e8f0;
    z-index: 50;
    justify-content: space-around;
    align-items: center;
    box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.05);
    padding-bottom: env(safe-area-inset-bottom, 0);
  }
  .bottom-nav-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 3px;
    color: #64748b;
    font-size: 0.68rem;
    font-weight: 600;
    flex: 1;
    height: 100%;
    background: transparent;
    border: none;
    cursor: pointer;
    text-decoration: none;
    transition: color 0.15s ease;
  }
  .bottom-nav-btn.active {
    color: #2563eb;
    font-weight: 800;
  }
  .bottom-icon {
    font-size: 1.3rem;
    line-height: 1;
  }
  .btn-logout-mobile {
    color: #e11d48;
  }
}
</style>
