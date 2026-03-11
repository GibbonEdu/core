import { useState } from 'react'
import {
  Card, Row, Col, Select, DatePicker, Table, Button, Tag, Space,
  Typography, message, Statistic, Progress,
} from 'antd'
import { CheckCircleOutlined, CloseCircleOutlined, ClockCircleOutlined } from '@ant-design/icons'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import dayjs, { Dayjs } from 'dayjs'
import apiClient from '@/api/client'

const { Title } = Typography

const mockStudents = [
  { id: 1, name: '张小明', studentID: 'S001', status: null },
  { id: 2, name: '李小红', studentID: 'S002', status: null },
  { id: 3, name: '王小刚', studentID: 'S003', status: null },
  { id: 4, name: '赵小芳', studentID: 'S004', status: null },
  { id: 5, name: '钱小华', studentID: 'S005', status: null },
]

const codeOptions = [
  { value: 1, label: '出席', type: 'Present', color: 'green' },
  { value: 2, label: '缺席', type: 'Absent', color: 'red' },
  { value: 3, label: '迟到', type: 'Late', color: 'orange' },
  { value: 4, label: '早退', type: 'Left Early', color: 'gold' },
]

export default function AttendancePage() {
  const [selectedDate, setSelectedDate] = useState<Dayjs>(dayjs())
  const [attendanceData, setAttendanceData] = useState<Record<number, number>>({})

  const presentCount = Object.values(attendanceData).filter(v => v === 1).length
  const absentCount = Object.values(attendanceData).filter(v => v === 2).length
  const lateCount = Object.values(attendanceData).filter(v => v === 3).length
  const total = mockStudents.length
  const rate = total > 0 ? Math.round(((presentCount + lateCount) / total) * 100) : 0

  const handleCodeChange = (studentId: number, codeId: number) => {
    setAttendanceData(prev => ({ ...prev, [studentId]: codeId }))
  }

  const handleBulkSet = (codeId: number) => {
    const newData: Record<number, number> = {}
    mockStudents.forEach(s => { newData[s.id] = codeId })
    setAttendanceData(newData)
  }

  const handleSave = () => {
    const unmarked = mockStudents.filter(s => !attendanceData[s.id])
    if (unmarked.length > 0) {
      message.warning(`还有 ${unmarked.length} 名学生未标记考勤`)
      return
    }
    message.success(`${selectedDate.format('YYYY-MM-DD')} 考勤已保存，共 ${total} 条记录`)
  }

  const columns = [
    {
      title: '学生姓名',
      dataIndex: 'name',
      render: (name: string, record: any) => (
        <div>
          <div style={{ fontWeight: 500 }}>{name}</div>
          <div style={{ fontSize: 12, color: '#999' }}>{record.studentID}</div>
        </div>
      ),
    },
    {
      title: '考勤状态',
      key: 'status',
      width: 300,
      render: (_: any, record: any) => (
        <Select
          style={{ width: 200 }}
          placeholder="请选择状态"
          value={attendanceData[record.id]}
          onChange={(val) => handleCodeChange(record.id, val)}
          options={codeOptions.map(o => ({
            value: o.value,
            label: <Tag color={o.color}>{o.label}</Tag>,
          }))}
        />
      ),
    },
    {
      title: '当前状态',
      key: 'indicator',
      width: 100,
      render: (_: any, record: any) => {
        const code = codeOptions.find(c => c.value === attendanceData[record.id])
        if (!code) return <span style={{ color: '#d9d9d9' }}>未标记</span>
        return <Tag color={code.color}>{code.label}</Tag>
      },
    },
  ]

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 }}>
        <Title level={4} style={{ margin: 0 }}>考勤管理</Title>
      </div>

      {/* 统计概览 */}
      <Row gutter={[16, 16]} style={{ marginBottom: 16 }}>
        <Col xs={24} sm={6}>
          <Card bordered={false} style={{ borderRadius: 10 }}>
            <Statistic title="出勤率" value={rate} suffix="%" valueStyle={{ color: rate >= 90 ? '#52c41a' : '#fa8c16' }} />
            <Progress percent={rate} size="small" showInfo={false} strokeColor={rate >= 90 ? '#52c41a' : '#fa8c16'} />
          </Card>
        </Col>
        <Col xs={24} sm={6}>
          <Card bordered={false} style={{ borderRadius: 10 }}>
            <Statistic title="出席" value={presentCount} prefix={<CheckCircleOutlined style={{ color: '#52c41a' }} />} />
          </Card>
        </Col>
        <Col xs={24} sm={6}>
          <Card bordered={false} style={{ borderRadius: 10 }}>
            <Statistic title="缺席" value={absentCount} prefix={<CloseCircleOutlined style={{ color: '#ff4d4f' }} />} />
          </Card>
        </Col>
        <Col xs={24} sm={6}>
          <Card bordered={false} style={{ borderRadius: 10 }}>
            <Statistic title="迟到" value={lateCount} prefix={<ClockCircleOutlined style={{ color: '#fa8c16' }} />} />
          </Card>
        </Col>
      </Row>

      <Card
        bordered={false}
        style={{ borderRadius: 12, boxShadow: '0 2px 8px rgba(0,0,0,0.06)' }}
        title={
          <Space wrap>
            <span>考勤日期：</span>
            <DatePicker
              value={selectedDate}
              onChange={(d) => d && setSelectedDate(d)}
              allowClear={false}
            />
            <span style={{ marginLeft: 16 }}>批量操作：</span>
            {codeOptions.map(code => (
              <Button
                key={code.value}
                size="small"
                onClick={() => handleBulkSet(code.value)}
              >
                全部{code.label}
              </Button>
            ))}
          </Space>
        }
        extra={
          <Button type="primary" onClick={handleSave}>
            保存考勤
          </Button>
        }
      >
        <Table
          rowKey="id"
          columns={columns}
          dataSource={mockStudents}
          pagination={false}
          size="middle"
        />
      </Card>
    </div>
  )
}
