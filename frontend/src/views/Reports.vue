<template>
  <div class="reports-page">
    <div class="card page-header-card">
      <div class="header-content">
        <div>
          <h2>📈 Thống Kê Chuyên Cần & Báo Cáo</h2>
          <p>Tính toán tỷ lệ chuyên cần theo tuần, theo kỳ và xuất báo cáo file Excel (.xlsx)</p>
        </div>

        <button @click="exportExcel" class="btn btn-success" :disabled="exporting">
          <span v-if="exporting">Đang tải file...</span>
          <span v-else>📊 Xuất Báo Cáo Excel</span>
        </button>
      </div>

      <!-- Tab selection -->
      <div class="report-tabs">
        <button
          @click="activeTab = 'weekly'"
          class="tab-btn"
          :class="{ active: activeTab === 'weekly' }"
        >
          Theo Tuần
        </button>
        <button
          @click="activeTab = 'semester'"
          class="tab-btn"
          :class="{ active: activeTab === 'semester' }"
        >
          Theo Học Kỳ (3 Tháng)
        </button>
      </div>

      <!-- Filters -->
      <div class="filters-row">
        <div v-if="activeTab === 'weekly'" class="filter-item">
          <label class="form-label">Chọn tuần:</label>
          <select v-model="weekOffset" @change="fetchReport" class="form-control">
            <option :value="0">Tuần hiện tại</option>
            <option :value="1">Tuần trước</option>
            <option :value="2">Cách 2 tuần</option>
            <option :value="3">Cách 3 tuần</option>
          </select>
        </div>

        <div class="filter-item">
          <label class="form-label">Buổi thực hành:</label>
          <select v-model="selectedSessionId" @change="fetchReport" class="form-control">
            <option value="">-- Tất cả các buổi --</option>
            <option v-for="ps in practiceSessions" :key="ps.id" :value="ps.id">
              {{ ps.ten_buoi }} ({{ ps.lop }})
            </option>
          </select>
        </div>

        <div class="filter-item">
          <label class="form-label">Lọc theo Lớp:</label>
          <input
            v-model="filterLop"
            @input="fetchReport"
            placeholder="VD: D21CNTT01"
            class="form-control"
          />
        </div>
      </div>
    </div>

    <!-- Report Table -->
    <div class="card mt-4">
      <div class="card-header">
        <h3 class="card-title">
          Bảng Điểm Chuyên Cần ({{ reportMeta.start_date || '...' }} - {{ reportMeta.end_date || '...' }})
        </h3>
        <span class="badge badge-info">Tổng số sinh viên: {{ reportItems.length }}</span>
      </div>

      <div class="table-container">
        <table class="custom-table">
          <thead>
            <tr>
              <th>STT</th>
              <th>Mã Sinh Viên</th>
              <th>Họ và Tên</th>
              <th>Lớp</th>
              <th class="text-center">Tổng số buổi</th>
              <th class="text-center">Số buổi có mặt</th>
              <th class="text-center">Số buổi vắng</th>
              <th class="text-center">Tỷ lệ chuyên cần</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="8" class="text-center py-4">Đang tổng hợp báo cáo...</td>
            </tr>
            <tr v-else-if="reportItems.length === 0">
              <td colspan="8" class="text-center py-4">Không có dữ liệu thống kê cho kỳ này</td>
            </tr>
            <tr v-for="(item, idx) in reportItems" :key="item.student_id">
              <td>{{ idx + 1 }}</td>
              <td><b>{{ item.ma_sinh_vien }}</b></td>
              <td>{{ item.ho_ten }}</td>
              <td><span class="badge badge-info">{{ item.lop }}</span></td>
              <td class="text-center">{{ item.tong_buoi }}</td>
              <td class="text-center font-bold text-success">{{ item.co_mat }}</td>
              <td class="text-center font-bold text-danger">{{ item.vang }}</td>
              <td class="text-center">
                <div class="rate-cell">
                  <div class="rate-progress">
                    <div
                      class="rate-bar"
                      :style="{ width: item.ty_le + '%' }"
                      :class="getRateColorClass(item.ty_le)"
                    ></div>
                  </div>
                  <span class="rate-text" :class="getRateColorClass(item.ty_le)">
                    {{ item.ty_le }}%
                  </span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import api from '../services/api'

