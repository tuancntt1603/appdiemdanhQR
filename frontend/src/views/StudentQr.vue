<template>
  <div class="student-qr-page">
    <div class="qr-container">
      <div class="card qr-box">
        <div class="qr-header">
          <span class="badge badge-info">CỔNG SINH VIÊN</span>
          <h2>📱 MÃ QR ĐIỂM DANH CÁ NHÂN</h2>
          <p>Xuất trình mã QR này trước webcam tại cửa xưởng để ghi nhận Giờ Vào / Giờ Ra</p>
        </div>

        <!-- Student Selector / Quick Login -->
        <div v-if="userRole !== 'sinh_vien'" class="selector-card">
          <label class="form-label">Chọn sinh viên để xem mã QR:</label>
          <div class="select-group">
            <select v-model="selectedStudentId" @change="onStudentChange" class="form-control">
              <option v-for="st in students" :key="st.id" :value="st.id">
                {{ st.ma_sinh_vien }} - {{ st.ho_ten }} ({{ st.lop }})
              </option>
            </select>
          </div>
        </div>

        <!-- QR Display -->
        <div v-if="currentStudent" class="qr-presentation">
          <div class="student-badge-info">
            <h3 class="student-name">{{ currentStudent.ho_ten }}</h3>
            <div class="meta-row">
              <span>Mã SV: <b>{{ currentStudent.ma_sinh_vien }}</b></span>
              <span>•</span>
              <span>Lớp: <b>{{ currentStudent.lop }}</b></span>
              <span>•</span>
              <span>Khoa: <b>{{ currentStudent.khoa || 'CNTT' }}</b></span>
            </div>
          </div>

          <!-- QR Code Canvas -->
          <div class="qr-canvas-wrapper" :class="{ 'expired-dim': remainingSeconds <= 0 }">
            <qrcode-vue
              v-if="qrDataString"
              :value="qrDataString"
              :size="260"
              level="M"
            />
            <div v-if="remainingSeconds <= 0" class="expired-overlay">
              <span>⚠️ ĐÃ HẾT HẠN</span>
            </div>
          </div>

          <!-- Countdown Progress & Time -->
          <div class="timer-section">
            <div class="timer-tag" :class="{ expired: remainingSeconds <= 0 }">
              <span v-if="remainingSeconds > 0">
                ⏳ Mã có hiệu lực trong: <b>{{ remainingSeconds }} giây</b>
              </span>
              <span v-else>
                ❌ Token đã hết hạn! Vui lòng bấm nút tạo mã mới.
              </span>
            </div>

            <div class="progress-track">
              <div
                class="progress-fill"
                :style="{ width: (remainingSeconds / 90) * 100 + '%' }"
                :class="{ warning: remainingSeconds <= 20 }"
              ></div>
            </div>
          </div>

          <!-- Actions -->
          <div class="qr-controls">
            <button @click="generateQrCode" class="btn btn-primary btn-lg btn-block" :disabled="loading">
              <span v-if="loading">Đang khởi tạo mã...</span>
              <span v-else>🔄 Tạo mã QR mới (Gia hạn 90s)</span>
            </button>
            <p class="security-note">
              🔒 Mã được bảo mật với token ngẫu nhiên, tự động hủy sau 90 giây để chống chụp ảnh điểm danh hộ.
            </p>
          </div>
        </div>

        <div v-else class="text-center py-4">
          <p>Đang tải thông tin sinh viên...</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import QrcodeVue from 'qrcode.vue'
import api from '../services/api'

const students = ref([])
const selectedStudentId = ref(null)
const currentStudent = ref(null)
const qrDataString = ref('')
const remainingSeconds = ref(90)
const loading = ref(false)
let countdownTimer = null

const userRole = ref(localStorage.getItem('user_role') || '')
const userName = ref(localStorage.getItem('user_name') || '')

