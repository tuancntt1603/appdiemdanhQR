import axios from 'axios'

const api = axios.create({
  baseURL: '/api',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

// Tự động đính kèm Token đăng nhập nếu có
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Bắt lỗi 401 Unauthenticated khi token hết hạn hoặc db bị reset
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response && error.response.status === 401) {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user_name')
      localStorage.removeItem('user_role')
      if (window.location.pathname !== '/login') {
        alert('Phiên đăng nhập đã hết hạn hoặc không hợp lệ. Vui lòng đăng nhập lại!')
        window.location.href = '/login'
      }
    }
    return Promise.reject(error)
  }
)

export default api

