import { useState } from 'react'
import {
  Card, Table, Button, Typography, Space, Tag, Input, Modal,
  Form, Select, InputNumber, message, Tabs,
} from 'antd'
import { PlusOutlined, SaveOutlined } from '@ant-design/icons'

const { Title } = Typography

interface Assessment {
  id: number
  name: string
  type: string
  date: string
}

interface StudentGrade {
  id: number
  name: string
  studentID: string
  grades: Record<number, number | null>
}

const mockAssessments: Assessment[] = [
  { id: 1, name: '第一次月考', type: 'Assessment', date: '2024-10-15' },
  { id: 2, name: '期中考试', type: 'Assessment', date: '2024-11-20' },
  { id: 3, name: '单元测验一', type: 'Assessment', date: '2024-12-01' },
]

const mockStudents: StudentGrade[] = [
  { id: 1, name: '张小明', studentID: 'S001', grades: { 1: 85, 2: 88, 3: 90 } },
  { id: 2, name: '李小红', studentID: 'S002', grades: { 1: 72, 2: 78, 3: 80 } },
  { id: 3, name: '王小刚', studentID: 'S003', grades: { 1: 91, 2: 93, 3: null } },
  { id: 4, name: '赵小芳', studentID: 'S004', grades: { 1: 68, 2: null, 3: 75 } },
  { id: 5, name: '钱小华', studentID: 'S005', grades: { 1: 95, 2: 97, 3: 98 } },
]

const getGradeColor = (score: number | null): string => {
  if (score === null) return '#f5f5f5'
  if (score >= 90) return '#f6ffed'
  if (score >= 75) return '#e6f7ff'
  if (score >= 60) return '#fffbe6'
  return '#fff2f0'
}

const getGradeTextColor = (score: number | null): string => {
  if (score === null) return '#bbb'
  if (score >= 90) return '#52c41a'
  if (score >= 75) return '#1677ff'
  if (score >= 60) return '#fa8c16'
  return '#ff4d4f'
}

export default function MarkbookPage() {
  const [grades, setGrades] = useState<Record<string, number | null>>({})
  const [addColumnVisible, setAddColumnVisible] = useState(false)
  const [form] = Form.useForm()

  const handleGradeChange = (studentId: number, assessmentId: number, value: number | null) => {
    setGrades(prev => ({ ...prev, [`${studentId}_${assessmentId}`]: value }))
  }

  const getGrade = (studentId: number, assessmentId: number, original: number | null) => {
    const key = `${studentId}_${assessmentId}`
    return key in grades ? grades[key] : original
  }

  const handleSave = () => {
    message.success('成绩已保存')
  }

  const columns = [
    {
      title: '学生',
      key: 'student',
      fixed: 'left' as const,
      width: 140,
      render: (_: any, record: StudentGrade) => (
        <div>
          <div style={{ fontWeight: 500 }}>{record.name}</div>
          <div style={{ fontSize: 12, color: '#999' }}>{record.studentID}</div>
        </div>
      ),
    },
    ...mockAssessments.map(assessment => ({
      title: (
        <div style={{ textAlign: 'center' as const }}>
          <div style={{ fontWeight: 600 }}>{assessment.name}</div>
          <div style={{ fontSize: 11, color: '#999', fontWeight: 400 }}>{assessment.date}</div>
        </div>
      ),
      key: `assessment_${assessment.id}`,
      width: 120,
      render: (_: any, record: StudentGrade) => {
        const score = getGrade(record.id, assessment.id, record.grades[assessment.id])
        return (
          <div style={{
            background: getGradeColor(score),
            borderRadius: 6,
            padding: '4px 8px',
            textAlign: 'center' as const,
          }}>
            <InputNumber
              value={score ?? undefined}
              min={0}
              max={100}
              style={{
                width: 70,
                border: 'none',
                background: 'transparent',
                color: getGradeTextColor(score),
                fontWeight: 600,
                textAlign: 'center',
              }}
              onChange={(val) => handleGradeChange(record.id, assessment.id, val)}
              variant="borderless"
            />
          </div>
        )
      },
    })),
    {
      title: '平均分',
      key: 'average',
      width: 90,
      fixed: 'right' as const,
      render: (_: any, record: StudentGrade) => {
        const scores = mockAssessments
          .map(a => getGrade(record.id, a.id, record.grades[a.id]))
          .filter((s): s is number => s !== null)
        if (scores.length === 0) return '-'
        const avg = scores.reduce((a, b) => a + b, 0) / scores.length
        return (
          <div style={{ textAlign: 'center', fontWeight: 600, color: getGradeTextColor(avg) }}>
            {avg.toFixed(1)}
          </div>
        )
      },
    },
  ]

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 }}>
        <Title level={4} style={{ margin: 0 }}>成绩管理</Title>
        <Space>
          <Button icon={<PlusOutlined />} onClick={() => setAddColumnVisible(true)}>
            新增评估项目
          </Button>
          <Button type="primary" icon={<SaveOutlined />} onClick={handleSave}>
            保存成绩
          </Button>
        </Space>
      </div>

      <Card
        bordered={false}
        style={{ borderRadius: 12, boxShadow: '0 2px 8px rgba(0,0,0,0.06)' }}
        title={
          <Space>
            <span>班级：</span>
            <Select defaultValue="1" style={{ width: 200 }}
              options={[{ value: '1', label: '三年级一班 - 数学' }]}
            />
          </Space>
        }
      >
        <Table
          rowKey="id"
          columns={columns}
          dataSource={mockStudents}
          pagination={false}
          scroll={{ x: 'max-content' }}
          size="middle"
        />
        <div style={{ marginTop: 12, fontSize: 12, color: '#999' }}>
          颜色说明：
          <Tag color="success" style={{ marginLeft: 8 }}>≥90 优秀</Tag>
          <Tag color="processing">≥75 良好</Tag>
          <Tag color="warning">≥60 及格</Tag>
          <Tag color="error">&lt;60 不及格</Tag>
        </div>
      </Card>

      <Modal
        title="新增评估项目"
        open={addColumnVisible}
        onOk={() => form.submit()}
        onCancel={() => { setAddColumnVisible(false); form.resetFields() }}
        okText="确认添加"
        cancelText="取消"
      >
        <Form form={form} layout="vertical" onFinish={() => { message.success('评估项目已添加'); setAddColumnVisible(false) }} style={{ marginTop: 16 }}>
          <Form.Item name="name" label="项目名称" rules={[{ required: true }]}>
            <Input placeholder="如：第一次月考、期中考试..." />
          </Form.Item>
          <Form.Item name="type" label="评估类型" initialValue="Assessment">
            <Select options={[
              { value: 'Assessment', label: '评估考试' },
              { value: 'Effort', label: '努力程度' },
              { value: 'Comment Only', label: '仅评语' },
            ]} />
          </Form.Item>
          <Form.Item name="date" label="评估日期">
            <Input type="date" />
          </Form.Item>
        </Form>
      </Modal>
    </div>
  )
}
