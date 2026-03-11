from fastapi import APIRouter, Depends, HTTPException, Query, status
from sqlalchemy.orm import Session
from sqlalchemy import or_
from typing import Optional, List

from app.core.database import get_db
from app.core.deps import get_current_user
from app.core.security import get_password_hash
from app.models.person import Person, SchoolYear
from app.models.student import StudentEnrolment
from app.models.attendance import AttendanceLogPerson
from app.schemas.student import (
    StudentCreate,
    StudentUpdate,
    StudentResponse,
    StudentListResponse,
    EnrolmentCreate,
    EnrolmentResponse,
)

router = APIRouter(prefix="/students", tags=["学生管理"])


@router.get("", response_model=StudentListResponse, summary="获取学生列表")
def list_students(
    page: int = Query(1, ge=1),
    page_size: int = Query(20, ge=1, le=100),
    search: Optional[str] = Query(None, description="搜索姓名/学号/用户名"),
    school_year_id: Optional[int] = Query(None),
    year_group_id: Optional[int] = Query(None),
    form_group_id: Optional[int] = Query(None),
    db: Session = Depends(get_db),
    current_user: Person = Depends(get_current_user),
):
    query = db.query(Person).filter(Person.status.in_(["Full", "Expected", "Left"]))

    # 通过入学记录过滤
    if school_year_id or year_group_id or form_group_id:
        query = query.join(
            StudentEnrolment,
            Person.gibbonPersonID == StudentEnrolment.gibbonPersonID,
        )
        if school_year_id:
            query = query.filter(StudentEnrolment.gibbonSchoolYearID == school_year_id)
        if year_group_id:
            query = query.filter(StudentEnrolment.gibbonYearGroupID == year_group_id)
        if form_group_id:
            query = query.filter(StudentEnrolment.gibbonFormGroupID == form_group_id)
    else:
        # 仅查询有入学记录的学生
        query = query.filter(
            Person.gibbonPersonID.in_(
                db.query(StudentEnrolment.gibbonPersonID)
            )
        )

    if search:
        query = query.filter(
            or_(
                Person.surname.ilike(f"%{search}%"),
                Person.firstName.ilike(f"%{search}%"),
                Person.preferredName.ilike(f"%{search}%"),
                Person.username.ilike(f"%{search}%"),
                Person.studentID.ilike(f"%{search}%"),
            )
        )

    total = query.count()
    students = query.offset((page - 1) * page_size).limit(page_size).all()

    return StudentListResponse(
        items=[StudentResponse.model_validate(s) for s in students],
        total=total,
        page=page,
        page_size=page_size,
    )


@router.post("", response_model=StudentResponse, status_code=status.HTTP_201_CREATED, summary="新增学生")
def create_student(
    data: StudentCreate,
    db: Session = Depends(get_db),
    current_user: Person = Depends(get_current_user),
):
    existing = db.query(Person).filter(Person.username == data.username).first()
    if existing:
        raise HTTPException(status_code=400, detail="用户名已存在")

    person = Person(
        username=data.username,
        passwordStrong=get_password_hash(data.password),
        surname=data.surname,
        firstName=data.firstName,
        preferredName=data.preferredName or data.firstName,
        officialName=f"{data.surname} {data.firstName}",
        gender=data.gender or "Unspecified",
        email=data.email,
        phone1=data.phone1,
        dob=data.dob,
        studentID=data.studentID,
        status="Full",
    )
    db.add(person)
    db.commit()
    db.refresh(person)
    return StudentResponse.model_validate(person)


@router.get("/{student_id}", response_model=StudentResponse, summary="获取学生详情")
def get_student(
    student_id: int,
    db: Session = Depends(get_db),
    current_user: Person = Depends(get_current_user),
):
    person = db.query(Person).filter(Person.gibbonPersonID == student_id).first()
    if not person:
        raise HTTPException(status_code=404, detail="学生不存在")
    return StudentResponse.model_validate(person)


@router.put("/{student_id}", response_model=StudentResponse, summary="更新学生信息")
def update_student(
    student_id: int,
    data: StudentUpdate,
    db: Session = Depends(get_db),
    current_user: Person = Depends(get_current_user),
):
    person = db.query(Person).filter(Person.gibbonPersonID == student_id).first()
    if not person:
        raise HTTPException(status_code=404, detail="学生不存在")

    for field, value in data.model_dump(exclude_unset=True).items():
        setattr(person, field, value)

    db.commit()
    db.refresh(person)
    return StudentResponse.model_validate(person)


@router.delete("/{student_id}", status_code=status.HTTP_204_NO_CONTENT, summary="删除学生")
def delete_student(
    student_id: int,
    db: Session = Depends(get_db),
    current_user: Person = Depends(get_current_user),
):
    person = db.query(Person).filter(Person.gibbonPersonID == student_id).first()
    if not person:
        raise HTTPException(status_code=404, detail="学生不存在")
    person.status = "Left"
    db.commit()


@router.get("/{student_id}/enrolments", response_model=List[EnrolmentResponse], summary="获取入学记录")
def get_student_enrolments(
    student_id: int,
    db: Session = Depends(get_db),
    current_user: Person = Depends(get_current_user),
):
    enrolments = (
        db.query(StudentEnrolment)
        .filter(StudentEnrolment.gibbonPersonID == student_id)
        .all()
    )
    return [EnrolmentResponse.model_validate(e) for e in enrolments]


@router.post("/{student_id}/enrolments", response_model=EnrolmentResponse, summary="添加入学记录")
def add_enrolment(
    student_id: int,
    data: EnrolmentCreate,
    db: Session = Depends(get_db),
    current_user: Person = Depends(get_current_user),
):
    person = db.query(Person).filter(Person.gibbonPersonID == student_id).first()
    if not person:
        raise HTTPException(status_code=404, detail="学生不存在")

    enrolment = StudentEnrolment(
        gibbonPersonID=student_id,
        gibbonSchoolYearID=data.gibbonSchoolYearID,
        gibbonYearGroupID=data.gibbonYearGroupID,
        gibbonFormGroupID=data.gibbonFormGroupID,
        dateStart=data.dateStart,
    )
    db.add(enrolment)
    db.commit()
    db.refresh(enrolment)
    return EnrolmentResponse.model_validate(enrolment)


@router.get("/{student_id}/attendance", summary="获取学生考勤记录")
def get_student_attendance(
    student_id: int,
    limit: int = Query(30),
    db: Session = Depends(get_db),
    current_user: Person = Depends(get_current_user),
):
    logs = (
        db.query(AttendanceLogPerson)
        .filter(
            AttendanceLogPerson.gibbonPersonID == student_id,
            AttendanceLogPerson.context == "School",
        )
        .order_by(AttendanceLogPerson.date.desc())
        .limit(limit)
        .all()
    )
    return [
        {
            "date": str(log.date),
            "code": log.attendance_code.name if log.attendance_code else None,
            "type": log.attendance_code.type if log.attendance_code else None,
            "reason": log.reason,
            "comment": log.comment,
        }
        for log in logs
    ]
