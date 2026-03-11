# EduSys v2.0 - 现代化教育管理平台

基于 **Python FastAPI + React + Ant Design** 重写，所有技术栈均可在**中国大陆直接访问**，无需VPN。

## 技术栈

| 层次 | 技术 | 说明 |
|------|------|------|
| 后端 | Python 3.11 + FastAPI | 高性能异步API |
| ORM | SQLAlchemy 2.0 | 兼容现有MySQL数据库 |
| 前端 | React 18 + TypeScript | 现代前端工程化 |
| UI | Ant Design 5.x | 阿里出品，国内首选 |
| AI | DeepSeek API | 国内可直接访问 |
| 短信 | 阿里云SMS | 替代Google SMS |
| 支付 | 支付宝 + 微信支付 | 替代PayPal/Stripe |
| 缓存 | Redis | Session + 接口缓存 |

## 快速启动

### 方式一：Docker（推荐）

```bash
cd edusys-v2
cp backend/.env.example backend/.env
# 编辑 .env 配置数据库连接和 API Key
docker-compose up -d
```

访问：
- 前端：http://localhost:5173
- 后端API文档：http://localhost:8000/docs

### 方式二：本地开发

**后端：**
```bash
cd backend
python -m venv venv
source venv/bin/activate  # Windows: venv\Scripts\activate
pip install -r requirements.txt -i https://mirrors.aliyun.com/pypi/simple/
cp .env.example .env
# 编辑 .env 配置
uvicorn app.main:app --reload
```

**前端：**
```bash
cd frontend
npm install --registry https://registry.npmmirror.com
npm run dev
```

## 核心功能

### 已实现（Phase 1-3）
- **认证系统**：手机号+短信验证码登录（替代 Google OAuth）+ JWT
- **学生管理**：列表、详情、创建、编辑、软删除、考勤查看
- **课程管理**：课程CRUD、班级管理、学生入班
- **考勤管理**：日常考勤录入、课堂考勤、统计报表
- **成绩管理**：评估项目、批量录入成绩、学生成绩汇总
- **AI功能**：
  - AI教学助手对话（DeepSeek驱动）
  - 学习风险学生识别
  - 学生进度报告自动生成
  - 成绩趋势分析
  - 考勤异常预警

### 待实现（Phase 4）
- 支付宝/微信支付集成（替换Finance模块）
- 阿里云OSS文件上传
- 企业微信/钉钉消息推送
- 移动端响应式优化

## 环境变量配置

主要配置项（在 `backend/.env` 中设置）：

```env
# 数据库（连接现有 Gibbon 数据库）
DATABASE_URL=mysql+pymysql://user:password@host:3306/gibbon

# AI功能（申请地址：platform.deepseek.com）
DEEPSEEK_API_KEY=sk-xxx

# 短信验证码（阿里云控制台申请）
ALIYUN_ACCESS_KEY_ID=xxx
ALIYUN_ACCESS_KEY_SECRET=xxx
ALIYUN_SMS_SIGN_NAME=教育管理平台
ALIYUN_SMS_TEMPLATE_CODE=SMS_xxx
```

## API 文档

启动后访问 http://localhost:8000/docs 查看完整 OpenAPI 文档。

## 目录结构

```
edusys-v2/
├── backend/
│   ├── app/
│   │   ├── api/v1/          # API路由（auth/students/courses/attendance/markbook/ai）
│   │   ├── models/          # SQLAlchemy模型（映射现有gibbon_*表）
│   │   ├── schemas/         # Pydantic请求/响应模型
│   │   ├── services/        # 业务逻辑（sms_service, ai_service）
│   │   ├── core/            # 配置、安全、数据库连接
│   │   └── main.py          # FastAPI应用入口
│   └── requirements.txt
├── frontend/
│   └── src/
│       ├── pages/           # 页面组件（auth/dashboard/students/...）
│       ├── components/      # 公共组件（Layout）
│       ├── api/             # API客户端（axios + JWT拦截）
│       └── store/           # 状态管理（Zustand）
└── docker-compose.yml
```
