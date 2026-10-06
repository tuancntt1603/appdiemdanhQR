<template>
  <div class="login-wrapper">
    <!-- Ambient Background Lighting Orbs -->
    <div class="ambient-orb orb-1"></div>
    <div class="ambient-orb orb-2"></div>
    <div class="ambient-orb orb-3"></div>

    <div class="login-card-glass">
      <!-- Header -->
      <div class="login-header">
        <div class="brand-badge">
          <span class="live-dot"></span>
          <span>HỆ THỐNG ĐIỂM DANH XƯỞNG THỰC HÀNH</span>
        </div>
        <div class="logo-box-glow">
          <span class="logo-emoji">🏭</span>
        </div>
        <h1 class="login-title">Cổng Đăng Nhập</h1>
        <p class="login-subtitle">Đăng nhập tài khoản Sinh viên hoặc Giảng viên quản trị</p>
      </div>

      <!-- Error Alert -->
      <transition name="fade">
        <div v-if="errorMessage" class="error-banner">
          <span class="error-icon">⚠️</span>
          <span class="error-text">{{ errorMessage }}</span>
        </div>
      </transition>

      <!-- Form -->
      <form @submit.prevent="handleLogin" class="login-form">
        <div class="form-group-custom">
          <label class="form-label-custom">
            <span>Tài khoản / Mã SV / Email</span>
          </label>
          <div class="input-wrapper">
            <span class="input-icon">👤</span>
            <input
              v-model="usernameOrEmail"
              type="text"
              class="input-custom"
              placeholder="1671020001 hoặc giangvien01"
              required
              autocomplete="username"
            />
          </div>
        </div>

        <div class="form-group-custom">
          <div class="label-row">
            <label class="form-label-custom">Mật khẩu</label>
            <span class="default-pwd-hint">Mặc định: 12345678</span>
          </div>
          <div class="input-wrapper">
            <span class="input-icon">🔒</span>
            <input
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              class="input-custom"
              placeholder="••••••••"
              required
              autocomplete="current-password"
            />
            <button
              type="button"
              class="btn-toggle-eye"
              @click="showPassword = !showPassword"
              title="Hiện / Ẩn mật khẩu"
            >
              {{ showPassword ? '👁️' : '👁️‍🗨️' }}
            </button>
          </div>
        </div>

        <button type="submit" class="btn-login-submit" :disabled="loading">
          <span v-if="loading" class="spinner-small"></span>
          <span v-if="loading">Đang xác thực tài khoản...</span>
          <span v-else class="btn-text-content">
            <span>Đăng nhập hệ thống</span>
            <span class="btn-arrow">&rarr;</span>
          </span>
        </button>
      </form>

      <!-- Quick Role Selector Demo -->
      <div class="demo-section">
        <div class="demo-header">
          <span class="demo-title">⚡ Chọn nhanh tài khoản Demo theo vai trò</span>
        </div>
        <div class="demo-grid">
          <button
            type="button"
            @click="fillDemo('student')"
            class="demo-card-btn"
            :class="{ active: usernameOrEmail === '1671020001' }"
          >
            <div class="demo-avatar bg-student">🎓</div>
            <div class="demo-info">
              <span class="demo-role-name">Sinh viên 1</span>
              <span class="demo-sub">1671020001 (Nguyễn Văn An)</span>
            </div>
            <span class="demo-badge">Student</span>
          </button>

          <button
            type="button"
            @click="fillDemo('student2')"
            class="demo-card-btn"
            :class="{ active: usernameOrEmail === '1671020002' }"
          >
            <div class="demo-avatar bg-student2">🎓</div>
            <div class="demo-info">
              <span class="demo-role-name">Sinh viên 2</span>
              <span class="demo-sub">1671020002 (Trần Văn Bình)</span>
            </div>
            <span class="demo-badge">Student</span>
          </button>

          <button
            type="button"
            @click="fillDemo('lecturer')"
            class="demo-card-btn"
            :class="{ active: usernameOrEmail === 'giangvien01' }"
          >
            <div class="demo-avatar bg-lecturer">👨‍🏫</div>
            <div class="demo-info">
              <span class="demo-role-name">Giảng viên</span>
              <span class="demo-sub">giangvien01</span>
            </div>
            <span class="demo-badge badge-lec">Lecturer</span>
          </button>

          <button
            type="button"
            @click="fillDemo('admin')"
            class="demo-card-btn"
            :class="{ active: usernameOrEmail === 'admin' }"
          >
            <div class="demo-avatar bg-admin">🛡️</div>
            <div class="demo-info">
              <span class="demo-role-name">Quản trị viên</span>
              <span class="demo-sub">admin</span>
            </div>
            <span class="demo-badge badge-adm">Admin</span>
          </button>
        </div>
      </div>

      <!-- Security Footer Badge -->
      <div class="login-footer">
        <span class="security-chip">🔒 Bảo mật Token QR 90s • Laravel Sanctum</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'

