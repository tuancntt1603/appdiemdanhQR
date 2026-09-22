<template>
  <div v-if="shouldShowComponent" class="pwa-install-wrapper" :class="{ 'compact-mode': compact }">
    <div class="pwa-card">
      <div class="pwa-card-header">
        <div class="pwa-icon-box">
          <span class="pwa-icon">📱</span>
        </div>
        <div class="pwa-info">
          <h4 class="pwa-title">Cài đặt ứng dụng Điểm danh QR</h4>
          <p class="pwa-desc">
            Cài ứng dụng lên màn hình điện thoại để truy cập nhanh hệ thống điểm danh.
          </p>
        </div>
      </div>

      <!-- Action Area -->
      <div class="pwa-actions">
        <!-- Nút Cài đặt cho trình duyệt hỗ trợ BeforeInstallPrompt (Chrome Android, Edge, Desktop Chrome) -->
        <button
          v-if="canInstall"
          @click="installPwa"
          class="btn btn-primary btn-install"
          :disabled="installing"
        >
          <span v-if="!installing">➕ Cài đặt ứng dụng</span>
          <span v-else>Đang chuẩn bị...</span>
        </button>

        <!-- Hướng dẫn dành riêng cho iOS Safari -->
        <button
          v-else-if="isIos"
          @click="showIosModal = true"
          class="btn btn-primary btn-install"
        >
          <span>➕ Hướng dẫn cài trên iPhone / iPad</span>
        </button>

        <!-- Trình duyệt desktop hoặc chưa kích hoạt prompt: nút chia sẻ / hướng dẫn chung -->
        <button
          v-else
          @click="showGeneralGuide = !showGeneralGuide"
          class="btn btn-secondary btn-install"
        >
          <span>ℹ️ Cách cài đặt lên màn hình</span>
        </button>
      </div>

      <!-- Thông báo khi cài đặt thành công -->
      <div v-if="installedSuccess" class="install-success-msg">
        🎉 Đã cài đặt ứng dụng thành công lên màn hình chính!
      </div>

      <!-- Hướng dẫn cho thiết bị iOS Safari -->
      <div v-if="showIosModal" class="ios-instruction-box">
        <div class="ios-instruction-header">
          <strong>Cách cài trên iPhone / iPad (Safari):</strong>
          <button @click="showIosModal = false" class="btn-close" aria-label="Đóng">&times;</button>
        </div>
        <ol class="ios-steps">
          <li>Mở trang web này bằng trình duyệt <b>Safari</b>.</li>
          <li>Nhấn vào biểu tượng <b>Chia sẻ</b> (biểu tượng hình vuông có mũi tên trỏ lên <span class="ios-share-icon">⎘</span>) ở thanh công cụ dưới màn hình.</li>
          <li>Cuộn xuống và chọn <b>"Thêm vào MH chính"</b> (Add to Home Screen ➕).</li>
          <li>Nhấn <b>"Thêm"</b> (Add) ở góc trên bên phải để hoàn tất.</li>
        </ol>
      </div>

      <!-- Hướng dẫn cho các trình duyệt khác -->
      <div v-if="showGeneralGuide && !isIos" class="general-instruction-box">
        <p>
          Trên trình duyệt của bạn: Bấm vào <b>menu 3 chấm (⋮)</b> của trình duyệt và chọn <b>"Cài đặt ứng dụng"</b> (Install app) hoặc <b>"Thêm vào màn hình chính"</b>.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  compact: {
    type: Boolean,
    default: false
  }
})

const deferredPrompt = ref(null)
const canInstall = ref(false)
const isInstalled = ref(false)
const isIos = ref(false)
const showIosModal = ref(false)
const showGeneralGuide = ref(false)
const installing = ref(false)
const installedSuccess = ref(false)

// Kiểm tra xem ứng dụng đã chạy ở chế độ standalone (đã cài) hay chưa
const checkIsStandalone = () => {
  const isStandaloneMode = window.matchMedia && window.matchMedia('(display-mode: standalone)').matches
  const isIosStandalone = window.navigator.standalone === true
  return Boolean(isStandaloneMode || isIosStandalone)
}

// Kiểm tra có phải thiết bị iOS Safari không
const checkIsIos = () => {
  const ua = window.navigator.userAgent.toLowerCase()
  return /iphone|ipad|ipod/.test(ua) && !window.MSStream
}

const shouldShowComponent = computed(() => {
  // Nếu đã được cài đặt và đang mở dưới dạng app PWA standalone thì ẩn hoàn toàn
  if (isInstalled.value) return false
  return true
})

