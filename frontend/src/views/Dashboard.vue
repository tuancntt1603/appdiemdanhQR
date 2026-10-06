<template>
  <div class="dashboard-page animate-fade">
    <!-- Lecturer Hero Banner -->
    <div class="lecturer-hero-card">
      <div class="hero-glow-blob"></div>
      <div class="hero-left">
        <div class="hero-live-badge">
          <span class="live-dot-green"></span>
          <span>BẢNG ĐIỀU KHIỂN GIẢNG VIÊN • TRỰC TUYẾN</span>
        </div>
        <h1 class="hero-title">Xin chào, <span>{{ lecturerName }}</span></h1>
        <p class="hero-subtitle">Hệ thống điểm danh xưởng thực hành bằng mã QR tự động hóa thời gian thực</p>
      </div>

      <div class="hero-quick-actions">
        <router-link to="/scan" class="btn-hero-action btn-scan-glow">
          <span class="action-icon">📷</span>
          <span>Quét QR Điểm danh</span>
        </router-link>
        <router-link to="/students" class="btn-hero-action btn-ghost">
          <span class="action-icon">🎓</span>
          <span>Quản lý Sinh viên</span>
        </router-link>
        <router-link to="/reports" class="btn-hero-action btn-ghost">
          <span class="action-icon">📊</span>
          <span>Báo cáo Chuyên cần</span>
        </router-link>
      </div>
    </div>

    <!-- Stat Cards Grid -->
    <div class="stats-grid">
      <div class="stat-card stat-blue card-hoverable">
        <div class="stat-icon-wrapper bg-blue-glow">👥</div>
        <div class="stat-content">
          <span class="stat-label">Tổng số sinh viên</span>
          <span class="stat-value font-mono">{{ stats.total_students || 0 }}</span>
          <span class="stat-sub">Hồ sơ đã đăng ký</span>
        </div>
      </div>

      <div class="stat-card stat-green card-hoverable">
        <div class="stat-icon-wrapper bg-green-glow">✅</div>
        <div class="stat-content">
          <span class="stat-label">Đã điểm danh hôm nay</span>
          <span class="stat-value text-success font-mono">{{ stats.attended_today || 0 }}</span>
          <span class="stat-sub">Sinh viên có mặt tại xưởng</span>
        </div>
      </div>

      <div class="stat-card stat-amber card-hoverable">
        <div class="stat-icon-wrapper bg-amber-glow">⏳</div>
        <div class="stat-content">
          <span class="stat-label">Chưa điểm danh hôm nay</span>
          <span class="stat-value text-warning font-mono">{{ stats.unattended_today || 0 }}</span>
          <span class="stat-sub">Chưa quét mã vào</span>
        </div>
      </div>

      <div class="stat-card stat-purple card-hoverable">
        <div class="stat-icon-wrapper bg-purple-glow">🚪</div>
        <div class="stat-content">
          <span class="stat-label">Lượt Vào / Ra hôm nay</span>
          <span class="stat-value text-indigo font-mono">
            {{ stats.check_in_count || 0 }} <span class="slash">/</span> {{ stats.check_out_count || 0 }}
          </span>
          <span class="stat-sub">Đã vào / Đã ra về</span>
        </div>
      </div>
    </div>

    <!-- Charts Section -->
    <div class="charts-row">
      <!-- 7 Days Bar Chart -->
      <div class="card chart-card card-hoverable">
        <div class="card-header">
          <div>
            <h3 class="card-title">Điểm danh 7 ngày gần nhất</h3>
            <p class="card-subtitle">Số lượng sinh viên có mặt tại xưởng theo từng ngày</p>
          </div>
          <span class="badge badge-info">7 Ngày qua</span>
        </div>

        <div class="bar-chart-container">
          <div v-for="(day, idx) in stats.chart_days" :key="idx" class="bar-col">
            <div class="bar-track">
              <div
                class="bar-fill"
                :style="{ height: getBarHeight(day.total, maxDayTotal) + '%' }"
                :title="`${day.date}: ${day.total} sinh viên`"
              >
                <span v-if="day.total > 0" class="bar-tooltip">{{ day.total }}</span>
              </div>
            </div>
            <span class="bar-label">{{ day.date }}</span>
          </div>
        </div>
      </div>

      <!-- 4 Weeks Progress Card -->
      <div class="card chart-card card-hoverable">
        <div class="card-header">
          <div>
            <h3 class="card-title">Tiến độ theo tuần</h3>
            <p class="card-subtitle">Tổng lượt thực hành qua 4 tuần gần đây</p>
          </div>
          <span class="badge badge-success">4 Tuần</span>
        </div>

        <div class="weeks-list">
          <div v-for="(wk, idx) in stats.chart_weeks" :key="idx" class="week-item">
            <div class="week-info">
              <span class="week-name">{{ wk.week }} ({{ wk.range }})</span>
              <span class="week-count"><b>{{ wk.total }}</b> lượt</span>
            </div>
            <div class="progress-bar-track">
              <div
                class="progress-fill-bar"
                :style="{ width: getWeekPercent(wk.total) + '%' }"
              ></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Today Practice Sessions Section -->
    <div v-if="stats.today_sessions && stats.today_sessions.length > 0" class="card mb-4 card-hoverable">
      <div class="card-header">
        <div>
          <h3 class="card-title">📅 Lịch Buổi Thực Hành Hôm Nay</h3>
          <p class="card-subtitle">Các ca thực hành xưởng đang diễn ra hoặc sắp bắt đầu</p>
        </div>
        <router-link to="/practice-sessions" class="btn btn-secondary btn-sm">
          Quản lý lịch học &rarr;
        </router-link>
      </div>

      <div class="today-sessions-grid">
        <div v-for="sess in stats.today_sessions" :key="sess.id" class="today-session-item">
          <div class="session-top">
            <span class="session-class-badge">{{ sess.lop }}</span>
            <span :class="getStatusBadgeClass(sess.trang_thai)">{{ getStatusText(sess.trang_thai) }}</span>
          </div>
          <h4 class="session-name">{{ sess.ten_buoi }}</h4>
          <div class="session-sub">Môn: {{ sess.mon_hoc || 'Thực hành xưởng' }}</div>
          <div class="session-meta">
            <span>🏢 {{ sess.workshop?.ten_xuong }}</span>
            <span class="font-mono">⏰ {{ formatTimeOnly(sess.gio_bat_dau) }} - {{ formatTimeOnly(sess.gio_ket_thuc) }}</span>
          </div>
          <div class="session-footer">
            <span class="attended-tag">👥 <b>{{ sess.attendances_count || 0 }}</b> đã điểm danh</span>
            <router-link :to="{ path: '/scan', query: { session_id: sess.id } }" class="btn btn-primary btn-xs">
              📷 Quét QR
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Attendances Table -->
    <div class="card table-card card-hoverable">
      <div class="card-header">
        <div>
          <h3 class="card-title">Lượt điểm danh mới nhất hôm nay</h3>
          <p class="card-subtitle">Cập nhật thời gian thực khi sinh viên quét mã</p>
        </div>
        <router-link to="/attendance" class="btn btn-secondary btn-sm">
          Xem tất cả &rarr;
        </router-link>
      </div>

      <div class="table-container">
        <table class="custom-table">
          <thead>
            <tr>
              <th>Sinh viên</th>
              <th>Mã SV</th>
              <th>Lớp</th>
              <th>Xưởng</th>
              <th>Giờ Vào</th>
              <th>Giờ Ra</th>
              <th>Trạng thái</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!stats.recent_attendances || stats.recent_attendances.length === 0">
              <td colspan="7" class="text-center py-4 text-muted">
                Chưa có lượt điểm danh nào trong hôm nay.
              </td>
            </tr>
            <tr v-for="att in stats.recent_attendances" :key="att.id">
              <td>
                <div class="student-name-col">
                  <b>{{ att.student?.ho_ten }}</b>
                  <small class="text-muted">{{ att.student?.khoa || 'CNTT' }}</small>
                </div>
              </td>
              <td>
                <span class="badge-code font-mono">{{ att.student?.ma_sinh_vien }}</span>
              </td>
              <td>{{ att.student?.lop }}</td>
              <td>{{ att.workshop?.ten_xuong || 'Xưởng' }}</td>
              <td>
                <span class="time-tag in font-mono">📥 {{ formatTime(att.check_in) }}</span>
              </td>
              <td>
                <span v-if="att.check_out" class="time-tag out font-mono">📤 {{ formatTime(att.check_out) }}</span>
                <span v-else class="text-muted">--:--</span>
              </td>
              <td>
                <span :class="getStatusBadgeClass(att.status)">
                  {{ getStatusText(att.status) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../services/api'

const lecturerName = ref(localStorage.getItem('user_name') || 'Giảng viên')

const stats = ref({
  total_students: 0,
  attended_today: 0,
  unattended_today: 0,
  check_in_count: 0,
  check_out_count: 0,
  chart_days: [],
  chart_weeks: [],
  recent_attendances: []
})

const maxDayTotal = computed(() => {
  if (!stats.value.chart_days?.length) return 10
  const max = Math.max(...stats.value.chart_days.map(d => d.total))
  return max > 0 ? max : 10
})

const getBarHeight = (value, max) => {
  if (!value) return 4
  return Math.min(100, Math.max(12, Math.round((value / max) * 100)))
}

const getWeekPercent = (total) => {
  const max = 50
  return Math.min(100, Math.max(5, Math.round((total / max) * 100)))
}

const formatTime = (datetimeStr) => {
  if (!datetimeStr) return '--:--'
  const date = new Date(datetimeStr)
  return date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
}

const getStatusBadgeClass = (status) => {
  if (status === 'hoan_thanh' || status === 'da_ket_thuc') return 'badge badge-success'
  if (status === 'dung_gio' || status === 'dang_dien_ra') return 'badge badge-info'
  return 'badge badge-warning'
}

const getStatusText = (status) => {
  if (status === 'hoan_thanh') return 'Hoàn thành'
  if (status === 'dung_gio') return 'Đang ở xưởng'
  if (status === 'muon') return 'Đi muộn'
  if (status === 'dang_dien_ra') return 'Đang diễn ra'
  if (status === 'sap_dien_ra') return 'Sắp diễn ra'
  if (status === 'da_ket_thuc') return 'Đã kết thúc'
  return status
}

const formatTimeOnly = (timeStr) => {
  if (!timeStr) return '--:--'
  return timeStr.slice(0, 5)
}

const fetchDashboard = async () => {
  try {
    const res = await api.get('/dashboard')
    if (res.data.success) {
      stats.value = res.data.data
    }
  } catch (err) {
    console.error('Error fetching dashboard:', err)
  }
}

onMounted(() => {
  fetchDashboard()
})
</script>

<style scoped>
.dashboard-page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.animate-fade {
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(6px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Lecturer Hero Banner */
.lecturer-hero-card {
  position: relative;
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #1e1b4b 100%);
  color: white;
  border-radius: 20px;
  padding: 2rem 2.25rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 15px 35px -5px rgba(15, 23, 42, 0.3);
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.hero-glow-blob {
  position: absolute;
  width: 350px;
  height: 350px;
  background: radial-gradient(circle, rgba(59, 130, 246, 0.25) 0%, transparent 70%);
  top: -120px;
  right: -50px;
  pointer-events: none;
}

.hero-live-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(8px);
  color: #93c5fd;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.3rem 0.85rem;
  border-radius: 999px;
  margin-bottom: 0.75rem;
  border: 1px solid rgba(255, 255, 255, 0.12);
  letter-spacing: 0.03em;
}

.live-dot-green {
  width: 7px;
  height: 7px;
  background-color: #10b981;
  border-radius: 50%;
  box-shadow: 0 0 8px #10b981;
}

.hero-title {
  font-size: 1.8rem;
  font-weight: 800;
  color: #ffffff;
  letter-spacing: -0.02em;
}

.hero-title span {
  background: linear-gradient(135deg, #60a5fa, #93c5fd);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.hero-subtitle {
  font-size: 0.88rem;
  color: #94a3b8;
  margin-top: 0.35rem;
}

.hero-quick-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.btn-hero-action {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  border-radius: 12px;
  font-size: 0.88rem;
  font-weight: 700;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  text-decoration: none;
}

.btn-scan-glow {
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.2);
  box-shadow: 0 8px 20px -3px rgba(37, 99, 235, 0.5);
}

.btn-scan-glow:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 25px -4px rgba(37, 99, 235, 0.65);
}

.btn-ghost {
  background: rgba(255, 255, 255, 0.08);
  color: #e2e8f0;
  border: 1px solid rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(8px);
}

.btn-ghost:hover {
  background: rgba(255, 255, 255, 0.16);
  color: white;
  transform: translateY(-2px);
}

/* Stat Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
  gap: 1.25rem;
}

.stat-card {
  background: white;
  border-radius: 16px;
  padding: 1.35rem;
  border: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  gap: 1.15rem;
  box-shadow: var(--shadow-card);
}

.stat-blue { border-left: 4px solid #2563eb; }
.stat-green { border-left: 4px solid #10b981; }
.stat-amber { border-left: 4px solid #f59e0b; }
.stat-purple { border-left: 4px solid #6366f1; }

.stat-icon-wrapper {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.6rem;
  flex-shrink: 0;
}

.bg-blue-glow { background: #eff6ff; color: #2563eb; }
.bg-green-glow { background: #ecfdf5; color: #10b981; }
.bg-amber-glow { background: #fffbeb; color: #f59e0b; }
.bg-purple-glow { background: #f5f3ff; color: #6366f1; }

.stat-content {
  display: flex;
  flex-direction: column;
}

.stat-label {
  font-size: 0.78rem;
  color: #64748b;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.stat-value {
  font-size: 1.7rem;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.2;
  margin: 0.2rem 0;
}

.stat-value .slash {
  color: #cbd5e1;
  font-weight: 400;
}

.stat-sub {
  font-size: 0.74rem;
  color: #94a3b8;
}

/* Charts */
.charts-row {
  display: grid;
  grid-template-columns: 3fr 2fr;
  gap: 1.25rem;
}

@media (max-width: 900px) {
  .charts-row {
    grid-template-columns: 1fr;
  }
}

.chart-card {
  padding: 1.5rem;
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
}

.card-title {
  font-size: 1.15rem;
  font-weight: 800;
  color: #0f172a;
}

.card-subtitle {
  font-size: 0.8rem;
  color: #64748b;
  margin-top: 0.2rem;
}

.bar-chart-container {
  display: flex;
  align-items: flex-end;
  gap: 1rem;
  height: 190px;
  padding-top: 1.5rem;
}

.bar-col {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  height: 100%;
}

.bar-track {
  width: 100%;
  max-width: 38px;
  flex: 1;
  background-color: #f1f5f9;
  border-radius: 8px;
  display: flex;
  align-items: flex-end;
  position: relative;
}

.bar-fill {
  width: 100%;
  background: linear-gradient(180deg, #3b82f6 0%, #1d4ed8 100%);
  border-radius: 8px 8px 0 0;
  transition: height 0.6s cubic-bezier(0.16, 1, 0.3, 1);
  position: relative;
  box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);
}

.bar-tooltip {
  position: absolute;
  top: -24px;
  left: 50%;
  transform: translateX(-50%);
  font-size: 0.72rem;
  font-weight: 800;
  color: #2563eb;
  background: white;
  padding: 0.1rem 0.4rem;
  border-radius: 4px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.bar-label {
  font-size: 0.74rem;
  color: #64748b;
  margin-top: 0.5rem;
  font-weight: 600;
}

/* Weeks Progress */
.weeks-list {
  display: flex;
  flex-direction: column;
  gap: 1.15rem;
  margin-top: 0.5rem;
}

.week-info {
  display: flex;
  justify-content: space-between;
  font-size: 0.85rem;
  margin-bottom: 0.4rem;
}

.week-name {
  color: #334155;
  font-weight: 600;
}

.week-count {
  color: #2563eb;
}

.progress-bar-track {
  height: 9px;
  background: #f1f5f9;
  border-radius: 999px;
  overflow: hidden;
}

.progress-fill-bar {
  height: 100%;
  background: linear-gradient(90deg, #10b981 0%, #059669 100%);
  border-radius: 999px;
  transition: width 0.6s ease;
}

/* Today Sessions */
.today-sessions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1rem;
}

.today-session-item {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.15rem;
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
  transition: all 0.2s;
}

.today-session-item:hover {
  background: white;
  border-color: #cbd5e1;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

.session-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.session-class-badge {
  background: #eff6ff;
  color: #1d4ed8;
  font-weight: 800;
  font-size: 0.78rem;
  padding: 0.2rem 0.6rem;
  border-radius: 6px;
  border: 1px solid #bfdbfe;
}

.session-name {
  font-size: 0.98rem;
  font-weight: 800;
  color: #0f172a;
}

.session-sub {
  font-size: 0.8rem;
  color: #64748b;
}

.session-meta {
  display: flex;
  justify-content: space-between;
  font-size: 0.8rem;
  color: #475569;
  margin-top: 0.4rem;
  padding-top: 0.4rem;
  border-top: 1px dashed #e2e8f0;
}

.session-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 0.6rem;
}

.attended-tag {
  font-size: 0.82rem;
  color: #475569;
}

.btn-xs {
  padding: 0.35rem 0.75rem;
  font-size: 0.78rem;
  border-radius: 6px;
}

/* Recent Table Styles */
.student-name-col {
  display: flex;
  flex-direction: column;
}

.badge-code {
  background: #f1f5f9;
  color: #334155;
  padding: 0.2rem 0.5rem;
  border-radius: 6px;
  font-size: 0.82rem;
  font-weight: 700;
}

.time-tag {
  display: inline-block;
  padding: 0.25rem 0.55rem;
  border-radius: 6px;
  font-size: 0.82rem;
  font-weight: 700;
}

.time-tag.in {
  background: #eff6ff;
  color: #1d4ed8;
}

.time-tag.out {
  background: #ecfdf5;
  color: #047857;
}

@media (max-width: 768px) {
  .lecturer-hero-card {
    flex-direction: column;
    align-items: flex-start;
    padding: 1.5rem 1.25rem;
    gap: 1.25rem;
  }
  .hero-title {
    font-size: 1.45rem;
  }
  .hero-quick-actions {
    width: 100%;
  }
  .btn-hero-action {
    flex: 1;
    justify-content: center;
  }
}
</style>
