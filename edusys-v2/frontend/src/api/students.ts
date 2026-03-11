import apiClient from './client'

export interface Student {
  gibbonPersonID: number
  surname: string
  firstName: string
  preferredName?: string
  gender?: string
  email?: string
  phone1?: string
  dob?: string
  studentID?: string
  status: string
  image_240?: string
}

export interface StudentListResponse {
  items: Student[]
  total: number
  page: number
  page_size: number
}

export interface StudentListParams {
  page?: number
  page_size?: number
  search?: string
  school_year_id?: number
  year_group_id?: number
  form_group_id?: number
}

export const studentsApi = {
  list: (params: StudentListParams = {}) =>
    apiClient.get<StudentListResponse>('/students', { params }).then(r => r.data),

  getById: (id: number) =>
    apiClient.get<Student>(`/students/${id}`).then(r => r.data),

  create: (data: Partial<Student> & { username: string; password: string }) =>
    apiClient.post<Student>('/students', data).then(r => r.data),

  update: (id: number, data: Partial<Student>) =>
    apiClient.put<Student>(`/students/${id}`, data).then(r => r.data),

  delete: (id: number) =>
    apiClient.delete(`/students/${id}`),

  getAttendance: (id: number, limit?: number) =>
    apiClient.get(`/students/${id}/attendance`, { params: { limit } }).then(r => r.data),

  getEnrolments: (id: number) =>
    apiClient.get(`/students/${id}/enrolments`).then(r => r.data),

  addEnrolment: (id: number, data: object) =>
    apiClient.post(`/students/${id}/enrolments`, data).then(r => r.data),
}
