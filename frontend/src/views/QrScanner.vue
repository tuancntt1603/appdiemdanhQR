<template>
  <div class="scanner-page">
    <div class="scanner-layout">
      <!-- Left: Camera Viewport -->
      <div class="card scanner-box">
        <div class="scanner-header">
          <div class="title-with-status">
            <h2>📷 ĐIỂM DANH XƯỞNG THỰC HÀNH</h2>
            <div class="workshop-selector">
              <label>Xưởng:</label>
              <select v-model="selectedWorkshop" class="form-control-sm">
                <option v-for="ws in workshops" :key="ws.id" :value="ws.id">
                  {{ ws.ten_xuong }} ({{ ws.dia_diem || 'Phòng thực hành' }})
                </option>
              </select>
            </div>
          </div>
          <p class="scanner-instruction">Đưa mã QR cá nhân trên điện thoại của sinh viên vào khung camera bên dưới</p>
        </div>

        <!-- Camera Area -->
        <div class="camera-wrapper">
          <div id="qr-reader" class="qr-viewport"></div>
          
          <div v-if="cameraError" class="camera-fallback">
            <span class="error-icon">⚠️</span>
            <p>{{ cameraError }}</p>
            <button @click="startScanner" class="btn btn-secondary btn-sm">Thử lại Camera</button>
          </div>

          <div class="camera-status-bar" :class="statusClass">
            <span class="status-indicator"></span>
            <span>{{ statusMessage }}</span>
          </div>
        </div>

        <!-- Controls & Manual Simulation -->
        <div class="scanner-controls">
          <button v-if="!isScanning" @click="startScanner" class="btn btn-success btn-sm">
            ▶ Bật Camera
          </button>
          <button v-else @click="stopScanner" class="btn btn-danger btn-sm">
            ⏹ Tắt Camera
          </button>
          
          <div class="simulate-box">
            <span class="simulate-label">Test nhanh bằng cách dán chuỗi QR / token:</span>
            <div class="input-group">
              <input
                v-model="manualQrText"
                placeholder='Dán chuỗi {"student_id":1,...} hoặc bấm test sinh viên'
                class="form-control form-control-sm"
              />
              <button @click="processScan(manualQrText)" class="btn btn-primary btn-sm" :disabled="!manualQrText">
                Quét mã này
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Scan Result & Student Card -->
      <div class="scanner-sidebar">
        <!-- Live Result Card -->
        <div class="card result-card" :class="resultCardClass">
          <div class="result-header">
            <span class="result-badge">{{ lastScanResult ? lastScanResult.actionText : 'CHỜ QUÉT' }}</span>
            <span class="result-time">{{ lastScanResult ? lastScanResult.time : '--:--:--' }}</span>
          </div>

          <div v-if="lastScanResult && lastScanResult.student" class="student-profile">
            <div class="student-avatar">
              {{ lastScanResult.student.ho_ten.charAt(0) }}
            </div>
            <div class="student-details">
              <h3 class="student-name">{{ lastScanResult.student.ho_ten }}</h3>
              <div class="student-meta-item">
                <span class="meta-label">Mã SV:</span>
                <span class="meta-value highlight">{{ lastScanResult.student.ma_sinh_vien }}</span>
              </div>
              <div class="student-meta-item">
                <span class="meta-label">Lớp:</span>
                <span class="meta-value">{{ lastScanResult.student.lop }}</span>
              </div>
              <div class="student-meta-item">
                <span class="meta-label">Khoa:</span>
                <span class="meta-value">{{ lastScanResult.student.khoa || 'CNTT' }}</span>
              </div>
              <div class="student-meta-item">
                <span class="meta-label">Thông báo:</span>
                <span class="meta-value notify">{{ lastScanResult.message }}</span>
              </div>
            </div>
          </div>

          <div v-else class="empty-result">
            <span class="empty-icon">🎯</span>
            <h4>Sẵn sàng quét</h4>
            <p>Hệ thống tự động nhận diện sinh viên, ghi nhận giờ Vào hoặc Giờ Ra theo thời gian thực.</p>
          </div>
        </div>

        <!-- Quick Test Students List -->
        <div class="card quick-test-card">
          <div class="card-header-sm">
            <h4>⚡ Danh sách Test Nhanh (Sinh viên có sẵn)</h4>
          </div>
          <p class="helper-text">Bấm nút "Quét thử" để tạo token 90s và điểm danh giả lập ngay lập tức:</p>
          
          <div class="demo-student-list">
            <div v-for="st in demoStudents" :key="st.id" class="demo-student-item">
              <div>
                <b>{{ st.ma_sinh_vien }}</b> - {{ st.ho_ten }}
                <div class="sub-lop">{{ st.lop }}</div>
              </div>
              <button @click="testStudentCheckIn(st)" class="btn btn-secondary btn-xs" :disabled="testingStudentId === st.id">
                {{ testingStudentId === st.id ? 'Đang xử lý...' : 'Quét thử' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Html5Qrcode } from 'html5-qrcode'
import api from '../services/api'

const isScanning = ref(false)
const statusMessage = ref('Đang chờ quét mã QR...')
const statusClass = ref('status-idle')
const cameraError = ref('')
const selectedWorkshop = ref(1)
const workshops = ref([])
const manualQrText = ref('')
const lastScanResult = ref(null)
const resultCardClass = ref('')
const demoStudents = ref([])
const testingStudentId = ref(null)

let html5QrCode = null
let lastScannedToken = ''
let lastScanTimestamp = 0

// Khởi tạo và fetch danh sách
const initData = async () => {
  try {
    const [wsRes, stRes] = await Promise.all([
      api.get('/workshops'),
      api.get('/students')
    ])
    if (wsRes.data.success && wsRes.data.data.length > 0) {
      workshops.value = wsRes.data.data
      selectedWorkshop.value = wsRes.data.data[0].id
    }
    if (stRes.data.success) {
      demoStudents.value = stRes.data.data.slice(0, 6)
    }
  } catch (err) {
    console.error('Lỗi tải danh mục xưởng/sinh viên:', err)
  }
}

// Bật Camera Webcam quét QR
const startScanner = async () => {
  cameraError.value = ''
  statusMessage.value = 'Đang khởi động webcam...'
  statusClass.value = 'status-idle'

  try {
    if (!html5QrCode) {
      html5QrCode = new Html5Qrcode('qr-reader')
    }

    const config = {
      fps: 10,
      qrbox: { width: 260, height: 260 },
      aspectRatio: 1.0
    }

    await html5QrCode.start(
      { facingMode: 'environment' },
      config,
      onScanSuccess,
      onScanError
    )

    isScanning.value = true
    statusMessage.value = 'Đang chờ quét... Đưa mã QR vào khung ngắm'
    statusClass.value = 'status-waiting'
  } catch (err) {
    console.error('Không thể mở camera:', err)
    cameraError.value = 'Không thể kết nối với webcam máy tính. Bạn có thể sử dụng khung test nhanh bên dưới.'
    isScanning.value = false
    statusMessage.value = 'Webcam chưa sẵn sàng'
    statusClass.value = 'status-error'
  }
}

// Tắt Camera
const stopScanner = async () => {
  if (html5QrCode && isScanning.value) {
    try {
      await html5QrCode.stop()
      isScanning.value = false
      statusMessage.value = 'Camera đã tắt'
      statusClass.value = 'status-idle'
    } catch (err) {
      console.error('Lỗi khi tắt camera:', err)
    }
  }
}

// Callback khi camera bắt được QR Code
const onScanSuccess = (decodedText) => {
  const now = Date.now()
  // Chống quét liên tục cùng 1 mã trong vòng 4 giây
  if (decodedText === lastScannedToken && now - lastScanTimestamp < 4000) {
    return
  }

  lastScannedToken = decodedText
  lastScanTimestamp = now
  processScan(decodedText)
}

const onScanError = (errorMessage) => {
  // Ignored - frame scanning event
}

// Gửi dữ liệu QR lên Backend Laravel để xác thực & Điểm danh
const processScan = async (qrDataText) => {
  statusMessage.value = 'Đang xử lý dữ liệu điểm danh...'
  statusClass.value = 'status-processing'

  try {
    const res = await api.post('/attendance/scan', {
      qr_data: qrDataText,
      workshop_id: selectedWorkshop.value
    })

    if (res.data.success) {
      const resultData = res.data.data
      const isCheckIn = res.data.type === 'check_in'

      lastScanResult.value = {
        actionText: isCheckIn ? 'ĐIỂM DANH VÀO' : 'ĐIỂM DANH RA',
        time: resultData.time,
        student: resultData.student,
        message: res.data.message
      }

      resultCardClass.value = isCheckIn ? 'result-success-in' : 'result-success-out'
      statusMessage.value = `✅ ${res.data.message} (${resultData.student.ho_ten})`
      statusClass.value = 'status-success'

      // Phát âm thanh beep ngắn thông báo thành công
      playBeep(isCheckIn ? 800 : 600)
    } else {
      // Trường hợp đã hoàn thành hoặc lỗi từ backend
      statusMessage.value = `⚠️ ${res.data.message}`
      statusClass.value = 'status-warning'
      if (res.data.data?.student) {
        lastScanResult.value = {
          actionText: 'ĐÃ HOÀN THÀNH',
          time: new Date().toLocaleTimeString('vi-VN'),
          student: res.data.data.student,
          message: res.data.message
        }
        resultCardClass.value = 'result-warning'
      }
    }
  } catch (err) {
    const errMessage = err.response?.data?.message || 'Mã QR không hợp lệ hoặc đã hết hạn!'
    statusMessage.value = `❌ ${errMessage}`
    statusClass.value = 'status-error'
    resultCardClass.value = 'result-error'
    lastScanResult.value = {
      actionText: 'LỖI ĐIỂM DANH',
      time: new Date().toLocaleTimeString('vi-VN'),
      student: null,
      message: errMessage
    }
  }

  // Tự động chuyển camera về trạng thái sẵn sàng sau 2.5 giây
  setTimeout(() => {
    if (isScanning.value) {
      statusMessage.value = 'Đang chờ quét... Đưa mã QR vào khung ngắm'
      statusClass.value = 'status-waiting'
    }
  }, 2500)
}

// Tạo QR giả lập và test điểm danh tức thì cho 1 sinh viên trong danh sách demo
const testStudentCheckIn = async (student) => {
  testingStudentId.value = student.id
  try {
    // 1. Lấy QR Token mới của sinh viên
    const qrRes = await api.post('/qr/generate', { student_id: student.id })
    if (qrRes.data.success) {
      const qrData = qrRes.data.data.qr_data
      manualQrText.value = qrData
      // 2. Điểm danh
      await processScan(qrData)
    }
  } catch (err) {
    console.error('Lỗi khi quét thử:', err)
  } finally {
    testingStudentId.value = null
  }
}

// Âm thanh Beep Web Audio API
const playBeep = (freq) => {
  try {
    const AudioContext = window.AudioContext || window.webkitAudioContext
    if (!AudioContext) return
    const ctx = new AudioContext()
    const osc = ctx.createOscillator()
    const gain = ctx.createGain()
    osc.type = 'sine'
    osc.frequency.setValueAtTime(freq, ctx.currentTime)
    gain.gain.setValueAtTime(0.15, ctx.currentTime)
    osc.connect(gain)
    gain.connect(ctx.destination)
    osc.start()
    osc.stop(ctx.currentTime + 0.15)
  } catch (e) {}
}

onMounted(() => {
  initData()
  startScanner()
})

onUnmounted(() => {
  stopScanner()
})
</script>

<style scoped>
.scanner-page {
  display: flex;
  flex-direction: column;
}

.scanner-layout {
  display: grid;
  grid-template-columns: 1.4fr 1fr;
  gap: 1.5rem;
}

@media (max-width: 960px) {
  .scanner-layout {
    grid-template-columns: 1fr;
  }
}

/* Scanner Card */
.scanner-box {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.title-with-status {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.title-with-status h2 {
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--gray-900);
}

.workshop-selector {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  font-weight: 600;
}

.form-control-sm {
  padding: 0.35rem 0.6rem;
  font-size: 0.82rem;
  border-radius: var(--radius-sm);
  border: 1px solid var(--gray-300);
  background: white;
}

.scanner-instruction {
  font-size: 0.82rem;
  color: var(--gray-500);
  margin-top: 0.25rem;
}

.camera-wrapper {
  position: relative;
  width: 100%;
  max-width: 440px;
  margin: 0 auto;
  border-radius: var(--radius-md);
  overflow: hidden;
  background: #0f172a;
  border: 3px solid #334155;
  box-shadow: var(--shadow-md);
  min-height: 320px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.qr-viewport {
  width: 100%;
}

.camera-fallback {
  position: absolute;
  inset: 0;
  background: #1e293b;
  color: #e2e8f0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  text-align: center;
  gap: 0.75rem;
}

.error-icon {
  font-size: 2.2rem;
}

.camera-status-bar {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: rgba(15, 23, 42, 0.9);
  color: white;
  padding: 0.65rem 1rem;
  font-size: 0.82rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  z-index: 10;
}

.status-indicator {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #94a3b8;
}

.status-waiting .status-indicator { background: #3b82f6; box-shadow: 0 0 6px #3b82f6; }
.status-success .status-indicator { background: #10b981; box-shadow: 0 0 6px #10b981; }
.status-warning .status-indicator { background: #f59e0b; box-shadow: 0 0 6px #f59e0b; }
.status-error .status-indicator { background: #ef4444; box-shadow: 0 0 6px #ef4444; }

.scanner-controls {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
  margin-top: 0.5rem;
}

.simulate-box {
  background: var(--gray-50);
  border: 1px dashed var(--gray-300);
  padding: 0.75rem;
  border-radius: var(--radius-sm);
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.simulate-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--gray-600);
}

.input-group {
  display: flex;
  gap: 0.5rem;
}

/* Sidebar Result */
.scanner-sidebar {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.result-card {
  transition: all 0.3s ease;
  border-left: 6px solid var(--gray-300);
}

.result-success-in {
  border-left-color: #2563eb;
  background: linear-gradient(180deg, #eff6ff 0%, #ffffff 100%);
}

.result-success-out {
  border-left-color: #10b981;
  background: linear-gradient(180deg, #ecfdf5 0%, #ffffff 100%);
}

.result-warning {
  border-left-color: #f59e0b;
  background: #fffbeb;
}

.result-error {
  border-left-color: #ef4444;
  background: #fef2f2;
}

.result-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid var(--gray-200);
  padding-bottom: 0.75rem;
  margin-bottom: 1rem;
}

.result-badge {
  font-weight: 700;
  font-size: 0.85rem;
  letter-spacing: 0.05em;
}

.result-time {
  font-family: monospace;
  font-weight: 600;
  color: var(--gray-600);
  font-size: 0.85rem;
}

.student-profile {
  display: flex;
  gap: 1.25rem;
  align-items: flex-start;
}

.student-avatar {
  width: 56px;
  height: 56px;
  border-radius: var(--radius-md);
  background: var(--primary);
  color: white;
  font-size: 1.5rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.student-details {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.student-name {
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--gray-900);
}

.student-meta-item {
  display: flex;
  justify-content: space-between;
  font-size: 0.82rem;
  border-bottom: 1px dashed var(--gray-200);
  padding-bottom: 0.2rem;
}

.meta-label {
  color: var(--gray-500);
}

.meta-value {
  font-weight: 600;
  color: var(--gray-800);
}

.meta-value.highlight {
  color: var(--primary);
  font-size: 0.95rem;
}

.meta-value.notify {
  color: #059669;
}

.empty-result {
  text-align: center;
  padding: 2rem 1rem;
  color: var(--gray-500);
}

.empty-icon {
  font-size: 2.5rem;
  display: block;
  margin-bottom: 0.5rem;
}

.empty-result h4 {
  font-weight: 700;
  color: var(--gray-700);
  margin-bottom: 0.25rem;
}

.empty-result p {
  font-size: 0.8rem;
}

/* Quick test students list */
.quick-test-card {
  padding: 1.25rem;
}

.card-header-sm h4 {
  font-size: 0.92rem;
  font-weight: 700;
  color: var(--gray-800);
}

.helper-text {
  font-size: 0.76rem;
  color: var(--gray-500);
  margin: 0.35rem 0 0.75rem;
}

.demo-student-list {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.demo-student-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.55rem 0.75rem;
  background: var(--gray-50);
  border: 1px solid var(--gray-200);
  border-radius: var(--radius-sm);
  font-size: 0.8rem;
}

.sub-lop {
  font-size: 0.7rem;
  color: var(--gray-500);
}

.btn-xs {
  padding: 0.25rem 0.6rem;
  font-size: 0.75rem;
}
</style>
