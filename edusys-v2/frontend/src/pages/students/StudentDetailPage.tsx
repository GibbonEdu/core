import { Tabs, Card, Descriptions, Tag, Typography, Spin, List, Progress } from 'antd'
import { useParams } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { studentsApi } from '@/api/students'

const { Title } = Typography

const statusMap: Record<string, { label: string; color: string }> = {
  Full: { label: '在读', color: 'green' },
  Expected: { label: '待入学', color: 'blue' },
  Left: { label: '已离校', color: 'default' },
}

const codeTypeColor: Record<string, string> = {
  Present: 'green', Absent: 'red', Late: 'orange', 'Left Early': 'gold',
}

export default function StudentDetailPage() {
  const { id } = useParams<{ id: string }>()
  const studentId = Number(id)

  const { data: student, isLoading } = useQuery({
    queryKey: ['student', studentId],
    queryFn: () => studentsApi.getById(studentId),
  })

  const { data: attendance } = useQuery({
    queryKey: ['student-attendance', studentId],
    queryFn: () => studentsApi.getAttendance(studentId, 30),
  })

  if (isLoading) return <Spin size="large" style={{ display: 'block', margin: '100px auto' }} />
  if (!student) return <div>学生不存在</div>

  const statusInfo = statusMap[student.status] || { label: student.status, color: 'default' }

  const presentCount = (attendance || []).filter((a: any) => a.type === 'Present').length
  const totalCount = (attendance || []).length
  const attendanceRate = totalCount > 0 ? Math.round((presentCount / totalCount) * 100) : 100

  return (
    <div>
      <div style={{ marginBottom: 24 }}>
        <Title level={4} style={{ margin: 0 }}>
          {student.surname}{student.firstName}
          <Tag color={statusInfo.color} style={{ marginLeft: 12, fontSize: 14 }}>
            {statusInfo.label}
          </Tag>
        </Title>
      </div>

      <Tabs
        items={[
          {
            key: 'info',
            label: '基本信息',
            children: (
              <Card bordered={false} style={{ borderRadius: 12 }}>
                <Descriptions bordered column={2}>
                  <Descriptions.Item label="姓名">{student.surname}{student.firstName}</Descriptions.Item>
                  <Descriptions.Item label="学号">{student.studentID || '-'}</Descriptions.Item>
                  <Descriptions.Item label="性别">
                    {{ M: '男', F: '女', Unspecified: '未知' }[student.gender || ''] || '-'}
                  </Descriptions.Item>
                  <Descriptions.Item label="出生日期">{student.dob || '-'}</Descriptions.Item>
                  <Descriptions.Item label="邮箱">{student.email || '-'}</Descriptions.Item>
                  <Descriptions.Item label="手机号">{student.phone1 || '-'}</Descriptions.Item>
                  <Descriptions.Item label="状态">
                    <Tag color={statusInfo.color}>{statusInfo.label}</Tag>
                  </Descriptions.Item>
                </Descriptions>
              </Card>
            ),
          },
          {
            key: 'attendance',
            label: '考勤记录',
            children: (
              <Card bordered={false} style={{ borderRadius: 12 }}>
                <div style={{ marginBottom: 16 }}>
                  <div style={{ fontSize: 14, color: '#666', marginBottom: 8 }}>
                    近30天出勤率
                  </div>
                  <Progress
                    percent={attendanceRate}
                    strokeColor={attendanceRate >= 90 ? '#52c41a' : attendanceRate >= 80 ? '#fa8c16' : '#ff4d4f'}
                    format={(p) => `${p}%`}
                  />
                </div>
                <List
                  dataSource={attendance || []}
                  renderItem={(item: any) => (
                    <List.Item style={{ padding: '8px 0' }}>
                      <List.Item.Meta
                        title={
                          <span>
                            <span style={{ marginRight: 12, color: '#666' }}>{item.date}</span>
                            <Tag color={codeTypeColor[item.type] || 'default'}>{item.code || '未记录'}</Tag>
                          </span>
                        }
                        description={item.reason || ''}
                      />
                    </List.Item>
                  )}
                />
              </Card>
            ),
          },
        ]}
      />
    </div>
  )
}
