import { useState } from 'react'
import {
  Card, Tabs, Button, Input, Typography, Space, Tag, List, Progress,
  Spin, Alert, Divider, Row, Col, Select, Avatar,
} from 'antd'
import {
  RobotOutlined, SendOutlined, WarningOutlined, FileTextOutlined,
  LineChartOutlined, CalendarOutlined, TeamOutlined,
} from '@ant-design/icons'
import { useMutation } from '@tanstack/react-query'
import apiClient from '@/api/client'

const { Title, Text, Paragraph } = Typography
const { TextArea } = Input

interface ChatMessage {
  role: 'user' | 'assistant'
  content: string
}

const mockAtRiskStudents = [
  { name: '张小明', risk_level: '高', reason: '连续5天缺勤，数学成绩下降20分', suggestion: '立即联系家长，安排心理辅导' },
  { name: '李小红', risk_level: '中', reason: '出勤率78%，英语成绩持续下滑', suggestion: '与班主任沟通，了解家庭情况' },
  { name: '王小刚', risk_level: '中', reason: '近期表现积极性下降', suggestion: '安排课后辅导，关注学习动态' },
]

const riskColors: Record<string, string> = { 高: 'red', 中: 'orange', 低: 'green' }

export default function AIAnalyticsPage() {
  const [chatInput, setChatInput] = useState('')
  const [messages, setMessages] = useState<ChatMessage[]>([
    {
      role: 'assistant',
      content: '您好！我是教育管理AI助手，可以帮您分析学生数据、生成报告、回答教学管理问题。请问有什么可以帮到您？',
    },
  ])

  const [reportStudentId, setReportStudentId] = useState('')
  const [generatedReport, setGeneratedReport] = useState('')

  const chatMutation = useMutation({
    mutationFn: (question: string) =>
      apiClient.post('/ai/chat', { question }).then(r => r.data),
    onSuccess: (data) => {
      setMessages(prev => [...prev, { role: 'assistant', content: data.answer }])
    },
    onError: () => {
      setMessages(prev => [
        ...prev,
        { role: 'assistant', content: '抱歉，AI服务暂时不可用，请检查DeepSeek API配置。' },
      ])
    },
  })

  const reportMutation = useMutation({
    mutationFn: (studentId: string) =>
      apiClient.post('/ai/report/generate', { student_id: parseInt(studentId) }).then(r => r.data),
    onSuccess: (data) => {
      setGeneratedReport(data.report)
    },
  })

  const handleSendChat = () => {
    if (!chatInput.trim()) return
    const question = chatInput.trim()
    setMessages(prev => [...prev, { role: 'user', content: question }])
    setChatInput('')
    chatMutation.mutate(question)
  }

  return (
    <div>
      <div style={{ marginBottom: 16 }}>
        <Title level={4} style={{ margin: 0 }}>
          <RobotOutlined style={{ marginRight: 8, color: '#722ed1' }} />
          AI 分析中心
        </Title>
        <Text type="secondary">基于 DeepSeek 大模型，提供智能教育分析与辅助</Text>
      </div>

      <Tabs
        defaultActiveKey="chat"
        items={[
          {
            key: 'chat',
            label: <Space><RobotOutlined />AI 助手</Space>,
            children: (
              <Card
                bordered={false}
                style={{ borderRadius: 12, boxShadow: '0 2px 8px rgba(0,0,0,0.06)' }}
                bodyStyle={{ padding: 0 }}
              >
                {/* 消息列表 */}
                <div style={{ height: 420, overflowY: 'auto', padding: 24 }}>
                  {messages.map((msg, idx) => (
                    <div
                      key={idx}
                      style={{
                        display: 'flex',
                        justifyContent: msg.role === 'user' ? 'flex-end' : 'flex-start',
                        marginBottom: 16,
                        gap: 10,
                      }}
                    >
                      {msg.role === 'assistant' && (
                        <Avatar
                          icon={<RobotOutlined />}
                          style={{ background: '#722ed1', flexShrink: 0 }}
                        />
                      )}
                      <div
                        style={{
                          maxWidth: '70%',
                          padding: '12px 16px',
                          borderRadius: msg.role === 'user' ? '16px 4px 16px 16px' : '4px 16px 16px 16px',
                          background: msg.role === 'user' ? '#1677ff' : '#f5f5f5',
                          color: msg.role === 'user' ? '#fff' : '#333',
                          lineHeight: 1.6,
                          whiteSpace: 'pre-wrap',
                        }}
                      >
                        {msg.content}
                        {chatMutation.isPending && idx === messages.length - 1 && msg.role === 'user' && (
                          <div style={{ marginTop: 8 }}>
                            <Spin size="small" />
                          </div>
                        )}
                      </div>
                    </div>
                  ))}
                  {chatMutation.isPending && (
                    <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginBottom: 16 }}>
                      <Avatar icon={<RobotOutlined />} style={{ background: '#722ed1', flexShrink: 0 }} />
                      <div style={{ padding: '12px 16px', background: '#f5f5f5', borderRadius: '4px 16px 16px 16px' }}>
                        <Spin size="small" /> AI正在思考中...
                      </div>
                    </div>
                  )}
                </div>

                {/* 快捷问题 */}
                <div style={{ padding: '0 24px 12px', borderTop: '1px solid #f0f0f0' }}>
                  <Text type="secondary" style={{ fontSize: 12 }}>快捷问题：</Text>
                  <Space wrap style={{ marginTop: 8 }}>
                    {[
                      '如何提高学生出勤率？',
                      '如何分析成绩趋势？',
                      '怎样发现学习困难学生？',
                      '如何写学生评语？',
                    ].map(q => (
                      <Tag
                        key={q}
                        style={{ cursor: 'pointer', borderRadius: 12 }}
                        color="purple"
                        onClick={() => { setChatInput(q) }}
                      >
                        {q}
                      </Tag>
                    ))}
                  </Space>
                </div>

                {/* 输入框 */}
                <div style={{ padding: 16, borderTop: '1px solid #f0f0f0', display: 'flex', gap: 12 }}>
                  <TextArea
                    value={chatInput}
                    onChange={(e) => setChatInput(e.target.value)}
                    placeholder="请输入问题，按Shift+Enter换行，Enter发送..."
                    autoSize={{ minRows: 1, maxRows: 4 }}
                    onPressEnter={(e) => {
                      if (!e.shiftKey) {
                        e.preventDefault()
                        handleSendChat()
                      }
                    }}
                    style={{ borderRadius: 8 }}
                  />
                  <Button
                    type="primary"
                    icon={<SendOutlined />}
                    onClick={handleSendChat}
                    loading={chatMutation.isPending}
                    style={{ height: 'auto', minHeight: 38, borderRadius: 8 }}
                  >
                    发送
                  </Button>
                </div>
              </Card>
            ),
          },
          {
            key: 'atrisk',
            label: <Space><WarningOutlined />风险预警</Space>,
            children: (
              <Card
                bordered={false}
                style={{ borderRadius: 12, boxShadow: '0 2px 8px rgba(0,0,0,0.06)' }}
                title={
                  <Space>
                    <WarningOutlined style={{ color: '#fa8c16' }} />
                    <span>学习风险学生识别</span>
                    <Tag color="orange">{mockAtRiskStudents.length} 名需关注</Tag>
                  </Space>
                }
                extra={
                  <Button type="primary" size="small">
                    重新分析
                  </Button>
                }
              >
                <Alert
                  message="AI分析结果（基于近30天考勤率 + 成绩趋势）"
                  type="info"
                  showIcon
                  style={{ marginBottom: 16, borderRadius: 8 }}
                />
                <List
                  dataSource={mockAtRiskStudents}
                  renderItem={(student) => (
                    <List.Item
                      style={{
                        padding: 16,
                        marginBottom: 12,
                        background: '#fafafa',
                        borderRadius: 10,
                        border: `1px solid ${riskColors[student.risk_level]}30`,
                      }}
                    >
                      <List.Item.Meta
                        avatar={
                          <Avatar style={{ background: riskColors[student.risk_level] }}>
                            {student.name[0]}
                          </Avatar>
                        }
                        title={
                          <Space>
                            <span style={{ fontWeight: 600 }}>{student.name}</span>
                            <Tag color={riskColors[student.risk_level]}>
                              {student.risk_level}风险
                            </Tag>
                          </Space>
                        }
                        description={
                          <div>
                            <div style={{ color: '#666', marginBottom: 4 }}>
                              <WarningOutlined style={{ marginRight: 4, color: '#fa8c16' }} />
                              {student.reason}
                            </div>
                            <div style={{ color: '#1677ff' }}>
                              建议：{student.suggestion}
                            </div>
                          </div>
                        }
                      />
                      <Button type="link" size="small">查看详情</Button>
                    </List.Item>
                  )}
                />
              </Card>
            ),
          },
          {
            key: 'report',
            label: <Space><FileTextOutlined />AI报告生成</Space>,
            children: (
              <Card
                bordered={false}
                style={{ borderRadius: 12, boxShadow: '0 2px 8px rgba(0,0,0,0.06)' }}
              >
                <Row gutter={24}>
                  <Col xs={24} md={10}>
                    <Title level={5}>生成学生进度报告</Title>
                    <Text type="secondary">AI将根据学生的成绩、考勤和行为记录，自动生成专业的中文评语报告。</Text>
                    <Divider />
                    <Space direction="vertical" style={{ width: '100%' }}>
                      <div>
                        <Text strong>学年</Text>
                        <Select
                          defaultValue="1"
                          style={{ width: '100%', marginTop: 8 }}
                          options={[{ value: '1', label: '2024-2025学年' }]}
                        />
                      </div>
                      <div>
                        <Text strong>选择学生</Text>
                        <Input
                          style={{ marginTop: 8 }}
                          placeholder="输入学生ID"
                          value={reportStudentId}
                          onChange={(e) => setReportStudentId(e.target.value)}
                        />
                      </div>
                      <Button
                        type="primary"
                        block
                        loading={reportMutation.isPending}
                        onClick={() => reportStudentId && reportMutation.mutate(reportStudentId)}
                        icon={<RobotOutlined />}
                        style={{ marginTop: 8 }}
                      >
                        生成AI报告
                      </Button>
                    </Space>
                  </Col>
                  <Col xs={24} md={14}>
                    <Title level={5}>报告预览</Title>
                    {reportMutation.isPending && (
                      <div style={{ textAlign: 'center', padding: 40 }}>
                        <Spin size="large" />
                        <div style={{ marginTop: 16, color: '#666' }}>AI正在生成报告，请稍候...</div>
                      </div>
                    )}
                    {generatedReport ? (
                      <div
                        style={{
                          padding: 20,
                          background: '#f9f9f9',
                          borderRadius: 10,
                          border: '1px solid #e8e8e8',
                          lineHeight: 1.8,
                          whiteSpace: 'pre-wrap',
                        }}
                      >
                        {generatedReport}
                      </div>
                    ) : (
                      !reportMutation.isPending && (
                        <div
                          style={{
                            padding: 40,
                            textAlign: 'center',
                            color: '#bbb',
                            background: '#f9f9f9',
                            borderRadius: 10,
                          }}
                        >
                          <FileTextOutlined style={{ fontSize: 40, marginBottom: 12 }} />
                          <div>选择学生后点击「生成AI报告」</div>
                        </div>
                      )
                    )}
                    {generatedReport && (
                      <div style={{ marginTop: 12, textAlign: 'right' }}>
                        <Button onClick={() => navigator.clipboard.writeText(generatedReport)}>
                          复制报告
                        </Button>
                      </div>
                    )}
                  </Col>
                </Row>
              </Card>
            ),
          },
        ]}
      />
    </div>
  )
}
