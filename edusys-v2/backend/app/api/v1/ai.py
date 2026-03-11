from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy.orm import Session
from sqlalchemy import func
from typing import Optional, List
from pydantic import BaseModel
from datetime import date, timedelta

from app.core.database import get_db
from app.core.deps import get_current_user
from app.models.person import Person, SchoolYear
from app.models.course import Course, CourseClass, CourseClassPerson
from app.models.attendance import AttendanceCode, AttendanceLogPerson
from app.models.markbook import MarkbookColumn, MarkbookEntry
from app.models.student import StudentEnrolment
from app.services import ai_service

router = APIRouter(prefix="/ai", tags=["AI功能"])


class ChatRequest(BaseModel):
    question: str
    context: Optional[str] = None


class ReportRequest(BaseModel):
    student_id: int
    school_year_id: Optional[int] = None


class TimetableOptimizeRequest(BaseModel):
    timetable_id: int


@router.post("/chat", summary="AI教学助手对话")
def ai_chat(
    data: ChatRequest,
    current_user: Person = Depends(get_current_user),
):
    answer = ai_service.ai_teacher_chat(data.question, data.context)
    return {"question": data.question, "answer": answer}


@router.post("/report/generate", summary="AI生成学生进度报告")
def generate_report(
    data: ReportRequest,
    db: Session = Depends(get_db),
    current_user: Person = Depends(get_current_user),
):
    student = db.query(Person).filter(Person.gibbonPersonID == data.student_id).first()
    if not student:
        raise HTTPException(status_code=404, detail="学生不存在")

    # 获取年级信息
    enrolment = (
        db.query(StudentEnrolment)
        .filter(StudentEnrolment.gibbonPersonID == data.student_id)
        .order_by(StudentEnrolment.gibbonStudentEnrolmentID.desc())
        .first()
    )
    year_group = enrolment.year_group.name if enrolment and enrolment.year_group else "未知年级"

    # 计算考勤率（最近30天）
    start_date = date.today() - timedelta(days=30)
    total_logs = (
        db.query(func.count(AttendanceLogPerson.gibbonAttendanceLogPersonID))
        .filter(
            AttendanceLogPerson.gibbonPersonID == data.student_id,
            AttendanceLogPerson.date >= start_date,
            AttendanceLogPerson.context == "School",
        )
        .scalar()
    )
    present_logs = (
        db.query(func.count(AttendanceLogPerson.gibbonAttendanceLogPersonID))
        .join(AttendanceCode, AttendanceLogPerson.gibbonAttendanceCodeID == AttendanceCode.gibbonAttendanceCodeID)
        .filter(
            AttendanceLogPerson.gibbonPersonID == data.student_id,
            AttendanceLogPerson.date >= start_date,
            AttendanceCode.type == "Present",
        )
        .scalar()
    )
    attendance_rate = (present_logs / total_logs * 100) if total_logs > 0 else 100.0

    # 获取各科成绩
    entries = (
        db.query(MarkbookEntry, MarkbookColumn, CourseClass, Course)
        .join(MarkbookColumn, MarkbookEntry.gibbonMarkbookColumnID == MarkbookColumn.gibbonMarkbookColumnID)
        .join(CourseClass, MarkbookColumn.gibbonCourseClassID == CourseClass.gibbonCourseClassID)
        .join(Course, CourseClass.gibbonCourseID == Course.gibbonCourseID)
        .filter(MarkbookEntry.gibbonPersonIDStudent == data.student_id)
        .all()
    )

    grade_summary = {}
    for entry, column, cls, course in entries:
        if entry.attainmentValueRaw:
            if course.name not in grade_summary:
                grade_summary[course.name] = []
            grade_summary[course.name].append(float(entry.attainmentValueRaw))

    avg_grades = {
        course: f"{sum(scores)/len(scores):.1f}分"
        for course, scores in grade_summary.items()
        if scores
    }

    report = ai_service.generate_student_report(
        student_name=f"{student.surname}{student.firstName}",
        year_group=year_group,
        attendance_rate=attendance_rate,
        grade_summary=avg_grades,
    )

    return {
        "student_name": f"{student.surname}{student.firstName}",
        "year_group": year_group,
        "attendance_rate": round(attendance_rate, 1),
        "grade_summary": avg_grades,
        "report": report,
    }