const fetchStudents = async () => {
  try {
    const res = await api.get('/students')
    if (res.data.success && res.data.data.length > 0) {
      students.value = res.data.data

      // Nếu người dùng đăng nhập là sinh viên (name là Mã SV hoặc Email)
      if (userRole.value === 'sinh_vien') {
        const found = students.value.find(s => s.ma_sinh_vien === userName.value || s.email === userName.value)
        if (found) {
          selectedStudentId.value = found.id
          currentStudent.value = found
          await generateQrCode()
          return
        }
      }

      selectedStudentId.value = students.value[0].id
      currentStudent.value = students.value[0]
      await generateQrCode()
    }
  } catch (err) {
    console.error('Lỗi tải danh sách sinh viên:', err)
  }
}

const onStudentChange = () => {
  currentStudent.value = students.value.find(s => s.id === selectedStudentId.value)
  generateQrCode()
}

const generateQrCode = async () => {
  if (!currentStudent.value) return
  if (countdownTimer) clearInterval(countdownTimer)
  loading.value = true

  try {
    const res = await api.post('/qr/generate', {
      student_id: currentStudent.value.id
    })

    if (res.data.success) {
      qrDataString.value = res.data.data.qr_data
      remainingSeconds.value = res.data.data.expires_in_seconds || 90

      // Bắt đầu đếm ngược 90s
      countdownTimer = setInterval(() => {
        if (remainingSeconds.value > 0) {
          remainingSeconds.value--
        } else {
          clearInterval(countdownTimer)
        }
      }, 1000)
    }
  } catch (err) {
    console.error('Lỗi tạo mã QR:', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchStudents()
})

onUnmounted(() => {
  if (countdownTimer) clearInterval(countdownTimer)
})
</script>

<style scoped>
.student-qr-page {
  min-height: calc(100vh - 64px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}

.qr-container {
  width: 100%;
  max-width: 520px;
}

.qr-box {
  text-align: center;
  padding: 2.25rem;
}

.qr-header {
  margin-bottom: 1.5rem;
}

.qr-header h2 {
  font-size: 1.25rem;
  font-weight: 800;
  color: var(--gray-900);
  margin: 0.5rem 0 0.25rem;
}

.qr-header p {
  font-size: 0.82rem;
  color: var(--gray-500);
}

.selector-card {
  text-align: left;
  background: var(--gray-50);
  border: 1px solid var(--gray-200);
  border-radius: var(--radius-sm);
  padding: 0.85rem 1rem;
  margin-bottom: 1.5rem;
}

.student-badge-info {
  margin-bottom: 1.25rem;
}

.student-name {
  font-size: 1.35rem;
  font-weight: 700;
  color: var(--primary);
}

.meta-row {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.82rem;
  color: var(--gray-600);
  margin-top: 0.25rem;
}

.qr-canvas-wrapper {
  background: white;
  padding: 1.25rem;
  border-radius: var(--radius-md);
  border: 2px dashed var(--gray-300);
  display: inline-block;
  position: relative;
  box-shadow: var(--shadow-sm);
  transition: all 0.3s ease;
}

.expired-dim {
  filter: blur(2px) grayscale(80%);
  opacity: 0.5;
}

.expired-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(239, 68, 68, 0.2);
  color: #dc2626;
  font-weight: 800;
  font-size: 1.2rem;
}

.timer-section {
  margin: 1.5rem 0 1.25rem;
}

.timer-tag {
  display: inline-block;
  padding: 0.4rem 1rem;
  background: #eff6ff;
  color: #1d4ed8;
  font-size: 0.9rem;
  font-weight: 600;
  border-radius: 999px;
  margin-bottom: 0.6rem;
}

.timer-tag.expired {
  background: #fef2f2;
  color: #dc2626;
}

.progress-track {
  height: 6px;
  background: var(--gray-200);
  border-radius: 999px;
  overflow: hidden;
  max-width: 320px;
  margin: 0 auto;
}

.progress-fill {
  height: 100%;
  background: var(--primary);
  border-radius: 999px;
  transition: width 1s linear;
}

.progress-fill.warning {
  background: var(--danger);
}

.qr-controls {
  margin-top: 1.5rem;
}

.btn-lg {
  padding: 0.85rem 1.5rem;
  font-size: 1rem;
}

.btn-block {
  width: 100%;
}

.security-note {
  font-size: 0.75rem;
  color: var(--gray-500);
  margin-top: 0.75rem;
  line-height: 1.4;
}
</style>
