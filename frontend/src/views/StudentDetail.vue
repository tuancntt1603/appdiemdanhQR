<template>
  <div class="student-detail-page">
    <div class="card header-card">
      <div class="header-left">
        <button @click="$router.back()" class="btn btn-secondary btn-sm">
          &larr; Quay lại
        </button>
        <div class="profile-header">
          <h2>{{ student.ho_ten }}</h2>
          <span class="badge badge-info">{{ student.ma_sinh_vien }}</span>
        </div>
      </div>
      <router-link :to="`/student-qr`" class="btn btn-primary btn-sm">
        📱 Xem QR Sinh Viên
      </router-link>
    </div>

    <div class="detail-grid">
      <!-- Basic Info -->
      <div class="card info-box">
        <h3>Thông Tin Hồ Sơ</h3>
        <div class="info-list">
          <div class="info-row">
            <span class="lbl">Họ và tên:</span>
            <span class="val">{{ student.ho_ten }}</span>
          </div>
          <div class="info-row">
            <span class="lbl">Mã SV:</span>
            <span class="val">{{ student.ma_sinh_vien }}</span>
          </div>
          <div class="info-row">
            <span class="lbl">Lớp:</span>
            <span class="val">{{ student.lop }}</span>
          </div>
          <div class="info-row">
            <span class="lbl">Khoa:</span>
            <span class="val">{{ student.khoa || 'CNTT' }}</span>
          </div>
          <div class="info-row">
            <span class="lbl">Email:</span>
            <span class="val">{{ student.email || '--' }}</span>
          </div>
        </div>
      </div>

      <!-- Attendance History of this student -->
      <div class="card history-box">
        <h3>Lịch Sử Điểm Danh</h3>
        <div class="table-container mt-2">
          <table class="custom-table">
            <thead>
              <tr>
                <th>Ngày</th>
                <th>Giờ vào</th>
                <th>Giờ ra</th>
                <th>Trạng thái</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!student.attendances || student.attendances.length === 0">
                <td colspan="4" class="text-center py-4">Chưa có dữ liệu điểm danh</td>
              </tr>
              <tr v-for="att in student.attendances" :key="att.id">
                <td>{{ formatDate(att.check_in) }}</td>
                <td>{{ formatTime(att.check_in) }}</td>
                <td>{{ att.check_out ? formatTime(att.check_out) : '--:--' }}</td>
                <td>
                  <span class="badge badge-success" v-if="att.status === 'hoan_thanh'">Hoàn thành</span>
                  <span class="badge badge-info" v-else>Đang ở xưởng</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '../services/api'

const route = useRoute()
const student = ref({})

const fetchStudent = async () => {
  try {
    const res = await api.get(`/students/${route.params.id}`)
    if (res.data.success) {
      student.value = res.data.data
    }
  } catch (err) {
    console.error('Lỗi khi tải chi tiết sinh viên:', err)
  }
}

const formatDate = (d) => d ? new Date(d).toLocaleDateString('vi-VN') : '--'
const formatTime = (d) => d ? new Date(d).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }) : '--'

onMounted(() => {
  fetchStudent()
})
</script>

<style scoped>
.header-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.profile-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.profile-header h2 {
  font-size: 1.25rem;
  font-weight: 700;
}

.detail-grid {
  display: grid;
  grid-template-columns: 1fr 2fr;
  gap: 1.25rem;
}

@media (max-width: 800px) {
  .detail-grid {
    grid-template-columns: 1fr;
  }
}

.info-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-top: 1rem;
}

.info-row {
  display: flex;
  justify-content: space-between;
  padding-bottom: 0.35rem;
  border-bottom: 1px dashed var(--gray-200);
  font-size: 0.88rem;
}

.lbl { color: var(--gray-500); }
.val { font-weight: 600; color: var(--gray-800); }

.mt-2 { margin-top: 0.75rem; }
.text-center { text-align: center; }
.py-4 { padding-top: 1.5rem; padding-bottom: 1.5rem; }
</style>
