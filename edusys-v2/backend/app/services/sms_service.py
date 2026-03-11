import random
import string
import json
from datetime import datetime, timedelta
from typing import Optional
import redis
from app.core.config import settings

redis_client = redis.from_url(settings.REDIS_URL, decode_responses=True)

SMS_CODE_TTL = 300  # 5分钟
SMS_CODE_PREFIX = "sms_code:"


def generate_otp(length: int = 6) -> str:
    return "".join(random.choices(string.digits, k=length))


def store_otp(phone: str, code: str) -> None:
    key = f"{SMS_CODE_PREFIX}{phone}"
    redis_client.setex(key, SMS_CODE_TTL, code)


def verify_otp(phone: str, code: str) -> bool:
    key = f"{SMS_CODE_PREFIX}{phone}"
    stored = redis_client.get(key)
    if stored and stored == code:
        redis_client.delete(key)
        return True
    return False


def send_sms(phone: str, code: str) -> bool:
    """
    通过阿里云短信发送验证码
    文档: https://help.aliyun.com/document_detail/101414.html
    """
    if not settings.ALIYUN_ACCESS_KEY_ID:
        # 开发模式：直接打印验证码
        print(f"[开发模式] 手机号 {phone} 的验证码: {code}")
        return True

    try:
        from aliyunsdkcore.client import AcsClient
        from aliyunsdkcore.request import CommonRequest

        client = AcsClient(
            settings.ALIYUN_ACCESS_KEY_ID,
            settings.ALIYUN_ACCESS_KEY_SECRET,
            "cn-hangzhou",
        )
        request = CommonRequest()
        request.set_accept_format("json")
        request.set_domain("dysmsapi.aliyuncs.com")
        request.set_method("POST")
        request.set_protocol_type("https")
        request.set_version("2017-05-25")
        request.set_action_name("SendSms")
        request.add_query_param("RegionId", "cn-hangzhou")
        request.add_query_param("PhoneNumbers", phone)
        request.add_query_param("SignName", settings.ALIYUN_SMS_SIGN_NAME)
        request.add_query_param("TemplateCode", settings.ALIYUN_SMS_TEMPLATE_CODE)
        request.add_query_param("TemplateParam", json.dumps({"code": code}))

        response = client.do_action_with_exception(request)
        result = json.loads(response)
        return result.get("Code") == "OK"
    except Exception as e:
        print(f"短信发送失败: {e}")
        return False


def send_verification_code(phone: str) -> bool:
    code = generate_otp()
    store_otp(phone, code)
    return send_sms(phone, code)
