import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import { useAuthStore } from '@/store/authStore'
import MainLayout from '@/components/Layout/MainLayout'
import LoginPage from '@/pages/auth/LoginPage'
import DashboardPage from '@/pages/dashboard/DashboardPage'
import StudentListPage from '@/pages/students/StudentListPage'
import StudentDetailPage from '@/pages/students/StudentDetailPage'
import CourseListPage from '@/pages/courses/CourseListPage'
import TimetablePage from '@/pages/timetable/TimetablePage'
import AttendancePage from '@/pages/attendance/AttendancePage'
import MarkbookPage from '@/pages/markbook/MarkbookPage'
import AIAnalyticsPage from '@/pages/ai/AIAnalyticsPage'

function PrivateRoute({ children }: { children: React.ReactNode }) {
  const { token } = useAuthStore()
  return token ? <>{children}</> : <Navigate to="/login" replace />
}

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/login" element={<LoginPage />} />
        <Route
          path="/"
          element={
            <PrivateRoute>
              <MainLayout />
            </PrivateRoute>
          }
        >
          <Route index element={<Navigate to="/dashboard" replace />} />
          <Route path="dashboard" element={<DashboardPage />} />
          <Route path="students" element={<StudentListPage />} />
          <Route path="students/:id" element={<StudentDetailPage />} />
          <Route path="courses" element={<CourseListPage />} />
          <Route path="timetable" element={<TimetablePage />} />
          <Route path="attendance" element={<AttendancePage />} />
          <Route path="markbook" element={<MarkbookPage />} />
          <Route path="ai" element={<AIAnalyticsPage />} />
        </Route>
      </Routes>
    </BrowserRouter>
  )
}
