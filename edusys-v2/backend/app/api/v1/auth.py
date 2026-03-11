from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session
from datetime import timedelta

from app.core.database import get_db
from app.core.security import (
    verify_password,
    create_access_token,
    create_refresh_token,
    decode_token,
)
from app.core.config import settings
from app.core.deps import get_current_user
from app.models.person import Person
from app.schemas.auth import (
    LoginByPassword,
    LoginBySMS,
    SendSMSRequest,
    TokenResponse,
    RefreshTokenRequest,
    UserInfo,
)
from app.services.sms_service import send_verification_code, verify_otp

router = APIRouter(prefix="/auth", tags=["认证"])


def _create_tokens(person_id: int) -> TokenResponse:
    access_token = create_access_token({"sub": str(person_id)})
    refresh_token = create_refresh_token({"sub": str(person_id)})
    return TokenResponse(
        access_token=access_token,
        refresh_token=refresh_token,
        expires_in=settings.ACCESS_TOKEN_EXPIRE_MINUTES * 60,
    )


@router.post("/login/password", response_model=TokenResponse, summary="用户名密码登录")
def login_by_password(data: LoginByPassword, db: Session = Depends(get_db)):
    person = db.query(Person).filter(Person.username == data.username).first()
    if not person:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="用户名或密码错误")

    # 支持旧版 MD5 密码和新版 bcrypt
    password_valid = False
    if person.passwordStrong:
        password_valid = verify_password(data.password, person.passwordStrong)
    elif person.password:
        import hashlib
        password_valid = (
            hashlib.md5(data.password.encode()).hexdigest() == person.password
        )

    if not password_valid:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="用户名或密码错误")

    if person.status not in ("Full", "Expected"):
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="账号已禁用或未激活")

    return _create_tokens(person.gibbonPersonID)


@router.post("/sms/send", summary="发送短信验证码")
def send_sms_code(data: SendSMSRequest, db: Session = Depends(get_db)):
    person = db.query(Person).filter(Person.phone1 == data.phone).first()
    if not person:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="该手机号未注册")
    success = send_verification_code(data.phone)
    if not success:
        raise HTTPException(status_code=status.HTTP_500_INTERNAL_SERVER_ERROR, detail="短信发送失败，请稍后重试")
    return {"message": "验证码已发送", "expires_in": 300}


@router.post("/login/sms", response_model=TokenResponse, summary="手机短信验证码登录")
def login_by_sms(data: LoginBySMS, db: Session = Depends(get_db)):
    if not verify_otp(data.phone, data.code):
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="验证码错误或已过期")

    person = db.query(Person).filter(Person.phone1 == data.phone).first()
    if not person:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="用户不存在")

    if person.status not in ("Full", "Expected"):
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="账号已禁用")

    return _create_tokens(person.gibbonPersonID)


@router.post("/refresh", response_model=TokenResponse, summary="刷新访问令牌")
def refresh_token(data: RefreshTokenRequest):
    payload = decode_token(data.refresh_token)
    if not payload or payload.get("type") != "refresh":
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="无效的刷新令牌")
    person_id = payload.get("sub")
    return _create_tokens(int(person_id))


@router.post("/logout", summary="登出")
def logout():
    return {"message": "已成功登出"}


@router.get("/me", response_model=UserInfo, summary="获取当前用户信息")
def get_me(current_user: Person = Depends(get_current_user)):
    return UserInfo(
        id=current_user.gibbonPersonID,
        username=current_user.username,
        full_name=f"{current_user.surname}{current_user.firstName}",
        email=current_user.email,
        phone=current_user.phone1,
        role=current_user.gibbonRoleIDAll,
        avatar=current_user.image_240,
    )