const router = useRouter()
const usernameOrEmail = ref('1671020001')
const password = ref('12345678')
const showPassword = ref(false)
const loading = ref(false)
const errorMessage = ref('')

const fillDemo = (role) => {
  if (role === 'student') {
    usernameOrEmail.value = '1671020001'
    password.value = '12345678'
  } else if (role === 'student2') {
    usernameOrEmail.value = '1671020002'
    password.value = '12345678'
  } else if (role === 'lecturer') {
    usernameOrEmail.value = 'giangvien01'
    password.value = '12345678'
  } else if (role === 'admin') {
    usernameOrEmail.value = 'admin'
    password.value = '12345678'
  }
}

const handleLogin = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    const res = await api.post('/login', {
      username: usernameOrEmail.value.trim(),
      password: password.value
    })

    if (res.data.success) {
      const user = res.data.user || res.data.data?.user
      const token = res.data.token || res.data.data?.token

      localStorage.setItem('auth_token', token)
      localStorage.setItem('user_role', user.role)
      localStorage.setItem('user_name', user.ho_ten || user.name || user.username)
      localStorage.setItem('username', user.username)
      localStorage.setItem('user_id', user.id)
      if (user.student_id) {
        localStorage.setItem('student_id', user.student_id)
      }
      localStorage.setItem('user_info', JSON.stringify(user))

      // Điều hướng theo vai trò (Role Routing)
      if (user.role === 'student') {
        router.push('/student')
      } else {
        router.push('/dashboard')
      }
    } else {
      errorMessage.value = res.data.message || 'Đăng nhập không thành công'
    }
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Không thể kết nối đến máy chủ API'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-wrapper {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: radial-gradient(circle at 10% 20%, #0f172a 0%, #020617 90%);
  padding: 2rem 1.25rem;
  position: relative;
  overflow: hidden;
}

/* Ambient Lighting Orbs */
.ambient-orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(90px);
  pointer-events: none;
  z-index: 1;
}

.orb-1 {
  width: 450px;
  height: 450px;
  background: rgba(37, 99, 235, 0.22);
  top: -100px;
  left: -100px;
}

.orb-2 {
  width: 400px;
  height: 400px;
  background: rgba(99, 102, 241, 0.2);
  bottom: -100px;
  right: -80px;
}

.orb-3 {
  width: 300px;
  height: 300px;
  background: rgba(16, 185, 129, 0.12);
  top: 40%;
  left: 55%;
}

/* Glassmorphism Card */
.login-card-glass {
  position: relative;
  z-index: 10;
  width: 100%;
  max-width: 470px;
  background: rgba(15, 23, 42, 0.75);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 24px;
  padding: 2.5rem 2.25rem;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5),
              0 0 0 1px rgba(255, 255, 255, 0.05),
              inset 0 1px 0 rgba(255, 255, 255, 0.1);
  color: #f8fafc;
}

/* Header */
.login-header {
  text-align: center;
  margin-bottom: 2rem;
}

.brand-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.3rem 0.85rem;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  color: #94a3b8;
  margin-bottom: 1.25rem;
}

.live-dot {
  width: 7px;
  height: 7px;
  background-color: #10b981;
  border-radius: 50%;
  box-shadow: 0 0 8px #10b981;
}

.logo-box-glow {
  width: 68px;
  height: 68px;
  background: linear-gradient(135deg, rgba(37, 99, 235, 0.3), rgba(99, 102, 241, 0.2));
  border: 1.5px solid rgba(96, 165, 250, 0.4);
  border-radius: 20px;
  font-size: 2.2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.15rem;
  box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.4);
}

.login-title {
  font-size: 1.65rem;
  font-weight: 800;
  color: #ffffff;
  letter-spacing: -0.02em;
}

.login-subtitle {
  font-size: 0.86rem;
  color: #94a3b8;
  margin-top: 0.4rem;
}

/* Error Banner */
.error-banner {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid rgba(248, 113, 113, 0.35);
  color: #fca5a5;
  padding: 0.85rem 1rem;
  border-radius: 12px;
  font-size: 0.86rem;
  margin-bottom: 1.5rem;
}

/* Form Controls */
.form-group-custom {
  margin-bottom: 1.35rem;
}

