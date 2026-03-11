import { Row, Col, Card, Statistic, Typography, List, Tag, Space, Alert } from 'antd'
import {
  TeamOutlined,
  BookOutlined,
  CheckSquareOutlined,
  RobotOutlined,
  RiseOutlined,
  WarningOutlined,
} from '@ant-design/icons'
import { useAuthStore } from '@/store/authStore'

const { Title, Text } = Typography

const mockStats = [
  { title: '在校学生', value: 1248, icon: <TeamOutlined />, color: '#1677ff', suffix: '人' },
  { title: '开设课程', value: 86, icon: <BookOutlined />, color: '#52c41a', suffix: '门' },
  { title: '今日出勤率', value: 96.8, icon: <CheckSquareOutlined />, color: '#fa8c16', suffix: '%' },
  { title: 'AI分析报告', value: 32, icon: <RobotOutlined />, color: '#722ed1', suffix: '份' },
]

const mockAlerts = [
  { student: '张三', type: '出勤预警', desc: '连续3天未到校', level: 'error' as const },
  { student: '李四', type: '成绩下滑', desc: '数学成绩下降15分', level: 'warning' as const },
  { student: '王五', type: '出勤预警', desc: '本月出勤率低于80%', level: 'warning' as const },
]

const mockRecentActivities = [
  { time: '09:30', action: '班主任张老师完成3年级1班考勤录入' },
  { time: '09:15', action: 'AI生成月度学生进度报告 32份' },
  { time: '08:50', action: '新学生王小明完成入学注册' },
  { time: '08:30', action: '教务系统发现2处课表冲突，已标记' },
]

export default function DashboardPage() {
  const { user } = useAuthStore()
  const today = new Date().toLocaleDateString('zh-CN', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    weekday: 'long',
  })

  return (
    <div>
      <div className="page-header" style={{ marginBottom: 24 }}>
        <Title level={4} style={{ margin: 0 }}>
          欢迎回来，{user?.full_name || '管理员'} 👋
        </Title>
        <Text type="secondary">{today}</Text>
      </div>

      {/* 统计卡片 */}
      <Row gutter={[16, 16]} style={{ marginBottom: 24 }}>
        {mockStats.map((stat) => (
          <Col xs={24} sm={12} lg={6} key={stat.title}>
            <Card
              bordered={false}
              style={{ borderRadius: 12, boxShadow: '0 2px 8px rgba(0,0,0,0.06)' }}
            >
              <div style={{ display: 'flex', alignItems: 'center', gap: 16 }}>
                <div
                  style={{
                    width: 56,
                    height: 56,
                    borderRadius: 12,
                    background: `${stat.color}18`,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    fontSize: 24,
                    color: stat.color,
                  }}
                >
                  {stat.icon}
                </div>
                <Statistic
                  title={<span style={{ fontSize: 13, color: '#666' }}>{stat.title}</span>}
                  value={stat.value}
                  suffix={stat.suffix}
                  valueStyle={{ color: '#1a1a2e', fontWeight: 600, fontSize: 24 }}
                />
              </div>
            </Card>
          </Col>
        ))}
      </Row>

      <Row gutter={[16, 16]}>
        {/* 预警提醒 */}
        <Col xs={24} lg={12}>
          <Card
            title={
              <Space>
                <WarningOutlined style={{ color: '#fa8c16' }} />
                <span>学生预警提醒</span>
                <Tag color="orange">{mockAlerts.length}条</Tag>
              </Space>
            }
            bordered={false}
            style={{ borderRadius: 12, boxShadow: '0 2px 8px rgba(0,0,0,0.06)' }}
          >
            <List
              dataSource={mockAlerts}
              renderItem={(item) => (
                <List.Item
                  style={{
                    padding: '12px 0',
                    borderBottom: '1px solid #f5f5f5',
                  }}
                >
                  <List.Item.Meta
                    title={
                      <Space>
                        <span style={{ fontWeight: 600 }}>{item.student}</span>
                        <Tag color={item.level === 'error' ? 'red' : 'orange'}>{item.type}</Tag>
                      </Space>
                    }
                    description={<Text type="secondary">{item.desc}</Text>}
                  />
                </List.Item>
              )}
            />
          </Card>
        </Col>

        {/* 近期动态 */}
        <Col xs={24} lg={12}>
          <Card
            title={
              <Space>
                <RiseOutlined style={{ color: '#1677ff' }} />
                <span>近期动态</span>
              </Space>
            }
            bordered={false}
            style={{ borderRadius: 12, boxShadow: '0 2px 8px rgba(0,0,0,0.06)' }}
          >
            <List
              dataSource={mockRecentActivities}
              renderItem={(item) => (
                <List.Item style={{ padding: '10px 0', borderBottom: '1px solid #f5f5f5' }}>
                  <Space>
                    <Tag color="blue" style={{ minWidth: 44, textAlign: 'center' }}>
                      {item.time}
                    </Tag>
                    <Text>{item.action}</Text>
                  </Space>
                </List.Item>
              )}
            />
          </Card>
        </Col>

        {/* AI分析提示 */}
        <Col xs={24}>
          <Alert
            message="AI 分析就绪"
            description="本月已生成32份学生进度报告，发现3名需要重点关注的学生。点击「AI 分析中心」查看详细报告。"
            type="info"
            showIcon
            icon={<RobotOutlined />}
            action={
              <a href="/ai" style={{ color: '#1677ff', fontWeight: 500 }}>
                查看AI报告 →
              </a>
            }
            style={{ borderRadius: 12 }}
          />
        </Col>
      </Row>
    </div>
  )
}
