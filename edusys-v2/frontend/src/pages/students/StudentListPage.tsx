import { useState } from 'react'
import {
  Table,
  Button,
  Input,
  Space,
  Tag,
  Avatar,
  Typography,
  Card,
  Modal,
  Form,
  Select,
  message,
  Tooltip,
  Popconfirm,
} from 'antd'
import {
  PlusOutlined,
  SearchOutlined,
  UserOutlined,
  EditOutlined,
  DeleteOutlined,
  EyeOutlined,
} from '@ant-design/icons'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useNavigate } from 'react-router-dom'
import { studentsApi, Student } from '@/api/students'
import type { ColumnsType } from 'antd/es/table'

const { Title } = Typography
const { Search } = Input

const genderMap: Record<string, string> = { M: '男', F: '女', Unspecified: '未知', Other: '其他' }
const statusMap: Record<string, { label: string; color: string }> = {
  Full: { label: '在读', color: 'green' },
  Expected: { label: '待入学', color: 'blue' },
  Left: { label: '已离校', color: 'default' },
  'Pending Approval': { label: '待审核', color: 'orange' },
}

export default function StudentListPage() {
  const [search, setSearch] = useState('')
  const [page, setPage] = useState(1)
  const [pageSize] = useState(20)
  const [addModalVisible, setAddModalVisible] = useState(false)
  const [form] = Form.useForm()
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['students', { page, pageSize, search }],
    queryFn: () => studentsApi.list({ page, page_size: pageSize, search: search || undefined }),
  })

  const createMutation = useMutation({
    mutationFn: studentsApi.create,
    onSuccess: () => {
      message.success('学生添加成功')
      setAddModalVisible(false)
      form.resetFields()
      queryClient.invalidateQueries({ queryKey: ['students'] })
    },
    onError: (err: any) => {
      message.error(err?.response?.data?.detail || '添加失败')
    },
  })

  const deleteMutation = useMutation({
    mutationFn: studentsApi.delete,
    onSuccess: () => {
      message.success('学生已标记为离校')
      queryClient.invalidateQueries({ queryKey: ['students'] })
    },
  })

  const columns: ColumnsType<Student> = [
    {
      title: '学生',
      key: 'student',
      render: (_, record) => (
        <Space>
          <Avatar
            src={record.image_240}
            icon={<UserOutlined />}
            style={{ background: '#1677ff' }}
          />
          <div>
            <div style={{ fontWeight: 500 }}>
              {record.surname}{record.firstName}
            </div>
            <div style={{ fontSize: 12, color: '#999' }}>
              {record.studentID || record.username || `ID:${record.gibbonPersonID}`}
            </div>
          </div>
        </Space>
      ),
    },
    {
      title: '性别',
      dataIndex: 'gender',
      width: 70,
      render: (g) => genderMap[g] || g,
    },
    {
      title: '出生日期',
      dataIndex: 'dob',
      width: 110,
      render: (d) => d || '-',
    },
    {
      title: '联系邮箱',
      dataIndex: 'email',
      render: (e) => e || '-',
      ellipsis: true,
    },
    {
      title: '手机号',
      dataIndex: 'phone1',
      width: 130,
      render: (p) => p || '-',
    },
    {
      title: '状态',
      dataIndex: 'status',
      width: 90,
      render: (s) => {
        const info = statusMap[s] || { label: s, color: 'default' }
        return <Tag color={info.color}>{info.label}</Tag>
      },
    },
    {
      title: '操作',
      key: 'action',
      width: 120,
      render: (_, record) => (
        <Space size={4}>
          <Tooltip title="查看详情">
            <Button
              type="text"
              icon={<EyeOutlined />}
              onClick={() => navigate(`/students/${record.gibbonPersonID}`)}
            />
          </Tooltip>
          <Tooltip title="编辑">
            <Button type="text" icon={<EditOutlined />} />
          </Tooltip>
          <Popconfirm
            title="确认操作"
            description="确定要将该学生标记为离校吗？"
            onConfirm={() => deleteMutation.mutate(record.gibbonPersonID)}
            okText="确定"
            cancelText="取消"
          >
            <Tooltip title="标记离校">
              <Button type="text" danger icon={<DeleteOutlined />} />
            </Tooltip>
          </Popconfirm>
        </Space>
      ),
    },
  ]

  return (
    <div>
      <div className="page-header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 }}>
        <Title level={4} style={{ margin: 0 }}>学生管理</Title>
        <Button
          type="primary"
          icon={<PlusOutlined />}
          onClick={() => setAddModalVisible(true)}
        >
          添加学生
        </Button>
      </div>

      <Card
        bordered={false}
        style={{ borderRadius: 12, boxShadow: '0 2px 8px rgba(0,0,0,0.06)' }}
      >
        <div className="search-bar">
          <Search
            placeholder="搜索姓名、学号、用户名..."
            prefix={<SearchOutlined />}
            allowClear
            onSearch={setSearch}
            onChange={(e) => !e.target.value && setSearch('')}
            style={{ width: 320 }}
          />
        </div>
        <Table<Student>
          rowKey="gibbonPersonID"
          columns={columns}
          dataSource={data?.items}
          loading={isLoading}
          pagination={{
            current: page,
            pageSize,
            total: data?.total,
            onChange: setPage,
            showTotal: (total) => `共 ${total} 名学生`,
            showSizeChanger: false,
          }}
          size="middle"
        />
      </Card>

      {/* 添加学生弹窗 */}
      <Modal
        title="添加学生"
        open={addModalVisible}
        onOk={() => form.submit()}
        onCancel={() => { setAddModalVisible(false); form.resetFields() }}
        confirmLoading={createMutation.isPending}
        okText="确认添加"
        cancelText="取消"
        width={560}
      >
        <Form form={form} layout="vertical" onFinish={createMutation.mutate} style={{ marginTop: 16 }}>
          <Form.Item label="姓名" style={{ marginBottom: 0 }}>
            <Space style={{ width: '100%' }}>
              <Form.Item name="surname" rules={[{ required: true, message: '请输入姓' }]} style={{ flex: 1 }}>
                <Input placeholder="姓" />
              </Form.Item>
              <Form.Item name="firstName" rules={[{ required: true, message: '请输入名' }]} style={{ flex: 1 }}>
                <Input placeholder="名" />
              </Form.Item>
            </Space>
          </Form.Item>
          <Form.Item name="gender" label="性别" initialValue="Unspecified">
            <Select>
              <Select.Option value="M">男</Select.Option>
              <Select.Option value="F">女</Select.Option>
              <Select.Option value="Unspecified">未指定</Select.Option>
            </Select>
          </Form.Item>
          <Form.Item name="studentID" label="学号">
            <Input placeholder="请输入学号（可选）" />
          </Form.Item>
          <Form.Item name="email" label="邮箱">
            <Input placeholder="请输入邮箱（可选）" />
          </Form.Item>
          <Form.Item name="phone1" label="手机号">
            <Input placeholder="请输入手机号（可选）" maxLength={11} />
          </Form.Item>
          <Form.Item name="username" label="用户名" rules={[{ required: true, message: '请输入用户名' }]}>
            <Input placeholder="用于登录系统" />
          </Form.Item>
          <Form.Item name="password" label="初始密码" rules={[{ required: true, message: '请输入初始密码' }]}>
            <Input.Password placeholder="建议使用默认格式，如：姓名拼音+学号" />
          </Form.Item>
        </Form>
      </Modal>
    </div>
  )
}
