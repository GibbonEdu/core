import { useState, useRef } from 'react'
import { Form, Input, Button, Tabs, Card, message, Typography, Space } from 'antd'
import { MobileOutlined, LockOutlined, UserOutlined, SafetyOutlined } from '@ant-design/icons'
import { useNavigate } from 'react-router-dom'
import { useAuthStore } from '@/store/authStore'
import { authApi } from '@/api/auth'

const { Title, Text } = Typography

export default function LoginPage() {
  const [loading, setLoading] = useState(false)
  const [smsLoading, setSmsLoading] = useState(false)
  const [countdown, setCountdown] = useState(0)
  const [activeTab, setActiveTab] = useState('sms')
  const timerRef = useRef<ReturnType<typeof setInterval> | null>(null)
  const navigate = useNavigate()
  const { setAuth } = useAuthStore()
  const [passwordForm] = Form.useForm()
  const [smsForm] = Form.useForm()

  const startCountdown = () => {
    setCountdown(60)
    timerRef.current = setInterval(() => {
      setCountdown((prev) => {
        if (prev <= 1) {
          if (timerRef.current) clearInterval(timerRef.current)
          return 0
        }
        return prev - 1
      })
    }, 1000)
  }

  const handleSendSMS = async () => {
    try {
      const { phone } = await smsForm.validateFields(['phone'])
      setSmsLoading(true)
      await authApi.sendSMSCode(phone)
      message.success('验证码已发送，请查收短信')
      startCountdown()
    } catch (err: any) {
      if (err?.response?.data?.detail) {
        message.error(err.response.data.detail)
      }
    } finally {
      setSmsLoading(false)
    }
  }

  const handleLogin = async (values: any, type: 'password' | 'sms') => {
    setLoading(true)
    try {
      let tokenData
      if (type === 'password') {
        tokenData = await authApi.loginByPassword(values)
      } else {
        tokenData = await authApi.loginBySMS(values)
      }

      // 获取用户信息
      const tempStore = useAuthStore.getState()
      tempStore.setAuth(tokenData.access_token, tokenData.refresh_token, {
        id: 0,
        username: '',
        full_name: '加载中...',
      })

      const userInfo = await authApi.getMe()
      setAuth(tokenData.access_token, tokenData.refresh_token, userInfo)
      message.success(`欢迎回来，${userInfo.full_name}！`)
      navigate('/dashboard')
    } catch (err: any) {
      const detail = err?.response?.data?.detail || '登录失败，请检查信息后重试'
      message.error(detail)
    } finally {
      setLoading(false)
    }
  }

  return (
    <div
      style={{
        minHeight: '100vh',
        background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        padding: 24,
      }}
    >
      <Card
        style={{
          width: '100%',
          maxWidth: 420,
          borderRadius: 16,
          boxShadow: '0 20px 60px rgba(0,0,0,0.3)',
        }}
        bodyStyle={{ padding: '40px 40px 32px' }}
      >
        <div style={{ textAlign: 'center', marginBottom: 32 }}>
          <div
            style={{
              width: 64,
              height: 64,
              background: 'linear-gradient(135deg, #667eea, #764ba2)',
              borderRadius: 16,
              display: 'inline-flex',
              alignItems: 'center',
              justifyContent: 'center',
              marginBottom: 16,
            }}
          >
            <span style={{ fontSize: 32 }}>🎓</span>
          </div>
          <Title level={3} style={{ margin: 0, color: '#1a1a2e' }}>
            教育管理平台
          </Title>
          <Text type="secondary">EduSys v2.0</Text>
        </div>

        <Tabs
          activeKey={activeTab}
          onChange={setActiveTab}
          centered
          items={[
            {
              key: 'sms',
              label: '手机验证码',
              children: (
                <Form
                  form={smsForm}
                  onFinish={(values) => handleLogin(values, 'sms')}
                  size="large"
                  style={{ marginTop: 8 }}
                >
                  <Form.Item
                    name="phone"
                    rules={[
                      { required: true, message: '请输入手机号' },
                      { pattern: /^1[3-9]\d{9}$/, message: '手机号格式不正确' },
                    ]}
                  >
                    <Input
                      prefix={<MobileOutlined style={{ color: '#bfbfbf' }} />}
                      placeholder="请输入手机号"
                      maxLength={11}
                    />
                  </Form.Item>
                  <Form.Item
                    name="code"
                    rules={[
                      { required: true, message: '请输入验证码' },
                      { len: 6, message: '验证码为6位数字' },
                    ]}
                  >
                    <Space.Compact style={{ width: '100%' }}>
                      <Input
                        prefix={<SafetyOutlined style={{ color: '#bfbfbf' }} />}
                        placeholder="请输入验证码"
                        maxLength={6}
                      />
                      <Button
                        onClick={handleSendSMS}
                        loading={smsLoading}
                        disabled={countdown > 0}
                        style={{ width: 120, flexShrink: 0 }}
                      >
                        {countdown > 0 ? `${countdown}秒后重发` : '获取验证码'}
                      </Button>
                    </Space.Compact>
                  </Form.Item>
                  <Form.Item style={{ marginBottom: 0, marginTop: 8 }}>
                    <Button
                      type="primary"
                      htmlType="submit"
                      loading={loading}
                      block
                      style={{
                        height: 44,
                        borderRadius: 8,
                        background: 'linear-gradient(135deg, #667eea, #764ba2)',
                        border: 'none',
                        fontSize: 16,
                        fontWeight: 500,
                      }}
                    >
                      登录
                    </Button>
                  </Form.Item>
                </Form>
              ),
            },
            {
              key: 'password',
              label: '账号密码',
              children: (
                <Form
                  form={passwordForm}
                  onFinish={(values) => handleLogin(values, 'password')}
                  size="large"
                  style={{ marginTop: 8 }}
                >
                  <Form.Item
                    name="username"
                    rules={[{ required: true, message: '请输入用户名' }]}
                  >
                    <Input
                      prefix={<UserOutlined style={{ color: '#bfbfbf' }} />}
                      placeholder="请输入用户名"
                    />
                  </Form.Item>
                  <Form.Item
                    name="password"
                    rules={[{ required: true, message: '请输入密码' }]}
                  >
                    <Input.Password
                      prefix={<LockOutlined style={{ color: '#bfbfbf' }} />}
                      placeholder="请输入密码"
                    />
                  </Form.Item>
                  <Form.Item style={{ marginBottom: 0, marginTop: 8 }}>
                    <Button
                      type="primary"
                      htmlType="submit"
                      loading={loading}
                      block
                      style={{
                        height: 44,
                        borderRadius: 8,
                        background: 'linear-gradient(135deg, #667eea, #764ba2)',
                        border: 'none',
                        fontSize: 16,
                        fontWeight: 500,
                      }}
                    >
                      登录
                    </Button>
                  </Form.Item>
                </Form>
              ),
            },
          ]}
        />

        <div style={{ textAlign: 'center', marginTop: 24 }}>
          <Text type="secondary" style={{ fontSize: 12 }}>
            登录即表示您同意平台服务协议和隐私政策
          </Text>
        </div>
      </Card>
    </div>
  )
}
