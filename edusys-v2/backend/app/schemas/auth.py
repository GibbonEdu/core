from pydantic import BaseModel, Field
from typing import Optional


class LoginByPassword(BaseModel):
    username: str = Field(..., description="用户名")
    password: str = Field(..., description="密码")


class LoginBySMS(BaseModel):
    phone: str = Field(..., description="手机号", pattern=r"^1[3-9]\d{9}$")
    code: str = Field(..., description="短信验证码", min_length=6, max_length=6)


class SendSMSRequest(BaseModel):
    phone: str = Field(..., description="手机号", pattern=r"^1[3-9]\d{9}$")


class TokenResponse(BaseModel):
    access_token: str
    refresh_token: str
    token_type: str = "bearer"
    expires_in: int


class RefreshTokenRequest(BaseModel):
    refresh_token: str


class UserInfo(BaseModel):
    id: int
    username: str
    full_name: str
    email: Optional[str]
    phone: Optional[str]
    role: Optional[str]
    avatar: Optional[str]

    model_config = {"from_attributes": True}
