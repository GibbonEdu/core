from pydantic import BaseModel, Field
from typing import Optional, List


class CourseBase(BaseModel):
    name: str = Field(..., description="课程名称")
    nameShort: str = Field(..., description="课程简称")
    description: Optional[str] = None
    gibbonYearGroupIDList: Optional[str] = None


class CourseCreate(CourseBase):
    gibbonSchoolYearID: int


class CourseUpdate(BaseModel):
    name: Optional[str] = None
    nameShort: Optional[str] = None
    description: Optional[str] = None
    gibbonYearGroupIDList: Optional[str] = None


class CourseResponse(BaseModel):
    gibbonCourseID: int
    gibbonSchoolYearID: int
    name: str
    nameShort: str
    description: Optional[str]
    gibbonYearGroupIDList: Optional[str]

    model_config = {"from_attributes": True}


class CourseClassBase(BaseModel):
    name: str = Field(..., description="班级名称")
    nameShort: str = Field(..., description="班级简称")
    enrolmentMin: Optional[int] = None
    enrolmentMax: Optional[int] = None


class CourseClassCreate(CourseClassBase):
    pass


class CourseClassResponse(BaseModel):
    gibbonCourseClassID: int
    gibbonCourseID: int
    name: str
    nameShort: str
    enrolmentMin: Optional[int]
    enrolmentMax: Optional[int]
    attendance: str

    model_config = {"from_attributes": True}


class EnrollStudentRequest(BaseModel):
    gibbonPersonID: int
    role: str = "Student"


class ClassMemberResponse(BaseModel):
    gibbonCourseClassPersonID: int
    gibbonPersonID: int
    role: str
    student_name: Optional[str]
    student_id: Optional[str]

    model_config = {"from_attributes": True}