.form-label-custom {
  display: block;
  font-size: 0.85rem;
  font-weight: 600;
  color: #cbd5e1;
  margin-bottom: 0.45rem;
}

.label-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.45rem;
}

.default-pwd-hint {
  font-size: 0.74rem;
  color: #64748b;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 1rem;
  font-size: 1rem;
  color: #94a3b8;
  pointer-events: none;
}

.input-custom {
  width: 100%;
  padding: 0.85rem 1rem 0.85rem 2.75rem;
  background: rgba(30, 41, 59, 0.65);
  border: 1.5px solid rgba(255, 255, 255, 0.12);
  border-radius: 12px;
  color: #ffffff;
  font-size: 0.95rem;
  outline: none;
  transition: all 0.25s ease;
}

.input-custom:focus {
  background: rgba(30, 41, 59, 0.9);
  border-color: #3b82f6;
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.25);
}

.input-custom::placeholder {
  color: #64748b;
}

.btn-toggle-eye {
  position: absolute;
  right: 0.85rem;
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1rem;
  padding: 0.25rem;
  opacity: 0.7;
  transition: opacity 0.2s;
}

.btn-toggle-eye:hover {
  opacity: 1;
}

/* Submit Button */
.btn-login-submit {
  width: 100%;
  padding: 0.95rem 1.5rem;
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 50%, #4338ca 100%);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.18);
  border-radius: 12px;
  font-size: 0.98rem;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 10px 25px -4px rgba(37, 99, 235, 0.5);
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.btn-login-submit:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 14px 30px -4px rgba(37, 99, 235, 0.65);
}

.btn-login-submit:active:not(:disabled) {
  transform: translateY(0);
}

.btn-login-submit:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-text-content {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-arrow {
  transition: transform 0.2s ease;
}

.btn-login-submit:hover .btn-arrow {
  transform: translateX(4px);
}

.spinner-small {
  width: 18px;
  height: 18px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: #ffffff;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Quick Demo Selector */
.demo-section {
  margin-top: 1.75rem;
  padding-top: 1.5rem;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.demo-header {
  margin-bottom: 0.75rem;
}

.demo-title {
  font-size: 0.78rem;
  font-weight: 700;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.demo-grid {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.demo-card-btn {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: rgba(30, 41, 59, 0.45);
  border: 1px solid rgba(255, 255, 255, 0.08);
  padding: 0.6rem 0.85rem;
  border-radius: 10px;
  cursor: pointer;
  text-align: left;
  transition: all 0.2s ease;
  color: #f1f5f9;
}

.demo-card-btn:hover {
  background: rgba(30, 41, 59, 0.85);
  border-color: rgba(96, 165, 250, 0.4);
  transform: translateX(3px);
}

.demo-card-btn.active {
  background: rgba(37, 99, 235, 0.2);
  border-color: #3b82f6;
}

.demo-avatar {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
}

.bg-student { background: rgba(59, 130, 246, 0.25); color: #93c5fd; }
.bg-student2 { background: rgba(99, 102, 241, 0.25); color: #c7d2fe; }
.bg-lecturer { background: rgba(16, 185, 129, 0.25); color: #a7f3d0; }
.bg-admin { background: rgba(245, 158, 11, 0.25); color: #fde68a; }

.demo-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  line-height: 1.25;
}

.demo-role-name {
  font-size: 0.84rem;
  font-weight: 700;
  color: #ffffff;
}

.demo-sub {
  font-size: 0.72rem;
  color: #94a3b8;
}

.demo-badge {
  font-size: 0.68rem;
  font-weight: 700;
  padding: 0.2rem 0.5rem;
  border-radius: 999px;
  background: rgba(59, 130, 246, 0.2);
  color: #93c5fd;
  border: 1px solid rgba(59, 130, 246, 0.3);
}

.badge-lec {
  background: rgba(16, 185, 129, 0.2);
  color: #6ee7b7;
  border-color: rgba(16, 185, 129, 0.3);
}

.badge-adm {
  background: rgba(245, 158, 11, 0.2);
  color: #fcd34d;
  border-color: rgba(245, 158, 11, 0.3);
}

.login-footer {
  text-align: center;
  margin-top: 1.5rem;
}

.security-chip {
  font-size: 0.72rem;
  color: #64748b;
  display: inline-block;
}

@media (max-width: 480px) {
  .login-card-glass {
    padding: 1.75rem 1.25rem;
    border-radius: 20px;
  }
  .login-title {
    font-size: 1.35rem;
  }
}
</style>
