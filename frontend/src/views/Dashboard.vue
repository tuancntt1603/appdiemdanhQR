<template>
  <div class="dashboard-page">
    <!-- Stat Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon bg-blue">👥</div>
        <div class="stat-content">
          <span class="stat-label">Tổng số sinh viên</span>
          <span class="stat-value">{{ stats.total_students || 0 }}</span>
          <span class="stat-sub">Đã đăng ký vào hệ thống</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon bg-green">✅</div>
        <div class="stat-content">
          <span class="stat-label">Đã điểm danh hôm nay</span>
          <span class="stat-value">{{ stats.attended_today || 0 }}</span>
          <span class="stat-sub">Sinh viên có mặt tại xưởng</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon bg-amber">⏳</div>
        <div class="stat-content">
          <span class="stat-label">Chưa điểm danh hôm nay</span>
          <span class="stat-value">{{ stats.unattended_today || 0 }}</span>
          <span class="stat-sub">Chưa quét mã vào</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon bg-purple">🚪</div>
        <div class="stat-content">
          <span class="stat-label">Số lượt Vào / Ra</span>
          <span class="stat-value">{{ stats.check_in_count || 0 }} / {{ stats.check_out_count || 0 }}</span>
          <span class="stat-sub">Lượt vào / Lượt ra hoàn thành</span>
        </div>
      </div>
    </div>

    <!-- Charts Section -->
    <div class="charts-row">
      <!-- 7 Days Bar Chart -->
      <div class="card chart-card">
        <div class="card-header">
          <div>
            <h3 class="card-title">Điểm danh 7 ngày gần nhất</h3>
            <p class="card-subtitle">Số lượng sinh viên có mặt theo từng ngày</p>
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
      <div class="card chart-card">
        <div class="card-header">
          <div>
            <h3 class="card-title">Điểm danh theo tuần</h3>
            <p class="card-subtitle">Tổng lượt thực hành qua các tuần gần đây</p>
          </div>
          <span class="badge badge-success">4 Tuần</span>
        </div>

        <div class="weeks-list">
          <div v-for="(wk, idx) in stats.chart_weeks" :key="idx" class="week-item">
            <div class="week-info">
              <span class="week-name">{{ wk.week }} ({{ wk.range }})</span>
              <span class="week-count"><b>{{ wk.total }}</b> lượt</span>
            </div>
            <div class="progress-bar">
              <div
                class="progress-fill"
                :style="{ width: getWeekPercent(wk.total) + '%' }"
              ></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Attendances Table -->
    <div class="card table-card">
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
              <th>Mã SV</th>
              <th>Họ và tên</th>
              <th>Lớp</th>
              <th>Xưởng</th>
              <th>Giờ vào</th>
              <th>Giờ ra</th>
              <th>Trạng thái</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!stats.recent_attendances || stats.recent_attendances.length === 0">
              <td colspan="7" class="text-center py-4">Chưa có sinh viên nào điểm danh hôm nay</td>
            </tr>
            <tr v-for="att in stats.recent_attendances" :key="att.id">
              <td><b>{{ att.student?.ma_sinh_vien }}</b></td>
              <td>{{ att.student?.ho_ten }}</td>
              <td>{{ att.student?.lop }}</td>
              <td>{{ att.workshop?.ten_xuong || 'Xưởng chung' }}</td>
              <td><span class="time-tag in">{{ formatTime(att.check_in) }}</span></td>
              <td>
                <span v-if="att.check_out" class="time-tag out">{{ formatTime(att.check_out) }}</span>
                <span v-else class="text-muted">Đang trong xưởng...</span>
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
  if (status === 'hoan_thanh') return 'badge badge-success'
  if (status === 'dung_gio') return 'badge badge-info'
  return 'badge badge-warning'
}

const getStatusText = (status) => {
  if (status === 'hoan_thanh') return 'Hoàn thành'
  if (status === 'dung_gio') return 'Đang ở xưởng'
  return 'Muộn'
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

/* Stat Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.25rem;
}

.stat-card {
  background: white;
  border-radius: var(--radius-md);
  padding: 1.25rem;
  border: 1px solid var(--gray-200);
  display: flex;
  align-items: center;
  gap: 1.25rem;
  box-shadow: var(--shadow-sm);
}

.stat-icon {
  width: 52px;
  height: 52px;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.6rem;
  flex-shrink: 0;
}

.bg-blue { background: #eff6ff; }
.bg-green { background: #ecfdf5; }
.bg-amber { background: #fffbeb; }
.bg-purple { background: #f5f3ff; }

.stat-content {
  display: flex;
  flex-direction: column;
}

.stat-label {
  font-size: 0.8rem;
  color: var(--gray-500);
  font-weight: 500;
}

.stat-value {
  font-size: 1.65rem;
  font-weight: 700;
  color: var(--gray-900);
  line-height: 1.2;
  margin: 0.2rem 0;
}

.stat-sub {
  font-size: 0.72rem;
  color: var(--gray-400);
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

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.25rem;
}

.card-title {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--gray-900);
}

.card-subtitle {
  font-size: 0.78rem;
  color: var(--gray-500);
}

/* Bar Chart */
.bar-chart-container {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  height: 200px;
  padding-top: 1.5rem;
  gap: 0.75rem;
}

.bar-col {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  height: 100%;
}

.bar-track {
  flex: 1;
  width: 100%;
  max-width: 36px;
  background: var(--gray-100);
  border-radius: var(--radius-sm);
  display: flex;
  align-items: flex-end;
  overflow: hidden;
  position: relative;
}

.bar-fill {
  width: 100%;
  background: linear-gradient(180deg, #3b82f6 0%, #2563eb 100%);
  border-radius: var(--radius-sm) var(--radius-sm) 0 0;
  transition: height 0.6s ease;
  position: relative;
}

.bar-tooltip {
  position: absolute;
  top: -22px;
  left: 50%;
  transform: translateX(-50%);
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--primary);
}

.bar-label {
  font-size: 0.75rem;
  color: var(--gray-500);
  margin-top: 0.5rem;
  font-weight: 500;
}

/* Weeks Progress */
.weeks-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-top: 0.5rem;
}

.week-info {
  display: flex;
  justify-content: space-between;
  font-size: 0.85rem;
  margin-bottom: 0.35rem;
}

.week-name {
  color: var(--gray-700);
  font-weight: 500;
}

.week-count {
  color: var(--primary);
}

.progress-bar {
  height: 10px;
  background: var(--gray-100);
  border-radius: 999px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #10b981 0%, #059669 100%);
  border-radius: 999px;
  transition: width 0.5s ease;
}

/* Time Tags */
.time-tag {
  font-family: monospace;
  font-size: 0.85rem;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  font-weight: 600;
}
.time-tag.in {
  background: #eff6ff;
  color: #1d4ed8;
}
.time-tag.out {
  background: #ecfdf5;
  color: #047857;
}

.text-muted {
  color: var(--gray-400);
  font-style: italic;
  font-size: 0.8rem;
}

.text-center {
  text-align: center;
}
.py-4 {
  padding-top: 1.5rem;
  padding-bottom: 1.5rem;
}
</style>