@router.get("/students/at-risk", summary="识别学习风险学生")
def identify_at_risk_students(
    school_year_id: int = Query(...),
    form_group_id: Optional[int] = Query(None),
    db: Session = Depends(get_db),
    current_user: Person = Depends(get_current_user),
):
    enrolment_query = db.query(StudentEnrolment).filter(
        StudentEnrolment.gibbonSchoolYearID == school_year_id
    )
    if form_group_id:
        enrolment_query = enrolment_query.filter(StudentEnrolment.gibbonFormGroupID == form_group_id)

    enrolments = enrolment_query.all()
    start_date = date.today() - timedelta(days=30)

    students_data = []
    for enrolment in enrolments:
        person = enrolment.person
        if not person:
            continue

        total = (
            db.query(func.count(AttendanceLogPerson.gibbonAttendanceLogPersonID))
            .filter(
                AttendanceLogPerson.gibbonPersonID == person.gibbonPersonID,
                AttendanceLogPerson.date >= start_date,
                AttendanceLogPerson.context == "School",
            )
            .scalar()
        )
        present = (
            db.query(func.count(AttendanceLogPerson.gibbonAttendanceLogPersonID))
            .join(AttendanceCode, AttendanceLogPerson.gibbonAttendanceCodeID == AttendanceCode.gibbonAttendanceCodeID)
            .filter(
                AttendanceLogPerson.gibbonPersonID == person.gibbonPersonID,
                AttendanceLogPerson.date >= start_date,
                AttendanceCode.type == "Present",
            )
            .scalar()
        )
        attendance_rate = (present / total * 100) if total > 0 else 100.0

        # 最近成绩平均
        recent_grades = (
            db.query(func.avg(MarkbookEntry.attainmentValueRaw))
            .filter(MarkbookEntry.gibbonPersonIDStudent == person.gibbonPersonID)
            .scalar()
        )
        grade_avg = float(recent_grades) if recent_grades else 0.0

        students_data.append(
            {
                "name": f"{person.surname}{person.firstName}",
                "attendance_rate": attendance_rate,
                "grade_avg": grade_avg,
                "trend": "下降" if attendance_rate < 80 or grade_avg < 60 else "稳定",
            }
        )

    at_risk = ai_service.identify_at_risk_students(students_data)
    return {"total_students": len(students_data), "at_risk_students": at_risk}


@router.get("/attendance/anomaly", summary="考勤异常预警")
def detect_attendance_anomaly(
    class_id: int = Query(...),
    db: Session = Depends(get_db),
    current_user: Person = Depends(get_current_user),
):
    cls = db.query(CourseClass).filter(CourseClass.gibbonCourseClassID == class_id).first()
    if not cls:
        raise HTTPException(status_code=404, detail="班级不存在")

    students = (
        db.query(CourseClassPerson, Person)
        .join(Person, CourseClassPerson.gibbonPersonID == Person.gibbonPersonID)
        .filter(
            CourseClassPerson.gibbonCourseClassID == class_id,
            CourseClassPerson.role == "Student",
        )
        .all()
    )

    start_date = date.today() - timedelta(days=30)
    attendance_data = []
    for _, person in students:
        total = (
            db.query(func.count(AttendanceLogPerson.gibbonAttendanceLogPersonID))
            .filter(
                AttendanceLogPerson.gibbonPersonID == person.gibbonPersonID,
                AttendanceLogPerson.date >= start_date,
            )
            .scalar()
        )
        absent = (
            db.query(func.count(AttendanceLogPerson.gibbonAttendanceLogPersonID))
            .join(AttendanceCode, AttendanceLogPerson.gibbonAttendanceCodeID == AttendanceCode.gibbonAttendanceCodeID)
            .filter(
                AttendanceLogPerson.gibbonPersonID == person.gibbonPersonID,
                AttendanceLogPerson.date >= start_date,
                AttendanceCode.type == "Absent",
            )
            .scalar()
        )
        attendance_rate = ((total - absent) / total * 100) if total > 0 else 100.0
        attendance_data.append(
            {
                "student_name": f"{person.surname}{person.firstName}",
                "attendance_rate": attendance_rate,
                "recent_absences": absent,
            }
        )

    analysis = ai_service.detect_attendance_anomalies(
        class_name=f"{cls.name}", attendance_data=attendance_data
    )
    return {
        "class_name": cls.name,
        "analysis_period": f"最近{30}天",
        "students": attendance_data,
        "ai_analysis": analysis,
    }


