from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy.orm import Session
from sqlalchemy import func
from typing import Optional, List
from datetime import date, timedelta

from app.core.database import get_db
from app.core.deps import get_current_user
from app.models.person import Person
from app.models.attendance import AttendanceCode, AttendanceLogPerson, AttendanceLogCourseClass
from app.models.course import CourseClass, CourseClassPerson
from app.schemas.attendance import (
    AttendanceCodeResponse,
    AttendanceLogCreate,
    AttendanceLogResponse,
    BulkAttendanceCreate,
    AttendanceStatistics,
)

router = APIRouter(prefix="/attendance", tags=["考勤管理"])


@router.get("/codes", response_model=List[AttendanceCodeResponse], summary="获取考勤代码")
def list_attendance_codes(
    db: Session = Depends(get_db),
    current_user: Person = Depends(get_current_user),
):
    codes = db.query(AttendanceCode).filter(AttendanceCode.active == "Y").all()
    return [AttendanceCodeResponse.model_validate(c) for c in codes]


@router.get("/daily", summary="获取全校当日考勤")
def get_daily_attendance(
    attendance_date: date = Query(default=date.today()),
    form_group_id: Optional[int] = Query(None),
    db: Session = Depends(get_db),
    current_user: Person = Depends(get_current_user),
):
    query = (
        db.query(AttendanceLogPerson, Person, AttendanceCode)
        .join(Person, AttendanceLogPerson.gibbonPersonID == Person.gibbonPersonID)
        .outerjoin(AttendanceCode, AttendanceLogPerson.gibbonAttendanceCodeID == AttendanceCode.gibbonAttendanceCodeID)
        .filter(
            AttendanceLogPerson.date == attendance_date,
            AttendanceLogPerson.context == "School",
        )
    )
    results = query.all()
    return [
        {
            "gibbonPersonID": log.gibbonPersonID,
            "student_name": f"{person.surname}{person.firstName}",
            "date": str(log.date),
            "code": code.name if code else None,
            "code_type": code.type if code else None,
            "reason": log.reason,
        }
        for log, person, code in results
    ]


@router.post("/daily", summary="批量提交当日考勤")
def submit_daily_attendance(
    data: BulkAttendanceCreate,
    db: Session = Depends(get_db),
    current_user: Person = Depends(get_current_user),
):
    created = 0
    for record in data.records:
        existing = (
            db.query(AttendanceLogPerson)
            .filter(
                AttendanceLogPerson.gibbonPersonID == record.gibbonPersonID,
                AttendanceLogPerson.date == data.date,
                AttendanceLogPerson.context == data.context,
            )
            .first()
        )
        if existing:
            existing.gibbonAttendanceCodeID = record.gibbonAttendanceCodeID
            existing.reason = record.reason
            existing.comment = record.comment
            existing.gibbonPersonIDTaken = current_user.gibbonPersonID
        else:
            log = AttendanceLogPerson(
                gibbonPersonID=record.gibbonPersonID,
                gibbonAttendanceCodeID=record.gibbonAttendanceCodeID,
                date=data.date,
                context=data.context,
                reason=record.reason,
                comment=record.comment,
                gibbonPersonIDTaken=current_user.gibbonPersonID,
            )
            db.add(log)
            created += 1
    db.commit()
    return {"message": f"考勤已保存，共{len(data.records)}条记录"}


@router.get("/class/{class_id}", summary="获取班级考勤记录")
def get_class_attendance(
    class_id: int,
    attendance_date: date = Query(default=date.today()),
    db: Session = Depends(get_db),
    current_user: Person = Depends(get_current_user),
):
    # 获取班级所有学生
    members = (
        db.query(CourseClassPerson, Person)
        .join(Person, CourseClassPerson.gibbonPersonID == Person.gibbonPersonID)
        .filter(
            CourseClassPerson.gibbonCourseClassID == class_id,
            CourseClassPerson.role == "Student",
        )
        .all()
    )

    # 获取当日考勤记录
    logs = {
        log.gibbonPersonID: log
        for log in db.query(AttendanceLogPerson).filter(
            AttendanceLogPerson.gibbonCourseClassID == class_id,
            AttendanceLogPerson.date == attendance_date,
        ).all()
    }

    return [
        {
            "gibbonPersonID": person.gibbonPersonID,
            "student_name": f"{person.surname}{person.firstName}",
            "student_id": person.studentID,
            "attendance_code_id": logs[person.gibbonPersonID].gibbonAttendanceCodeID
            if person.gibbonPersonID in logs
            else None,
            "reason": logs[person.gibbonPersonID].reason
            if person.gibbonPersonID in logs
            else None,
            "taken": person.gibbonPersonID in logs,
        }
        for _, person in members
    ]


