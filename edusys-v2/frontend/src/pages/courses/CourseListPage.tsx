import { useState } from 'react'
import { Table, Button, Input, Space, Typography, Card, Tag, Modal, Form, message } from 'antd'
import { PlusOutlined, SearchOutlined, BookOutlined } from '@ant-design/icons'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import apiClient from '@/api/client'
import type { ColumnsType } from 'antd/es/table'

const { Title } = Typography
const { Search } = Input

interface Course {
  gibbonCourseID: number
  gibbonSchoolYearID: number
  name: string
  nameShort: string
  description?: string
  gibbonYearGroupIDList?: string
}

export default function CourseListPage() {
  const [search, setSearch] = useState('')
  const [addVisible, setAddVisible] = useState(false)
  const [form] = Form.useForm()
  const queryClient = useQueryClient()

  const { data: courses = [], isLoading } = useQuery({
    queryKey: ['courses', search],
    queryFn: () =>
      apiClient.get<Course[]>('/courses', { params: { search: search || undefined } }).then(r => r.data),
  })

  const createMutation = useMutation({
    mutationFn: (data: any) => apiClient.post('/courses', data).then(r => r.data),
    onSuccess: () => {
      message.success('课程创建成功')
      setAddVisible(false)
      form.resetFields()
      queryClient.invalidateQueries({ queryKey: ['courses'] })
    },
    onError: (err: any) => {
      message.error(err?.response?.data?.detail || '创建失败')
    },
  })

  const columns: ColumnsType<Course> = [
    {
      title: '课程名称',
      key: 'name',
      render: (_, record) => (
        <Space>
          <BookOutlined style={{ color: '#1677ff' }} />
          <div>
            <div style={{ fontWeight: 500 }}>{record.name}</div>
            <div style={{ fontSize: 12, color: '#999' }}>{record.nameShort}</div>
          </div>
        </Space>
      ),
    },
    {
      title: '课程描述',
      dataIndex: 'description',
      ellipsis: true,
      render: (d) => d || <span style={{ color: '#bbb' }}>无描述</span>,
    },
    {
      title: '适用年级',
      dataIndex: 'gibbonYearGroupIDList',
      render: (v) => v ? <Tag color="blue">已设置</Tag> : <Tag>未设置</Tag>,
    },
    {
      title: '操作',
      key: 'action',
      width: 100,
      render: () => (
        <Button type="link" size="small">查看班级</Button>
      ),
    },
  ]

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 }}>
        <Title level={4} style={{ margin: 0 }}>课程管理</Title>
        <Button type="primary" icon={<PlusOutlined />} onClick={() => setAddVisible(true)}>
          新建课程
        </Button>
      </div>

      <Card bordered={false} style={{ borderRadius: 12, boxShadow: '0 2px 8px rgba(0,0,0,0.06)' }}>
        <div style={{ marginBottom: 16 }}>
          <Search
            placeholder="搜索课程名称..."
            prefix={<SearchOutlined />}
            allowClear
            onSearch={setSearch}
            onChange={(e) => !e.target.value && setSearch('')}
            style={{ width: 300 }}
          />
        </div>
        <Table<Course>
          rowKey="gibbonCourseID"
          columns={columns}
          dataSource={courses}
          loading={isLoading}
          size="middle"
          pagination={{ showTotal: (total) => `共 ${total} 门课程` }}
        />
      </Card>

      <Modal
        title="新建课程"
        open={addVisible}
        onOk={() => form.submit()}
        onCancel={() => { setAddVisible(false); form.resetFields() }}
        confirmLoading={createMutation.isPending}
        okText="确认创建"
        cancelText="取消"
      >
        <Form form={form} layout="vertical" onFinish={createMutation.mutate} style={{ marginTop: 16 }}>
          <Form.Item name="gibbonSchoolYearID" label="学年ID" rules={[{ required: true }]}>
            <Input type="number" placeholder="请输入学年ID" />
          </Form.Item>
          <Form.Item name="name" label="课程名称" rules={[{ required: true, message: '请输入课程名称' }]}>
            <Input placeholder="如：数学、语文、英语..." />
          </Form.Item>
          <Form.Item name="nameShort" label="课程简称" rules={[{ required: true, message: '请输入课程简称' }]}>
            <Input placeholder="如：数、语、英" maxLength={14} />
          </Form.Item>
          <Form.Item name="description" label="课程描述">
            <Input.TextArea rows={3} placeholder="课程描述（可选）" />
          </Form.Item>
        </Form>
      </Modal>
    </div>
  )
}
