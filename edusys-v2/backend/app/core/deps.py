from typing import Optional
from fastapi import Depends, HTTPException, status
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from sqlalchemy.orm import Session
from app.core.database import get_db
from app.core.security import decode_token
from app.models.person import Person

security = HTTPBearer()


def get_current_user(
    credentials: HTTPAuthorizationCredentials = Depends(security),
    db: Session = Depends(get_db),
) -> Person:
    token = credentials.credentials
    payload = decode_token(token)
    if not payload or payload.get("type") != "access":
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="无效或已过期的令牌",
            headers={"WWW-Authenticate": "Bearer"},
        )
    person_id = payload.get("sub")
    if not person_id:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="令牌数据无效")

    person = db.query(Person).filter(Person.gibbonPersonID == person_id).first()
    if not person:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="用户不存在")
    if person.status != "Full":
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="账号已禁用")
    return person


def get_current_active_user(current_user: Person = Depends(get_current_user)) -> Person:
    return current_user


def require_role(required_roles: list[str]):
    def role_checker(current_user: Person = Depends(get_current_user)) -> Person:
        if not any(role in required_roles for role in current_user.role_names):
            raise HTTPException(
                status_code=status.HTTP_403_FORBIDDEN, detail="权限不足"
            )
        return current_user

    return role_checker
