from pydantic import BaseModel, Field
from typing import Optional, List
from datetime import date, datetime
from decimal import Decimal


class MarkbookColumnCreate(BaseModel):
    name: str = Field(..., description="评估项目名称")
    description: Optional[str] = None
    type: str = "Assessment"
    attainment: str = "Y"
    effort: str = "N"
    comment: str = "N"
    date: Optional[date] = None
    viewableStudents: str = "N"
    viewableParents: str = "N"


class MarkbookColumnUpdate(BaseModel):
    name: Optional[str] = None
    description: Optional[str] = None
    date: Optional[date] = None
    viewableStudents: Optional[str] = None
    viewableParents: Optional[str] = None
    complete: Optional[str] = None


class MarkbookColumnResponse(BaseModel):
    gibbonMarkbookColumnID: int
    gibbonCourseClassID: int
    name: str
    description: Optional[str]
    type: str
    attainment: str
    effort: str
    date: Optional[date]
    complete: str
    viewableStudents: str
    viewableParents: str

    model_config = {"from_attributes": True}


class MarkbookEntryUpdate(BaseModel):
    attainmentValue: Optional[str] = None
    attainmentValueRaw: Optional[Decimal] = None
    effortValue: Optional[str] = None
    comment: Optional[str] = None


class MarkbookEntryResponse(BaseModel):
    gibbonMarkbookEntryID: int
    gibbonMarkbookColumnID: int
    gibbonPersonIDStudent: int
    attainmentValue: Optional[str]
    attainmentValueRaw: Optional[Decimal]
    effortValue: Optional[str]
    comment: Optional[str]
    timestamp: Optional[datetime]
    student_name: Optional[str]

    model_config = {"from_attributes": True}


class BulkMarkbookEntryItem(BaseModel):
    gibbonPersonIDStudent: int
    attainmentValue: Optional[str] = None
    attainmentValueRaw: Optional[Decimal] = None
    effortValue: Optional[str] = None
    comment: Optional[str] = None


class BulkMarkbookEntryCreate(BaseModel):
    entries: List[BulkMarkbookEntryItem]


class StudentGradeSummary(BaseModel):
    gibbonPersonID: int
    student_name: str
    course_name: str
    class_name: str
    columns: List[dict]
    average_attainment: Optional[float]
