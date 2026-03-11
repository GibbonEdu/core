from pydantic import BaseModel, Field
from typing import Optional, List
from datetime import date, datetime


class AttendanceCodeResponse(BaseModel):
    gibbonAttendanceCodeID: int
    name: str
    nameShort: str
    type: str
    scope: str
    colour: Optional[str]
    colourBackground: Optional[str]

    model_config = {"from_attributes": True}


class AttendanceLogCreate(BaseModel):
    gibbonPersonID: int
    gibbonAttendanceCodeID: int
    date: date
    reason: Optional[str] = None
    comment: Optional[str] = None
    context: str = "School"


class AttendanceLogResponse(BaseModel):
    gibbonAttendanceLogPersonID: int
    gibbonPersonID: int
    gibbonAttendanceCodeID: int
    date: date
    reason: Optional[str]
    comment: Optional[str]
    context: str
    timestampTaken: Optional[datetime]
    code_name: Optional[str]
    code_type: Optional[str]

    model_config = {"from_attributes": True}


class BulkAttendanceCreate(BaseModel):
    date: date
    context: str = "School"
    records: List[AttendanceLogCreate]


class AttendanceStatistics(BaseModel):
    period: str
    total_days: int
    present_count: int
    absent_count: int
    late_count: int
    attendance_rate: float


class ClassAttendanceSummary(BaseModel):
    gibbonCourseClassID: int
    date: date
    total_students: int
    present_count: int
    absent_count: int
    late_count: int
    taken: bool
