import { Card, Typography, Table, Tag, Empty } from 'antd'
import { CalendarOutlined } from '@ant-design/icons'

const { Title, Text } = Typography

const days = ['周一', '周二', '周三', '周四', '周五']
const periods = [
  { name: '第一节', time: '08:00 - 08:45' },
  { name: '第二节', time: '08:55 - 09:40' },
  { name: '课间操', time: '09:40 - 10:10', type: 'break' },
  { name: '第三节', time: '10:10 - 10:55' },
  { name: '第四节', time: '11:05 - 11:50' },
  { name: '午休', time: '11:50 - 13:30', type: 'break' },
  { name: '第五节', time: '13:30 - 14:15' },
  { name: '第六节', time: '14:25 - 15:10' },
  { name: '第七节', time: '15:20 - 16:05' },
]

const sampleClasses: Record<string, Record<string, { course: string; teacher: string; room: string }>> = {
  '第一节': {
    '周一': { course: '语文', teacher: '张老师', room: 'A101' },
    '周二': { course: '数学', teacher: '李老师', room: 'A102' },
    '周三': { course: '英语', teacher: '王老师', room: 'A103' },
    '周四': { course: '科学', teacher: '赵老师', room: '实验室' },
    '周五': { course: '体育', teacher: '钱老师', room: '操场' },
  },
  '第二节': {
    '周一': { course: '数学', teacher: '李老师', room: 'A102' },
    '周二': { course: '语文', teacher: '张老师', room: 'A101' },
    '周三': { course: '历史', teacher: '孙老师', room: 'A104' },
    '周四': { course: '英语', teacher: '王老师', room: 'A103' },
    '周五': { course: '美术', teacher: '周老师', room: '美术室' },
  },
}

const courseColors: Record<string, string> = {
  语文: 'red', 数学: 'blue', 英语: 'green', 科学: 'purple',
  体育: 'orange', 历史: 'brown', 美术: 'pink',
}

export default function TimetablePage() {
  const columns = [
    {
      title: '时间',
      dataIndex: 'name',
      width: 100,
      render: (name: string, record: any) => (
        <div>
          <div style={{ fontWeight: record.type === 'break' ? 400 : 600, color: record.type === 'break' ? '#999' : '#333' }}>
            {name}
          </div>
          <div style={{ fontSize: 12, color: '#bbb' }}>{record.time}</div>
        </div>
      ),
    },
    ...days.map((day) => ({
      title: day,
      key: day,
      render: (_: any, record: any) => {
        if (record.type === 'break') {
          return <div style={{ textAlign: 'center', color: '#bbb', fontSize: 12 }}>——</div>
        }
        const cls = sampleClasses[record.name]?.[day]
        if (!cls) return <div style={{ textAlign: 'center', color: '#eee' }}>-</div>
        return (
          <div
            style={{
              padding: '8px 10px',
              background: `${courseColors[cls.course] || '#1677ff'}18`,
              borderRadius: 6,
              borderLeft: `3px solid ${courseColors[cls.course] || '#1677ff'}`,
            }}
          >
            <div style={{ fontWeight: 600, color: courseColors[cls.course] || '#1677ff' }}>
              {cls.course}
            </div>
            <div style={{ fontSize: 11, color: '#666' }}>{cls.teacher}</div>
            <div style={{ fontSize: 11, color: '#999' }}>{cls.room}</div>
          </div>
        )
      },
    })),
  ]

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 }}>
        <Title level={4} style={{ margin: 0 }}>
          <CalendarOutlined style={{ marginRight: 8 }} />
          课表查看
        </Title>
        <Tag color="blue">2024-2025学年 第一学期</Tag>
      </div>

      <Card
        bordered={false}
        style={{ borderRadius: 12, boxShadow: '0 2px 8px rgba(0,0,0,0.06)' }}
      >
        <Table
          rowKey="name"
          columns={columns}
          dataSource={periods}
          pagination={false}
          size="middle"
          rowClassName={(record: any) => record.type === 'break' ? 'break-row' : ''}
        />
        <div style={{ marginTop: 16, color: '#999', fontSize: 12 }}>
          * 示例课表数据。实际课表从数据库读取，需配置课表模块。
        </div>
      </Card>
    </div>
  )
}
