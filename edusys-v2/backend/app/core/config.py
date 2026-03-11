from pydantic_settings import BaseSettings
from typing import List
import json


class Settings(BaseSettings):
    # 应用配置
    APP_NAME: str = "教育管理平台"
    APP_VERSION: str = "2.0.0"
    DEBUG: bool = False
    CORS_ORIGINS: List[str] = ["http://localhost:5173", "http://localhost:3000"]

    # 数据库配置
    DATABASE_URL: str = "mysql+pymysql://root:password@localhost:3306/gibbon"

    # JWT配置
    SECRET_KEY: str = "change-me-in-production"
    ALGORITHM: str = "HS256"
    ACCESS_TOKEN_EXPIRE_MINUTES: int = 120
    REFRESH_TOKEN_EXPIRE_DAYS: int = 7

    # Redis配置
    REDIS_URL: str = "redis://localhost:6379/0"

    # DeepSeek AI配置
    DEEPSEEK_API_KEY: str = ""
    DEEPSEEK_BASE_URL: str = "https://api.deepseek.com"
    DEEPSEEK_MODEL: str = "deepseek-chat"

    # 阿里云短信配置
    ALIYUN_ACCESS_KEY_ID: str = ""
    ALIYUN_ACCESS_KEY_SECRET: str = ""
    ALIYUN_SMS_SIGN_NAME: str = "教育管理平台"
    ALIYUN_SMS_TEMPLATE_CODE: str = ""

    # 支付宝配置
    ALIPAY_APP_ID: str = ""
    ALIPAY_PRIVATE_KEY: str = ""
    ALIPAY_PUBLIC_KEY: str = ""

    # 微信支付配置
    WECHAT_APP_ID: str = ""
    WECHAT_MCH_ID: str = ""
    WECHAT_API_KEY: str = ""

    # 阿里云OSS配置
    OSS_ACCESS_KEY_ID: str = ""
    OSS_ACCESS_KEY_SECRET: str = ""
    OSS_BUCKET_NAME: str = "edusys-files"
    OSS_ENDPOINT: str = "oss-cn-hangzhou.aliyuncs.com"

    model_config = {"env_file": ".env", "env_file_encoding": "utf-8"}


settings = Settings()
