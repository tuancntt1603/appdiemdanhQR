<template>
  <div class="login-wrapper">
    <div class="login-card">
      <div class="login-header">
        <div class="logo-box">🏭</div>
        <h2>HỆ THỐNG ĐIỂM DANH XƯỞNG</h2>
        <p>Đăng nhập tài khoản Cán bộ / Quản trị viên</p>
      </div>

      <div v-if="errorMessage" class="error-banner">
        <span>⚠️ {{ errorMessage }}</span>
      </div>

      <form @submit.prevent="handleLogin" class="login-form">
        <div class="form-group">
          <label class="form-label">Email đăng nhập</label>
          <input
            v-model="email"
            type="email"
            class="form-control"
            placeholder="admin@example.com"
            required
          />
        </div>

        <div class="form-group">
          <label class="form-label">Mật khẩu</label>
          <input
            v-model="password"
            type="password"
            class="form-control"
            placeholder="••••••••"
            required
          />
        </div>

        <button type="submit" class="btn btn-primary btn-block" :disabled="loading">
          <span v-if="loading">Đang đăng nhập...</span>
          <span v-else>Đăng nhập hệ thống</span>
        </button>
      </form>

      <div class="demo-box">
        <div class="demo-title">🔑 Tài khoản Demo có sẵn:</div>
        <div>Email: <b>admin@example.com</b></div>
        <div>Mật khẩu: <b>password</b></div>
        <div class="quick-fill" @click="fillDemo">Bấm vào đây để điền nhanh</div>
      </div>

      <div class="student-portal-link">
        <router-link to="/student-qr">
          📱 Dành cho Sinh viên: Xem mã QR cá nhân &rarr;
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'

const router = useRouter()
const email = ref('admin@example.com')
const password = ref('password')
const loading = ref(false)
const errorMessage = ref('')

const fillDemo = () => {
  email.value = 'admin@example.com'
  password.value = 'password'
}

const handleLogin = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    const res = await api.post('/login', {
      email: email.value,
      password: password.value
    })

    if (res.data.success) {
      localStorage.setItem('auth_token', res.data.data.token)
      localStorage.setItem('user_name', res.data.data.user.name)
      localStorage.setItem('user_role', res.data.data.user.role)
      router.push('/dashboard')
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
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
  padding: 1.5rem;
}

.login-card {
  background: white;
  border-radius: var(--radius-lg);
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.2);
  width: 100%;
  max-width: 440px;
  padding: 2.5rem;
}

.login-header {
  text-align: center;
  margin-bottom: 2rem;
}

.logo-box {
  width: 60px;
  height: 60px;
  background: var(--primary-light);
  border-radius: var(--radius-md);
  font-size: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1rem;
}

.login-header h2 {
  font-size: 1.3rem;
  font-weight: 700;
  color: var(--gray-900);
}

.login-header p {
  font-size: 0.85rem;
  color: var(--gray-500);
  margin-top: 0.35rem;
}

.error-banner {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #dc2626;
  padding: 0.75rem 1rem;
  border-radius: var(--radius-sm);
  font-size: 0.85rem;
  margin-bottom: 1.25rem;
}

.btn-block {
  width: 100%;
  padding: 0.75rem;
  margin-top: 0.5rem;
}

.demo-box {
  margin-top: 1.5rem;
  padding: 0.85rem 1rem;
  background: #f8fafc;
  border: 1px dashed var(--gray-300);
  border-radius: var(--radius-sm);
  font-size: 0.8rem;
  color: var(--gray-600);
}

.demo-title {
  font-weight: 600;
  color: var(--gray-800);
  margin-bottom: 0.25rem;
}

.quick-fill {
  margin-top: 0.5rem;
  color: var(--primary);
  font-weight: 600;
  cursor: pointer;
  text-decoration: underline;
}

.student-portal-link {
  text-align: center;
  margin-top: 1.5rem;
  font-size: 0.85rem;
}

.student-portal-link a {
  color: var(--primary);
  font-weight: 600;
}
.student-portal-link a:hover {
  text-decoration: underline;
}
</style>
