<template>
  <div class="student-page">
    <div class="card page-header-card">
      <div class="header-content">
        <div>
          <h2>🎓 Quản Lý Sinh Viên</h2>
          <p>Danh sách sinh viên xưởng thực hành, xem thông tin và mã QR cá nhân</p>
        </div>
        <button @click="openModal()" class="btn btn-primary">
          + Thêm sinh viên mới
        </button>
      </div>

      <!-- Search & Filters -->
      <div class="filters-row">
        <div class="search-box">
          <input
            v-model="keyword"
            @input="fetchStudents"
            placeholder="Tìm theo mã SV, họ tên, lớp..."
            class="form-control"
          />
        </div>
        <div class="filter-lop">
          <select v-model="selectedLop" @change="fetchStudents" class="form-control">
            <option value="">-- Tất cả các lớp --</option>
            <option v-for="l in classes" :key="l" :value="l">{{ l }}</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Student Table -->
    <div class="card mt-4">
      <div class="table-container">
        <table class="custom-table">
          <thead>
            <tr>
              <th>STT</th>
              <th>Mã Sinh Viên</th>
              <th>Họ và Tên</th>
              <th>Email</th>
              <th>Lớp</th>
              <th>Khoa</th>
              <th>Mã QR</th>
              <th class="text-right">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="8" class="text-center py-4">Đang tải dữ liệu sinh viên...</td>
            </tr>
            <tr v-else-if="students.length === 0">
              <td colspan="8" class="text-center py-4">Không tìm thấy sinh viên nào</td>
            </tr>
            <tr v-for="(st, idx) in students" :key="st.id">
              <td>{{ idx + 1 }}</td>
              <td><b>{{ st.ma_sinh_vien }}</b></td>
              <td>{{ st.ho_ten }}</td>
              <td>{{ st.email || '--' }}</td>
              <td><span class="badge badge-info">{{ st.lop }}</span></td>
              <td>{{ st.khoa || 'CNTT' }}</td>
              <td>
                <button @click="viewStudentQr(st)" class="btn btn-secondary btn-xs">
                  📱 Xem QR (90s)
                </button>
              </td>
              <td class="text-right">
                <button @click="openModal(st)" class="btn-action edit" title="Sửa">
                  ✏️
                </button>
                <button @click="deleteStudent(st)" class="btn-action delete" title="Xóa">
                  🗑️
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Create / Edit Student -->
    <div v-if="showModal" class="modal-overlay">
      <div class="modal-content">
        <div class="modal-header">
          <h3>{{ editingId ? 'Sửa thông tin sinh viên' : 'Thêm sinh viên mới' }}</h3>
          <button @click="showModal = false" class="btn-close">&times;</button>
        </div>

        <form @submit.prevent="saveStudent" class="modal-form">
          <div class="form-group">
            <label class="form-label">Mã sinh viên (*)</label>
            <input
              v-model="form.ma_sinh_vien"
              class="form-control"
              placeholder="VD: SV011"
              required
            />
          </div>

          <div class="form-group">
            <label class="form-label">Họ và tên (*)</label>
            <input
              v-model="form.ho_ten"
              class="form-control"
              placeholder="VD: Nguyễn Văn A"
              required
            />
          </div>

          <div class="form-group">
            <label class="form-label">Lớp (*)</label>
            <input
              v-model="form.lop"
              class="form-control"
              placeholder="VD: D21CNTT01"
              required
            />
          </div>

          <div class="form-group">
            <label class="form-label">Khoa</label>
            <input
              v-model="form.khoa"
              class="form-control"
              placeholder="Công nghệ Thông tin"
            />
          </div>

          <div class="form-group">
            <label class="form-label">Email</label>
            <input
              v-model="form.email"
              type="email"
              class="form-control"
              placeholder="email@example.com"
            />
          </div>

          <div v-if="formError" class="error-msg">{{ formError }}</div>

          <div class="modal-actions">
            <button type="button" @click="showModal = false" class="btn btn-secondary">Hủy</button>
            <button type="submit" class="btn btn-primary" :disabled="saving">
              {{ saving ? 'Đang lưu...' : 'Lưu thông tin' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal View Student QR Code (90s countdown) -->
    <div v-if="showQrModal" class="modal-overlay">
      <div class="modal-content qr-modal-card">
        <div class="modal-header">
          <h3>Mã QR Cá Nhân Sinh Viên</h3>
          <button @click="closeQrModal" class="btn-close">&times;</button>
        </div>

        <div class="qr-modal-body">
          <div class="qr-student-info">
            <h4>{{ activeStudent?.ho_ten }}</h4>
            <div class="qr-meta">
              <span>Mã SV: <b>{{ activeStudent?.ma_sinh_vien }}</b></span>
              <span>Lớp: <b>{{ activeStudent?.lop }}</b></span>
            </div>
          </div>

          <!-- QR Code Display Component -->
          <div class="qr-container-box">
            <qrcode-vue
              v-if="qrString"
              :value="qrString"
              :size="220"
              level="M"
              class="qr-canvas"
            />
            <div v-else class="qr-loading">Đang tạo token...</div>
          </div>

          <!-- Countdown Timer -->
          <div class="timer-box" :class="{ 'timer-expired': remainingSeconds <= 0 }">
            <span v-if="remainingSeconds > 0">
              ⏳ Mã có hiệu lực trong: <b>{{ remainingSeconds }}s</b>
            </span>
            <span v-else>
              ⚠️ Mã QR đã hết hạn! Vui lòng tạo mã mới.
            </span>
          </div>

          <div class="qr-actions">
            <button @click="refreshQr" class="btn btn-primary btn-block">
              🔄 Tạo mã QR mới
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import QrcodeVue from 'qrcode.vue'
import api from '../services/api'

const students = ref([])
const loading = ref(false)
const keyword = ref('')
const selectedLop = ref('')
const saving = ref(false)
const formError = ref('')

// Modal Form State
const showModal = ref(false)
const editingId = ref(null)
const form = ref({
  ma_sinh_vien: '',
  ho_ten: '',
  email: '',
  lop: '',
  khoa: 'Công nghệ Thông tin'
})

// QR Modal State
const showQrModal = ref(false)
const activeStudent = ref(null)
const qrString = ref('')
const remainingSeconds = ref(90)
let qrTimer = null

const classes = computed(() => {
  const set = new Set(students.value.map(s => s.lop).filter(Boolean))
  return Array.from(set)
})

const fetchStudents = async () => {
  loading.value = true
  try {
    const res = await api.get('/students', {
      params: { keyword: keyword.value, lop: selectedLop.value }
    })
    if (res.data.success) {
      students.value = res.data.data
    }
  } catch (err) {
    console.error('Lỗi tải sinh viên:', err)
  } finally {
    loading.value = false
  }
}

const openModal = (student = null) => {
  formError.value = ''
  if (student) {
    editingId.value = student.id
    form.value = { ...student }
  } else {
    editingId.value = null
    form.value = {
      ma_sinh_vien: '',
      ho_ten: '',
      email: '',
      lop: '',
      khoa: 'Công nghệ Thông tin'
    }
  }
  showModal.value = true
}

const saveStudent = async () => {
  saving.value = true
  formError.value = ''
  try {
    if (editingId.value) {
      await api.put(`/students/${editingId.value}`, form.value)
    } else {
      await api.post('/students', form.value)
    }
    showModal.value = false
    fetchStudents()
  } catch (err) {
    formError.value = err.response?.data?.message || 'Có lỗi xảy ra khi lưu'
  } finally {
    saving.value = false
  }
}

const deleteStudent = async (student) => {
  if (confirm(`Bạn có chắc chắn muốn xóa sinh viên ${student.ho_ten} (${student.ma_sinh_vien})?`)) {
    try {
      await api.delete(`/students/${student.id}`)
      fetchStudents()
    } catch (err) {
      alert('Không thể xóa sinh viên: ' + (err.response?.data?.message || err.message))
    }
  }
}

// Xem mã QR sinh viên với thời gian 90 giây
const viewStudentQr = async (student) => {
  activeStudent.value = student
  showQrModal.value = true
  await refreshQr()
}

const refreshQr = async () => {
  if (!activeStudent.value) return
  if (qrTimer) clearInterval(qrTimer)
  remainingSeconds.value = 90
  qrString.value = ''

  try {
    const res = await api.get(`/students/${activeStudent.value.id}/qr`)
    if (res.data.success) {
      qrString.value = res.data.data.qr_data
      remainingSeconds.value = res.data.data.expires_in_seconds || 90

      // Khởi động đếm ngược 90 giây
      qrTimer = setInterval(() => {
        if (remainingSeconds.value > 0) {
          remainingSeconds.value--
        } else {
          clearInterval(qrTimer)
        }
      }, 1000)
    }
  } catch (err) {
    console.error('Lỗi khi lấy mã QR:', err)
  }
}

const closeQrModal = () => {
  showQrModal.value = false
  if (qrTimer) clearInterval(qrTimer)
}

onMounted(() => {
  fetchStudents()
})

onUnmounted(() => {
  if (qrTimer) clearInterval(qrTimer)
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

.filters-row {
  display: flex;
  gap: 1rem;
}

.search-box {
  flex: 1;
}

.filter-lop {
  width: 220px;
}

.mt-4 {
  margin-top: 1.25rem;
}

.text-right {
  text-align: right;
}

.btn-action {
  background: none;
  border: none;
  font-size: 1.1rem;
  cursor: pointer;
  padding: 0.35rem;
  border-radius: var(--radius-sm);
  transition: 0.2s;
}

.btn-action:hover {
  background: var(--gray-100);
}

/* Modal Styling */
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid var(--gray-200);
  padding-bottom: 0.75rem;
  margin-bottom: 1.25rem;
}

.modal-header h3 {
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--gray-900);
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: var(--gray-400);
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 1.5rem;
}

.error-msg {
  color: var(--danger);
  font-size: 0.82rem;
  margin-top: 0.5rem;
}

/* QR Modal */
.qr-modal-card {
  max-width: 400px;
  text-align: center;
}

.qr-student-info h4 {
  font-size: 1.2rem;
  font-weight: 700;
  color: var(--gray-900);
}

.qr-meta {
  display: flex;
  justify-content: center;
  gap: 1.25rem;
  font-size: 0.85rem;
  color: var(--gray-600);
  margin-top: 0.25rem;
}

.qr-container-box {
  background: #ffffff;
  padding: 1rem;
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--gray-200);
  margin: 1.25rem auto;
  display: inline-block;
}

.qr-canvas {
  display: block;
}

.timer-box {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--primary);
  background: var(--primary-light);
  padding: 0.5rem 1rem;
  border-radius: 999px;
  display: inline-block;
  margin-bottom: 1.25rem;
}

.timer-expired {
  color: var(--danger);
  background: var(--danger-light);
}

.qr-actions .btn-block {
  width: 100%;
}

.btn-xs {
  padding: 0.3rem 0.6rem;
  font-size: 0.78rem;
}
</style>