@router.post("/grades/analyze", summary="成绩趋势分析")
def analyze_grades(
    class_id: int = Query(...),
    db: Session = Depends(get_db),
    current_user: Person = Depends(get_current_user),
):
    cls = db.query(CourseClass).filter(CourseClass.gibbonCourseClassID == class_id).first()
    if not cls:
        raise HTTPException(status_code=404, detail="班级不存在")

    columns = (
        db.query(MarkbookColumn)
        .filter(MarkbookColumn.gibbonCourseClassID == class_id)
        .order_by(MarkbookColumn.date)
        .all()
    )

    grade_data = []
    for col in columns:
        avg = (
            db.query(func.avg(MarkbookEntry.attainmentValueRaw))
            .filter(MarkbookEntry.gibbonMarkbookColumnID == col.gibbonMarkbookColumnID)
            .scalar()
        )
        if avg:
            grade_data.append(
                {
                    "assessment": col.name,
                    "class_avg": float(avg),
                    "date": str(col.date) if col.date else None,
                }
            )

    course = cls.course
    analysis = ai_service.analyze_grade_trends(
        course_name=f"{course.name if course else '未知'} - {cls.name}",
        grade_data=grade_data,
    )
    return {
        "class_name": cls.name,
        "grade_data": grade_data,
        "ai_analysis": analysis,
    }


@router.post("/timetable/optimize", summary="课表优化建议")
def optimize_timetable(
    data: TimetableOptimizeRequest,
    db: Session = Depends(get_db),
    current_user: Person = Depends(get_current_user),
):
    from app.models.timetable import Timetable, TimetableDayRowClass

    tt = db.query(Timetable).filter(Timetable.gibbonTTID == data.timetable_id).first()
    if not tt:
        raise HTTPException(status_code=404, detail="课表不存在")

    # 检测教师冲突（同一时段同一教师出现在多个班级）
    conflicts = []
    rows = (
        db.query(TimetableDayRowClass, CourseClassPerson)
        .join(
            CourseClassPerson,
            TimetableDayRowClass.gibbonCourseClassID == CourseClassPerson.gibbonCourseClassID,
        )
        .filter(
            CourseClassPerson.role == "Teacher",
            TimetableDayRowClass.day.has(gibbonTTID=data.timetable_id),
        )
        .all()
    )

    teacher_slots: dict = {}
    for row, person in rows:
        key = (person.gibbonPersonID, row.gibbonTTDayID, row.gibbonTTColumnRowID)
        if key in teacher_slots:
            conflicts.append(
                f"教师(ID:{person.gibbonPersonID})在同一时段被分配到多个班级"
            )
        teacher_slots[key] = row

    suggestion = ai_service.suggest_timetable_optimization({"conflicts": conflicts})
    return {
        "timetable_name": tt.name,
        "conflicts_detected": len(conflicts),
        "conflicts": conflicts,
        "ai_suggestion": suggestion,
    }
