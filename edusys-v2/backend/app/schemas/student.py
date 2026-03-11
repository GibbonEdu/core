from pydantic import BaseModel, Field, EmailStr
from typing import Optional, List
from datetime import date


class StudentBase(BaseModel):
    surname: str = Field(..., description="姓")
    firstName: str = Field(..., description="名")
    preferredName: Optional[str] = None
    gender: Optional[str] = "Unspecified"
    email: Optional[str] = None
    phone1: Optional[str] = None
    dob: Optional[date] = None
    studentID: Optional[str] = None


class StudentCreate(StudentBase):
    username: str
    password: str


class StudentUpdate(BaseModel):
    surname: Optional[str] = None
    firstName: Optional[str] = None
    preferredName: Optional[str] = None
    gender: Optional[str] = None
    email: Optional[str] = None
    phone1: Optional[str] = None
    dob: Optional[date] = None
    status: Optional[str] = None


class StudentResponse(BaseModel):
    gibbonPersonID: int
    surname: str
    firstName: str
    preferredName: Optional[str]
    gender: Optional[str]
    email: Optional[str]
    phone1: Optional[str]
    dob: Optional[date]
    studentID: Optional[str]
    status: str
    image_240: Optional[str]

    model_config = {"from_attributes": True}


class StudentListResponse(BaseModel):
    items: List[StudentResponse]
    total: int
    page: int
    page_size: int


class EnrolmentCreate(BaseModel):
    gibbonSchoolYearID: int
    gibbonYearGroupID: int
    gibbonFormGroupID: Optional[int] = None
    dateStart: Optional[date] = None


class EnrolmentResponse(BaseModel):
    gibbonStudentEnrolmentID: int
    gibbonPersonID: int
    gibbonSchoolYearID: int
    gibbonYearGroupID: int
    gibbonFormGroupID: Optional[int]
    dateStart: Optional[date]
    dateEnd: Optional[date]

    model_config = {"from_attributes": True}