const activeTab = ref('weekly')
const weekOffset = ref(0)
const filterLop = ref('')
const selectedSessionId = ref('')
const practiceSessions = ref([])
const reportItems = ref([])
const reportMeta = ref({})
const loading = ref(false)
const exporting = ref(false)

const fetchSessions = async () => {
  try {
    const res = await api.get('/practice-sessions')
    if (res.data.success) {
      practiceSessions.value = res.data.data
    }
  } catch (err) {
    console.error('Lỗi khi tải buổi thực hành:', err)
  }
}

const fetchReport = async () => {
  loading.value = true
  try {
    const endpoint = activeTab.value === 'weekly' ? '/reports/weekly' : '/reports/semester'
    const params = {
      week: weekOffset.value,
      lop: filterLop.value || undefined,
      practice_session_id: selectedSessionId.value || undefined,
    }

    const res = await api.get(endpoint, { params })
    if (res.data.success) {
      reportItems.value = res.data.data.items || []
      reportMeta.value = res.data.data
    }
  } catch (err) {
    console.error('Lỗi khi lấy báo cáo chuyên cần:', err)
  } finally {
    loading.value = false
  }
}

watch(activeTab, () => {
  fetchReport()
})

const getRateColorClass = (rate) => {
  if (rate >= 80) return 'text-green'
  if (rate >= 50) return 'text-amber'
  return 'text-red'
}

const exportExcel = async () => {
  exporting.value = true
  try {
    const response = await api.get('/reports/export', {
      params: {
        lop: filterLop.value || undefined,
        practice_session_id: selectedSessionId.value || undefined,
      },
      responseType: 'blob'
    })

    // Kích hoạt download file từ blob
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    const today = new Date().toISOString().slice(0, 10)
    link.setAttribute('download', `bao_cao_diem_danh_${today}.xlsx`)
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (err) {
    console.error('Lỗi khi tải file Excel:', err)
    alert('Không thể xuất báo cáo Excel, vui lòng thử lại!')
  } finally {
    exporting.value = false
  }
}

onMounted(() => {
  fetchSessions()
  fetchReport()
})
</script>

<style scoped>
.page-header-card {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
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

.report-tabs {
  display: flex;
  gap: 0.5rem;
  border-bottom: 1px solid var(--gray-200);
  padding-bottom: 0.5rem;
}

.tab-btn {
  padding: 0.5rem 1.2rem;
  border-radius: var(--radius-sm);
  border: none;
  background: none;
  font-weight: 600;
  font-size: 0.85rem;
  color: var(--gray-500);
  cursor: pointer;
  transition: all 0.2s;
}

.tab-btn:hover {
  color: var(--gray-800);
  background: var(--gray-100);
}

.tab-btn.active {
  background: var(--primary);
  color: white;
}

.filters-row {
  display: flex;
  gap: 1.25rem;
}

.filter-item {
  width: 220px;
}

.mt-4 {
  margin-top: 1.25rem;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.card-title {
  font-size: 1.05rem;
  font-weight: 700;
}

.text-center {
  text-align: center;
}

.font-bold {
  font-weight: 700;
}

.text-success { color: var(--success); }
.text-danger { color: var(--danger); }

.rate-cell {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
}

.rate-progress {
  width: 90px;
  height: 8px;
  background: var(--gray-100);
  border-radius: 999px;
  overflow: hidden;
}

.rate-bar {
  height: 100%;
  border-radius: 999px;
}

.rate-bar.text-green { background: var(--success); }
.rate-bar.text-amber { background: var(--warning); }
.rate-bar.text-red { background: var(--danger); }

.rate-text {
  font-size: 0.85rem;
  font-weight: 700;
  width: 44px;
  text-align: left;
}

.rate-text.text-green { color: var(--success); }
.rate-text.text-amber { color: var(--warning); }
.rate-text.text-red { color: var(--danger); }

.py-4 {
  padding-top: 1.5rem;
  padding-bottom: 1.5rem;
}
</style>