@router.post("/class/{class_id}", summary="提交课堂考勤")
def submit_class_attendance(
    class_id: int,
    data: BulkAttendanceCreate,
    db: Session = Depends(get_db),
    current_user: Person = Depends(get_current_user),
):
    # 更新考勤头记录
    header = (
        db.query(AttendanceLogCourseClass)
        .filter(
            AttendanceLogCourseClass.gibbonCourseClassID == class_id,
            AttendanceLogCourseClass.date == data.date,
        )
        .first()
    )
    if not header:
        header = AttendanceLogCourseClass(
            gibbonCourseClassID=class_id,
            date=data.date,
            gibbonPersonIDTaken=current_user.gibbonPersonID,
        )
        db.add(header)

    for record in data.records:
        existing = (
            db.query(AttendanceLogPerson)
            .filter(
                AttendanceLogPerson.gibbonPersonID == record.gibbonPersonID,
                AttendanceLogPerson.gibbonCourseClassID == class_id,
                AttendanceLogPerson.date == data.date,
            )
            .first()
        )
        if existing:
            existing.gibbonAttendanceCodeID = record.gibbonAttendanceCodeID
            existing.reason = record.reason
        else:
            log = AttendanceLogPerson(
                gibbonPersonID=record.gibbonPersonID,
                gibbonAttendanceCodeID=record.gibbonAttendanceCodeID,
                gibbonCourseClassID=class_id,
                date=data.date,
                context="Class",
                reason=record.reason,
                gibbonPersonIDTaken=current_user.gibbonPersonID,
            )
            db.add(log)

    db.commit()
    return {"message": "课堂考勤已保存"}


@router.get("/student/{student_id}", summary="获取学生考勤历史")
def get_student_attendance(
    student_id: int,
    days: int = Query(30),
    db: Session = Depends(get_db),
    current_user: Person = Depends(get_current_user),
):
    start_date = date.today() - timedelta(days=days)
    logs = (
        db.query(AttendanceLogPerson, AttendanceCode)
        .outerjoin(AttendanceCode, AttendanceLogPerson.gibbonAttendanceCodeID == AttendanceCode.gibbonAttendanceCodeID)
        .filter(
            AttendanceLogPerson.gibbonPersonID == student_id,
            AttendanceLogPerson.date >= start_date,
            AttendanceLogPerson.context == "School",
        )
        .order_by(AttendanceLogPerson.date.desc())
        .all()
    )
    return [
        {
            "date": str(log.date),
            "code": code.name if code else None,
            "type": code.type if code else None,
            "reason": log.reason,
        }
        for log, code in logs
    ]


@router.get("/statistics", summary="考勤统计报表")
def get_attendance_statistics(
    school_year_id: int = Query(...),
    form_group_id: Optional[int] = Query(None),
    year_group_id: Optional[int] = Query(None),
    db: Session = Depends(get_db),
    current_user: Person = Depends(get_current_user),
):
    from app.models.student import StudentEnrolment
    from app.models.person import SchoolYear

    school_year = db.query(SchoolYear).filter(SchoolYear.gibbonSchoolYearID == school_year_id).first()
    if not school_year:
        raise HTTPException(status_code=404, detail="学年不存在")

    enrolment_query = db.query(StudentEnrolment.gibbonPersonID).filter(
        StudentEnrolment.gibbonSchoolYearID == school_year_id
    )
    if form_group_id:
        enrolment_query = enrolment_query.filter(StudentEnrolment.gibbonFormGroupID == form_group_id)
    if year_group_id:
        enrolment_query = enrolment_query.filter(StudentEnrolment.gibbonYearGroupID == year_group_id)

    student_ids = [row[0] for row in enrolment_query.all()]

    # 统计各类型考勤数量
    type_counts = (
        db.query(AttendanceCode.type, func.count(AttendanceLogPerson.gibbonAttendanceLogPersonID))
        .join(AttendanceCode, AttendanceLogPerson.gibbonAttendanceCodeID == AttendanceCode.gibbonAttendanceCodeID)
        .filter(
            AttendanceLogPerson.gibbonPersonID.in_(student_ids),
            AttendanceLogPerson.date.between(school_year.firstDay, school_year.lastDay),
            AttendanceLogPerson.context == "School",
        )
        .group_by(AttendanceCode.type)
        .all()
    )

    counts = {t: c for t, c in type_counts}
    total = sum(counts.values())
    present = counts.get("Present", 0)

    return {
        "school_year": school_year.name,
        "student_count": len(student_ids),
        "total_records": total,
        "present_count": present,
        "absent_count": counts.get("Absent", 0),
        "late_count": counts.get("Late", 0),
        "attendance_rate": round(present / total * 100, 2) if total > 0 else 0,
        "breakdown": counts,
    }
