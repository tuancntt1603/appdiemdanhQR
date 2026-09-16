<template>
  <div class="workshop-page">
    <div class="card page-header-card">
      <div class="header-content">
        <div>
          <h2>🏢 Quản Lý Xưởng Thực Hành</h2>
          <p>Danh mục các phòng/xưởng thực hành và địa điểm phục vụ điểm danh</p>
        </div>
        <button @click="openModal()" class="btn btn-primary">
          + Thêm xưởng mới
        </button>
      </div>
    </div>

    <div class="card mt-4">
      <div class="table-container">
        <table class="custom-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Tên Xưởng Thực Hành</th>
              <th>Địa Điểm / Phòng</th>
              <th>Ngày Tạo</th>
              <th class="text-right">Thao Tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="5" class="text-center py-4">Đang tải danh sách xưởng...</td>
            </tr>
            <tr v-else-if="workshops.length === 0">
              <td colspan="5" class="text-center py-4">Chưa có xưởng thực hành nào</td>
            </tr>
            <tr v-for="ws in workshops" :key="ws.id">
              <td>#{{ ws.id }}</td>
              <td><b>{{ ws.ten_xuong }}</b></td>
              <td>{{ ws.dia_diem || '--' }}</td>
              <td>{{ formatDate(ws.created_at) }}</td>
              <td class="text-right">
                <button @click="openModal(ws)" class="btn-action edit" title="Sửa">✏️</button>
                <button @click="deleteWorkshop(ws)" class="btn-action delete" title="Xóa">🗑️</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Form -->
    <div v-if="showModal" class="modal-overlay">
      <div class="modal-content">
        <div class="modal-header">
          <h3>{{ editingId ? 'Sửa xưởng thực hành' : 'Thêm xưởng mới' }}</h3>
          <button @click="showModal = false" class="btn-close">&times;</button>
        </div>

        <form @submit.prevent="saveWorkshop">
          <div class="form-group">
            <label class="form-label">Tên xưởng (*)</label>
            <input
              v-model="form.ten_xuong"
              class="form-control"
              placeholder="VD: Xưởng thực hành Máy tính & Mạng"
              required
            />
          </div>

          <div class="form-group">
            <label class="form-label">Địa điểm / Phòng</label>
            <input
              v-model="form.dia_diem"
              class="form-control"
              placeholder="VD: Tòa B - Phòng B302"
            />
          </div>

          <div class="modal-actions">
            <button type="button" @click="showModal = false" class="btn btn-secondary">Hủy</button>
            <button type="submit" class="btn btn-primary" :disabled="saving">
              {{ saving ? 'Đang lưu...' : 'Lưu xưởng' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../services/api'

const workshops = ref([])
const loading = ref(false)
const showModal = ref(false)
const editingId = ref(null)
const saving = ref(false)

const form = ref({
  ten_xuong: '',
  dia_diem: ''
})

const fetchWorkshops = async () => {
  loading.value = true
  try {
    const res = await api.get('/workshops')
    if (res.data.success) {
      workshops.value = res.data.data
    }
  } catch (err) {
    console.error('Lỗi lấy xưởng:', err)
  } finally {
    loading.value = false
  }
}

const openModal = (ws = null) => {
  if (ws) {
    editingId.value = ws.id
    form.value = { ...ws }
  } else {
    editingId.value = null
    form.value = { ten_xuong: '', dia_diem: '' }
  }
  showModal.value = true
}

const saveWorkshop = async () => {
  saving.value = true
  try {
    if (editingId.value) {
      await api.put(`/workshops/${editingId.value}`, form.value)
    } else {
      await api.post('/workshops', form.value)
    }
    showModal.value = false
    fetchWorkshops()
  } catch (err) {
    alert('Có lỗi xảy ra: ' + (err.response?.data?.message || err.message))
  } finally {
    saving.value = false
  }
}

const deleteWorkshop = async (ws) => {
  if (confirm(`Bạn có chắc muốn xóa xưởng ${ws.ten_xuong}?`)) {
    try {
      await api.delete(`/workshops/${ws.id}`)
      fetchWorkshops()
    } catch (err) {
      alert('Không thể xóa xưởng: ' + (err.response?.data?.message || err.message))
    }
  }
}

const formatDate = (dt) => {
  if (!dt) return '--'
  return new Date(dt).toLocaleDateString('vi-VN')
}

onMounted(() => {
  fetchWorkshops()
})
</script>

<style scoped>
.page-header-card {
  padding: 1.25rem;
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
}

.btn-action:hover {
  background: var(--gray-100);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid var(--gray-200);
  padding-bottom: 0.75rem;
  margin-bottom: 1.25rem;
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
</style>
