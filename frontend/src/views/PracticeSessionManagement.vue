<template>
  <div class="practice-sessions-page">
    <div class="card page-header-card">
      <div class="header-content">
        <div>
          <h2>📅 Quản Lý Buổi Thực Hành & Lịch Điểm Danh</h2>
          <p>Thiết lập lịch thực hành, liên kết Lớp - Xưởng - Giảng viên và theo dõi sĩ số điểm danh</p>
        </div>
        <button v-if="canManage" @click="openModal()" class="btn btn-primary">
          + Thêm buổi thực hành
        </button>
      </div>

      <!-- Filters Row -->
      <div class="filters-grid">
        <div class="form-group mb-0">
          <label class="form-label">Theo ngày học:</label>
          <input type="date" v-model="filters.ngay_hoc" @change="fetchSessions" class="form-control" />
        </div>

        <div class="form-group mb-0">
          <label class="form-label">Theo lớp:</label>
          <input
            v-model="filters.lop"
            @input="fetchSessions"
            placeholder="VD: D21CNTT01"
            class="form-control"
          />
        </div>

        <div class="form-group mb-0">
          <label class="form-label">Xưởng thực hành:</label>
          <select v-model="filters.workshop_id" @change="fetchSessions" class="form-control">
            <option value="">-- Tất cả các xưởng --</option>
            <option v-for="ws in workshops" :key="ws.id" :value="ws.id">{{ ws.ten_xuong }}</option>
          </select>
        </div>

        <div class="form-group mb-0">
          <label class="form-label">Trạng thái:</label>
          <select v-model="filters.trang_thai" @change="fetchSessions" class="form-control">
            <option value="">-- Tất cả trạng thái --</option>
            <option value="dang_dien_ra">Đang diễn ra</option>
            <option value="sap_dien_ra">Sắp diễn ra</option>
            <option value="da_ket_thuc">Đã kết thúc</option>
          </select>
        </div>

        <div class="form-group mb-0 btn-align">
          <button @click="resetFilters" class="btn btn-secondary btn-block">
            Xóa bộ lọc
          </button>
        </div>
      </div>
    </div>

    <!-- Sessions Table -->
    <div class="card mt-4">
      <div class="table-container">
        <table class="custom-table">
          <thead>
            <tr>
              <th>Tên Buổi Học / Môn Học</th>
              <th>Lớp</th>
              <th>Xưởng Thực Hành</th>
              <th>Ngày Học</th>
              <th>Khung Giờ</th>
              <th>Giảng Viên</th>
              <th>Trạng Thái</th>
              <th>Điểm Danh</th>
              <th class="text-right">Thao Tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="9" class="text-center py-4">Đang tải danh sách buổi thực hành...</td>
            </tr>
            <tr v-else-if="sessions.length === 0">
              <td colspan="9" class="text-center py-4">Không có buổi thực hành nào phù hợp</td>
            </tr>
            <tr v-for="sess in sessions" :key="sess.id">
              <td>
                <b>{{ sess.ten_buoi }}</b>
                <div class="sub-text">{{ sess.mon_hoc || 'Chưa gắn môn' }}</div>
              </td>
              <td><span class="badge badge-info">{{ sess.lop }}</span></td>
              <td>
                <b>{{ sess.workshop?.ten_xuong || 'Chưa gán' }}</b>
                <div class="sub-text">{{ sess.workshop?.dia_diem || '' }}</div>
              </td>
              <td>{{ formatDate(sess.ngay_hoc) }}</td>
              <td>
                <span class="time-range">{{ formatTime(sess.gio_bat_dau) }} - {{ formatTime(sess.gio_ket_thuc) }}</span>
              </td>
              <td>{{ sess.lecturer?.name || 'Chưa phân công' }}</td>
              <td>
                <span :class="getStatusBadgeClass(sess.trang_thai)">
                  {{ getStatusText(sess.trang_thai) }}
                </span>
              </td>
              <td>
                <button @click="viewSessionDetail(sess)" class="btn btn-secondary btn-xs">
                  👥 {{ sess.attendances_count || 0 }} Đã điểm danh
                </button>
              </td>
              <td class="text-right">
                <router-link
                  :to="{ path: '/scan', query: { session_id: sess.id } }"
                  class="btn-action scan"
                  title="Điểm danh buổi này"
                >
                  📷
                </router-link>
                <button v-if="canManage" @click="openModal(sess)" class="btn-action edit" title="Sửa">✏️</button>
                <button v-if="canManage" @click="deleteSession(sess)" class="btn-action delete" title="Xóa">🗑️</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Form Create / Edit Session -->
    <div v-if="showModal" class="modal-overlay">
      <div class="modal-content modal-lg">
        <div class="modal-header">
          <h3>{{ editingId ? 'Cập nhật buổi thực hành' : 'Tạo mới buổi thực hành' }}</h3>
          <button @click="showModal = false" class="btn-close">&times;</button>
        </div>

        <form @submit.prevent="saveSession" class="modal-form">
          <div class="form-row">
            <div class="form-group flex-1">
              <label class="form-label">Tên buổi thực hành (*)</label>
              <input
                v-model="form.ten_buoi"
                class="form-control"
                placeholder="VD: Buổi 1: Cấu hình Switch & Router"
                required
              />
            </div>
            <div class="form-group flex-1">
              <label class="form-label">Môn học</label>
              <input
                v-model="form.mon_hoc"
                class="form-control"
                placeholder="VD: Mạng máy tính căn bản"
              />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group flex-1">
              <label class="form-label">Lớp sinh viên (*)</label>
              <input
                v-model="form.lop"
                class="form-control"
                placeholder="VD: D21CNTT01"
                required
              />
            </div>
            <div class="form-group flex-1">
              <label class="form-label">Xưởng thực hành (*)</label>
              <select v-model="form.workshop_id" class="form-control" required>
                <option value="" disabled>-- Chọn xưởng thực hành --</option>
                <option v-for="ws in workshops" :key="ws.id" :value="ws.id">
                  {{ ws.ten_xuong }} ({{ ws.dia_diem || 'Phòng xưởng' }})
                </option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group flex-1">
              <label class="form-label">Ngày học (*)</label>
              <input type="date" v-model="form.ngay_hoc" class="form-control" required />
            </div>
            <div class="form-group flex-1">
              <label class="form-label">Giờ bắt đầu (*)</label>
              <input type="time" v-model="form.gio_bat_dau" class="form-control" required />
            </div>
            <div class="form-group flex-1">
              <label class="form-label">Giờ kết thúc (*)</label>
              <input type="time" v-model="form.gio_ket_thuc" class="form-control" required />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group flex-1">
              <label class="form-label">Trạng thái</label>
              <select v-model="form.trang_thai" class="form-control">
                <option value="dang_dien_ra">Đang diễn ra</option>
                <option value="sap_dien_ra">Sắp diễn ra</option>
                <option value="da_ket_thuc">Đã kết thúc</option>
              </select>
            </div>
            <div class="form-group flex-2">
              <label class="form-label">Ghi chú yêu cầu buổi học</label>
              <input
                v-model="form.ghi_chu"
                class="form-control"
                placeholder="VD: Mang laptop, chuẩn bị cáp mạng..."
              />
            </div>
          </div>

          <div class="modal-actions">
            <button type="button" @click="showModal = false" class="btn btn-secondary">Hủy</button>
            <button type="submit" class="btn btn-primary" :disabled="saving">
              {{ saving ? 'Đang lưu...' : 'Lưu buổi học' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal View Session Attendance Detail -->
    <div v-if="showDetailModal && detailData" class="modal-overlay">
      <div class="modal-content modal-xl">
        <div class="modal-header">
          <div>
            <h3>📋 Chi Tiết Điểm Danh: {{ detailData.session.ten_buoi }}</h3>
            <p class="sub-text">
              Lớp: <b>{{ detailData.session.lop }}</b> | 
              Xưởng: <b>{{ detailData.session.workshop?.ten_xuong }}</b> | 
              Ngày: <b>{{ formatDate(detailData.session.ngay_hoc) }}</b> ({{ formatTime(detailData.session.gio_bat_dau) }} - {{ formatTime(detailData.session.gio_ket_thuc) }})
            </p>
          </div>
          <button @click="showDetailModal = false" class="btn-close">&times;</button>
        </div>

        <!-- Attendance Stats Summary -->
        <div class="detail-stats-grid">
          <div class="mini-stat bg-blue-light">
            <span class="mini-lbl">Tổng SV Lớp</span>
            <span class="mini-val">{{ detailData.total_students }}</span>
          </div>
          <div class="mini-stat bg-green-light">
            <span class="mini-lbl">Đã Điểm Danh</span>
            <span class="mini-val text-success">{{ detailData.attended_count }}</span>
          </div>
          <div class="mini-stat bg-red-light">
            <span class="mini-lbl">Chưa Điểm Danh (Vắng)</span>
            <span class="mini-val text-danger">{{ detailData.absent_count }}</span>
          </div>
        </div>

        <!-- Student Attendance List -->
        <div class="table-container mt-3">
          <table class="custom-table">
            <thead>
              <tr>
                <th>Mã SV</th>
                <th>Họ và Tên</th>
                <th>Lớp</th>
                <th>Check-in (Giờ vào)</th>
                <th>Check-out (Giờ ra)</th>
                <th>Trạng Thái</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in detailData.students_status" :key="item.student.id">
                <td><b>{{ item.student.ma_sinh_vien }}</b></td>
                <td>{{ item.student.ho_ten }}</td>
                <td>{{ item.student.lop }}</td>
                <td>
                  <span v-if="item.attendance?.check_in" class="time-tag in">
                    {{ formatFullTime(item.attendance.check_in) }}
                  </span>
                  <span v-else class="text-muted">--:--</span>
                </td>
                <td>
                  <span v-if="item.attendance?.check_out" class="time-tag out">
                    {{ formatFullTime(item.attendance.check_out) }}
                  </span>
                  <span v-else-if="item.attendance?.check_in" class="text-warning">Đang ở xưởng</span>
                  <span v-else class="text-muted">--:--</span>
                </td>
                <td>
                  <span v-if="!item.attended" class="badge badge-danger">Chưa điểm danh</span>
                  <span v-else-if="item.attendance.status === 'hoan_thanh'" class="badge badge-success">Hoàn thành</span>
                  <span v-else-if="item.attendance.status === 'muon'" class="badge badge-warning">Đi muộn</span>
                  <span v-else class="badge badge-info">Đúng giờ</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="modal-actions mt-3">
          <router-link
            :to="{ path: '/scan', query: { session_id: detailData.session.id } }"
            class="btn btn-primary"
            @click="showDetailModal = false"
          >
            📷 Mở Webcam Điểm Danh Buổi Này
          </router-link>
          <button @click="showDetailModal = false" class="btn btn-secondary">Đóng</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../services/api'

const sessions = ref([])
const workshops = ref([])
const loading = ref(false)
const saving = ref(false)
const showModal = ref(false)
const showDetailModal = ref(false)
const editingId = ref(null)
const detailData = ref(null)

const userRole = ref(localStorage.getItem('user_role') || 'can_bo')
const canManage = computed(() => userRole.value === 'admin' || userRole.value === 'can_bo')

const filters = ref({
  ngay_hoc: '',
  lop: '',
  workshop_id: '',
  trang_thai: ''
})

const form = ref({
  ten_buoi: '',
  mon_hoc: '',
  lop: '',
  workshop_id: '',
  ngay_hoc: new Date().toISOString().slice(0, 10),
  gio_bat_dau: '07:30',
  gio_ket_thuc: '11:30',
  trang_thai: 'dang_dien_ra',
  ghi_chu: ''
})

const fetchWorkshops = async () => {
  try {
    const res = await api.get('/workshops')
    if (res.data.success) {
      workshops.value = res.data.data
      if (!form.value.workshop_id && workshops.value.length > 0) {
        form.value.workshop_id = workshops.value[0].id
      }
    }
  } catch (err) {
    console.error('Lỗi khi tải danh sách xưởng:', err)
  }
}

const fetchSessions = async () => {
  loading.value = true
  try {
    const res = await api.get('/practice-sessions', {
      params: {
        ngay_hoc: filters.value.ngay_hoc || undefined,
        lop: filters.value.lop || undefined,
        workshop_id: filters.value.workshop_id || undefined,
        trang_thai: filters.value.trang_thai || undefined,
      }
    })
    if (res.data.success) {
      sessions.value = res.data.data
    }
  } catch (err) {
    console.error('Lỗi khi tải buổi thực hành:', err)
  } finally {
    loading.value = false
  }
}

const resetFilters = () => {
  filters.value = { ngay_hoc: '', lop: '', workshop_id: '', trang_thai: '' }
  fetchSessions()
}

const openModal = (sess = null) => {
  if (sess) {
    editingId.value = sess.id
    form.value = {
      ten_buoi: sess.ten_buoi,
      mon_hoc: sess.mon_hoc,
      lop: sess.lop,
      workshop_id: sess.workshop_id,
      ngay_hoc: sess.ngay_hoc ? sess.ngay_hoc.slice(0, 10) : '',
      gio_bat_dau: sess.gio_bat_dau?.slice(0, 5) || '07:30',
      gio_ket_thuc: sess.gio_ket_thuc?.slice(0, 5) || '11:30',
      trang_thai: sess.trang_thai || 'dang_dien_ra',
      ghi_chu: sess.ghi_chu || ''
    }
  } else {
    editingId.value = null
    form.value = {
      ten_buoi: '',
      mon_hoc: '',
      lop: '',
      workshop_id: workshops.value[0]?.id || '',
      ngay_hoc: new Date().toISOString().slice(0, 10),
      gio_bat_dau: '07:30',
      gio_ket_thuc: '11:30',
      trang_thai: 'dang_dien_ra',
      ghi_chu: ''
    }
  }
  showModal.value = true
}

const saveSession = async () => {
  saving.value = true
  try {
    if (editingId.value) {
      await api.put(`/practice-sessions/${editingId.value}`, form.value)
    } else {
      await api.post('/practice-sessions', form.value)
    }
    showModal.value = false
    fetchSessions()
  } catch (err) {
    alert('Lỗi lưu buổi thực hành: ' + (err.response?.data?.message || err.message))
  } finally {
    saving.value = false
  }
}

const deleteSession = async (sess) => {
  if (confirm(`Bạn có chắc muốn xóa buổi học "${sess.ten_buoi}"?`)) {
    try {
      await api.delete(`/practice-sessions/${sess.id}`)
      fetchSessions()
    } catch (err) {
      alert('Không thể xóa buổi thực hành: ' + (err.response?.data?.message || err.message))
    }
  }
}

const viewSessionDetail = async (sess) => {
  try {
    const res = await api.get(`/practice-sessions/${sess.id}`)
    if (res.data.success) {
      detailData.value = res.data.data
      showDetailModal.value = true
    }
  } catch (err) {
    alert('Không thể tải chi tiết điểm danh buổi học: ' + (err.response?.data?.message || err.message))
  }
}

const formatDate = (dateStr) => {
  if (!dateStr) return '--'
  return new Date(dateStr).toLocaleDateString('vi-VN')
}

const formatTime = (timeStr) => {
  if (!timeStr) return '--:--'
  return timeStr.slice(0, 5)
}

const formatFullTime = (dtStr) => {
  if (!dtStr) return '--:--'
  return new Date(dtStr).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
}

const getStatusBadgeClass = (st) => {
  if (st === 'dang_dien_ra') return 'badge badge-success'
  if (st === 'sap_dien_ra') return 'badge badge-info'
  return 'badge badge-secondary'
}

const getStatusText = (st) => {
  if (st === 'dang_dien_ra') return 'Đang diễn ra'
  if (st === 'sap_dien_ra') return 'Sắp diễn ra'
  return 'Đã kết thúc'
}

onMounted(() => {
  fetchWorkshops()
  fetchSessions()
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

.mb-0 { margin-bottom: 0; }
.mt-3 { margin-top: 1rem; }
.mt-4 { margin-top: 1.25rem; }

.sub-text {
  font-size: 0.78rem;
  color: var(--gray-500);
}

.time-range {
  font-family: monospace;
  font-weight: 600;
  background: var(--gray-100);
  padding: 0.2rem 0.5rem;
  border-radius: var(--radius-sm);
}

.time-tag {
  font-family: monospace;
  font-size: 0.85rem;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  font-weight: 600;
}
.time-tag.in { background: #eff6ff; color: #1d4ed8; }
.time-tag.out { background: #ecfdf5; color: #047857; }

.text-right { text-align: right; }
.text-center { text-align: center; }
.text-success { color: var(--success); }
.text-danger { color: var(--danger); }
.text-warning { color: #d97706; }
.text-muted { color: var(--gray-400); }

.btn-action {
  background: none;
  border: none;
  font-size: 1.1rem;
  cursor: pointer;
  padding: 0.35rem;
  border-radius: var(--radius-sm);
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.btn-action:hover { background: var(--gray-100); }

.modal-lg { max-width: 650px; }
.modal-xl { max-width: 850px; }

.form-row {
  display: flex;
  gap: 1rem;
  margin-bottom: 0.85rem;
}

.flex-1 { flex: 1; }
.flex-2 { flex: 2; }

.detail-stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  margin-top: 1rem;
}

.mini-stat {
  padding: 0.85rem 1rem;
  border-radius: var(--radius-sm);
  display: flex;
  flex-direction: column;
}

.bg-blue-light { background: #eff6ff; border: 1px solid #bfdbfe; }
.bg-green-light { background: #ecfdf5; border: 1px solid #a7f3d0; }
.bg-red-light { background: #fef2f2; border: 1px solid #fecaca; }

.mini-lbl { font-size: 0.78rem; color: var(--gray-600); font-weight: 600; }
.mini-val { font-size: 1.4rem; font-weight: 800; margin-top: 0.2rem; }

.py-4 { padding-top: 1.5rem; padding-bottom: 1.5rem; }
</style>
