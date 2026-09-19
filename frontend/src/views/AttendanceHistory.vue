<template>
  <div class="history-page">
    <div class="card page-header-card">
      <div class="header-content">
        <div>
          <h2>🕒 Lịch Sử Điểm Danh Xưởng</h2>
          <p>Xem toàn bộ lượt quét giờ Vào và giờ Ra của sinh viên tại các xưởng</p>
        </div>
      </div>

      <!-- Filters Row -->
      <div class="filters-grid">
        <div class="form-group mb-0">
          <label class="form-label">Theo ngày:</label>
          <input type="date" v-model="filters.date" @change="fetchHistory" class="form-control" />
        </div>

        <div class="form-group mb-0">
          <label class="form-label">Buổi thực hành:</label>
          <select v-model="filters.practice_session_id" @change="fetchHistory" class="form-control">
            <option value="">-- Tất cả các buổi --</option>
            <option v-for="ps in practiceSessions" :key="ps.id" :value="ps.id">
              {{ ps.ten_buoi }} ({{ ps.lop }})
            </option>
          </select>
        </div>

        <div class="form-group mb-0">
          <label class="form-label">Theo lớp:</label>
          <input
            v-model="filters.lop"
            @input="fetchHistory"
            placeholder="VD: D21CNTT01"
            class="form-control"
          />
        </div>

        <div class="form-group mb-0">
          <label class="form-label">Theo mã sinh viên:</label>
          <input
            v-model="filters.ma_sinh_vien"
            @input="fetchHistory"
            placeholder="VD: SV001"
            class="form-control"
          />
        </div>

        <div class="form-group mb-0 btn-align">
          <button @click="resetFilters" class="btn btn-secondary btn-block">
            Xóa bộ lọc
          </button>
        </div>
      </div>
    </div>

    <!-- Attendance Table -->
    <div class="card mt-4">
      <div class="table-container">
        <table class="custom-table">
          <thead>
            <tr>
              <th>STT</th>
              <th>Mã SV</th>
              <th>Họ và Tên</th>
              <th>Lớp</th>
              <th>Buổi Thực Hành</th>
              <th>Xưởng</th>
              <th>Ngày</th>
              <th>Giờ Vào</th>
              <th>Giờ Ra</th>
              <th>Trạng thái</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="10" class="text-center py-4">Đang tải lịch sử điểm danh...</td>
            </tr>
            <tr v-else-if="attendances.length === 0">
              <td colspan="10" class="text-center py-4">Không có bản ghi điểm danh nào phù hợp</td>
            </tr>
            <tr v-for="(att, idx) in attendances" :key="att.id">
              <td>{{ idx + 1 }}</td>
              <td><b>{{ att.student?.ma_sinh_vien }}</b></td>
              <td>{{ att.student?.ho_ten }}</td>
              <td>{{ att.student?.lop }}</td>
              <td>
                <span class="badge badge-info">{{ att.practice_session ? att.practice_session.ten_buoi : 'Chung' }}</span>
              </td>
              <td>{{ att.workshop?.ten_xuong || 'Xưởng chung' }}</td>
              <td>{{ formatDate(att.check_in) }}</td>
              <td><span class="time-tag in">{{ formatTime(att.check_in) }}</span></td>
              <td>
                <span v-if="att.check_out" class="time-tag out">{{ formatTime(att.check_out) }}</span>
                <span v-else class="text-muted">Chưa check-out</span>
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
import { ref, onMounted } from 'vue'
import api from '../services/api'

const attendances = ref([])
const practiceSessions = ref([])
const loading = ref(false)
const filters = ref({
  date: '',
  lop: '',
  ma_sinh_vien: '',
  practice_session_id: ''
})

const fetchSessions = async () => {
  try {
    const res = await api.get('/practice-sessions')
    if (res.data.success) {
      practiceSessions.value = res.data.data
    }
  } catch (err) {
    console.error('Lỗi lấy buổi thực hành:', err)
  }
}

const fetchHistory = async () => {
  loading.value = true
  try {
    const res = await api.get('/attendance', {
      params: {
        date: filters.value.date || undefined,
        lop: filters.value.lop || undefined,
        ma_sinh_vien: filters.value.ma_sinh_vien || undefined,
        practice_session_id: filters.value.practice_session_id || undefined
      }
    })
    if (res.data.success) {
      attendances.value = res.data.data
    }
  } catch (err) {
    console.error('Lỗi lấy lịch sử điểm danh:', err)
  } finally {
    loading.value = false
  }
}

const resetFilters = () => {
  filters.value = { date: '', lop: '', ma_sinh_vien: '', practice_session_id: '' }
  fetchHistory()
}

const formatDate = (datetimeStr) => {
  if (!datetimeStr) return '--'
  const date = new Date(datetimeStr)
  return date.toLocaleDateString('vi-VN')
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

onMounted(() => {
  fetchSessions()
  fetchHistory()
})
</script>

<style scoped>
.page-header-card {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.header-content h2 {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--gray-900);
}

.header-content p {
  font-size: 0.82rem;
  color: var(--gray-500);
}

.filters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 1rem;
}

.btn-align {
  display: flex;
  align-items: flex-end;
}

.mb-0 {
  margin-bottom: 0;
}

.mt-4 {
  margin-top: 1.25rem;
}

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