// Xử lý sự kiện beforeinstallprompt
const onBeforeInstallPrompt = (e) => {
  // Ngăn chặn prompt mặc định của Chrome
  e.preventDefault()
  deferredPrompt.value = e
  canInstall.value = true
}

// Xử lý sự kiện appinstalled
const onAppInstalled = () => {
  canInstall.value = false
  deferredPrompt.value = null
  installedSuccess.value = true
  setTimeout(() => {
    isInstalled.value = true
  }, 3500)
}

// Kích hoạt lời nhắc cài đặt
const installPwa = async () => {
  if (!deferredPrompt.value) return
  installing.value = true
  try {
    deferredPrompt.value.prompt()
    const { outcome } = await deferredPrompt.value.userChoice
    if (outcome === 'accepted') {
      canInstall.value = false
      deferredPrompt.value = null
    }
  } catch (err) {
    console.warn('Lỗi khi kích hoạt lời nhắc cài PWA:', err)
  } finally {
    installing.value = false
  }
}

onMounted(() => {
  isInstalled.value = checkIsStandalone()
  isIos.value = checkIsIos()

  // Bắt sự kiện PWA
  window.addEventListener('beforeinstallprompt', onBeforeInstallPrompt)
  window.addEventListener('appinstalled', onAppInstalled)
})

onUnmounted(() => {
  window.removeEventListener('beforeinstallprompt', onBeforeInstallPrompt)
  window.removeEventListener('appinstalled', onAppInstalled)
})
</script>

<style scoped>
.pwa-install-wrapper {
  margin: 1.25rem 0;
  width: 100%;
}

.pwa-card {
  background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 50%, #eff6ff 100%);
  border: 1px solid #bfdbfe;
  border-radius: var(--radius-md);
  padding: 1.25rem;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.08);
  transition: all 0.2s ease;
}

.pwa-card:hover {
  box-shadow: 0 6px 16px rgba(37, 99, 235, 0.12);
}

.pwa-card-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.pwa-icon-box {
  width: 44px;
  height: 44px;
  min-width: 44px;
  background: #2563eb;
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 6px rgba(37, 99, 235, 0.3);
}

.pwa-icon {
  font-size: 1.5rem;
}

.pwa-info {
  flex: 1;
  text-align: left;
}

.pwa-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 0.2rem;
}

.pwa-desc {
  font-size: 0.8rem;
  color: #64748b;
  line-height: 1.35;
}

.pwa-actions {
  display: flex;
  justify-content: flex-end;
}

.btn-install {
  width: 100%;
  padding: 0.75rem 1.25rem;
  font-size: 0.9rem;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-install:active {
  transform: scale(0.98);
}

.install-success-msg {
  margin-top: 0.75rem;
  padding: 0.6rem 0.8rem;
  background: #ecfdf5;
  color: #065f46;
  border-radius: var(--radius-sm);
  font-size: 0.85rem;
  font-weight: 600;
  text-align: center;
  border: 1px solid #a7f3d0;
}

.ios-instruction-box {
  margin-top: 1rem;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: var(--radius-sm);
  padding: 0.85rem 1rem;
  text-align: left;
}

.ios-instruction-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.5rem;
  color: #1e293b;
  font-size: 0.85rem;
}

.btn-close {
  background: transparent;
  border: none;
  font-size: 1.25rem;
  cursor: pointer;
  color: #94a3b8;
  line-height: 1;
}

.btn-close:hover {
  color: #1e293b;
}

.ios-steps {
  font-size: 0.8rem;
  color: #475569;
  padding-left: 1.25rem;
  line-height: 1.6;
}

.ios-steps li {
  margin-bottom: 0.25rem;
}

.ios-share-icon {
  font-weight: bold;
  font-size: 0.95rem;
}

.general-instruction-box {
  margin-top: 0.75rem;
  padding: 0.7rem 0.85rem;
  background: #f8fafc;
  border: 1px dashed #cbd5e1;
  border-radius: var(--radius-sm);
  font-size: 0.8rem;
  color: #475569;
  text-align: left;
  line-height: 1.4;
}

/* Compact Mode */
.compact-mode .pwa-card {
  padding: 0.85rem 1rem;
}

.compact-mode .pwa-card-header {
  margin-bottom: 0.65rem;
}

.compact-mode .pwa-icon-box {
  width: 36px;
  height: 36px;
  min-width: 36px;
}

.compact-mode .pwa-title {
  font-size: 0.88rem;
}

.compact-mode .pwa-desc {
  font-size: 0.75rem;
}

.compact-mode .btn-install {
  padding: 0.5rem 1rem;
  font-size: 0.82rem;
}

@media (max-width: 640px) {
  .pwa-card-header {
    flex-direction: row;
  }
}
</style>
