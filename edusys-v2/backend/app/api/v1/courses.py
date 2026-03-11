from fastapi import APIRouter, Depends, HTTPException, Query, status
from sqlalchemy.orm import Session
from typing import Optional, List

from app.core.database import get_db
from app.core.deps import get_current_user
from app.models.person import Person, SchoolYear
from app.models.course import Course, CourseClass, CourseClassPerson
from app.schemas.course import (
    CourseCreate,
    CourseUpdate,
    CourseResponse,
    CourseClassCreate,
    CourseClassResponse,
    EnrollStudentRequest,
    ClassMemberResponse,
)

router = APIRouter(prefix="/courses", tags=["课程管理"])


@router.get("", response_model=List[CourseResponse], summary="获取课程列表")
def list_courses(
    school_year_id: Optional[int] = Query(None),
    search: Optional[str] = Query(None),
    db: Session = Depends(get_db),
    current_user: Person = Depends(get_current_user),
):
    query = db.query(Course)
    if school_year_id:
        query = query.filter(Course.gibbonSchoolYearID == school_year_id)
    if search:
        query = query.filter(Course.name.ilike(f"%{search}%"))
    return [CourseResponse.model_validate(c) for c in query.all()]


@router.post("", response_model=CourseResponse, status_code=status.HTTP_201_CREATED, summary="新增课程")
def create_course(
    data: CourseCreate,
    db: Session = Depends(get_db),
    current_user: Person = Depends(get_current_user),
):
    course = Course(**data.model_dump())
    db.add(course)
    db.commit()
    db.refresh(course)
    return CourseResponse.model_validate(course)


@router.get("/{course_id}", response_model=CourseResponse, summary="获取课程详情")
def get_course(
    course_id: int,
    db: Session = Depends(get_db),
    current_user: Person = Depends(get_current_user),
):
    course = db.query(Course).filter(Course.gibbonCourseID == course_id).first()
    if not course:
        raise HTTPException(status_code=404, detail="课程不存在")
    return CourseResponse.model_validate(course)


@router.put("/{course_id}", response_model=CourseResponse, summary="更新课程")
def update_course(
    course_id: int,
    data: CourseUpdate,
    db: Session = Depends(get_db),
    current_user: Person = Depends(get_current_user),
):
    course = db.query(Course).filter(Course.gibbonCourseID == course_id).first()
    if not course:
        raise HTTPException(status_code=404, detail="课程不存在")
    for field, value in data.model_dump(exclude_unset=True).items():
        setattr(course, field, value)
    db.commit()
    db.refresh(course)
    return CourseResponse.model_validate(course)


@router.get("/{course_id}/classes", response_model=List[CourseClassResponse], summary="获取班级列表")
def list_classes(
    course_id: int,
    db: Session = Depends(get_db),
    current_user: Person = Depends(get_current_user),
):
    classes = db.query(CourseClass).filter(CourseClass.gibbonCourseID == course_id).all()
    return [CourseClassResponse.model_validate(c) for c in classes]


@router.post("/{course_id}/classes", response_model=CourseClassResponse, status_code=status.HTTP_201_CREATED, summary="新增班级")
def create_class(
    course_id: int,
    data: CourseClassCreate,
    db: Session = Depends(get_db),
    current_user: Person = Depends(get_current_user),
):
    course = db.query(Course).filter(Course.gibbonCourseID == course_id).first()
    if not course:
        raise HTTPException(status_code=404, detail="课程不存在")
    cls = CourseClass(gibbonCourseID=course_id, **data.model_dump())
    db.add(cls)
    db.commit()
    db.refresh(cls)
    return CourseClassResponse.model_validate(cls)


@router.get("/classes/{class_id}/students", summary="获取班级学生列表")
def list_class_students(
    class_id: int,
    db: Session = Depends(get_db),
    current_user: Person = Depends(get_current_user),
):
    members = (
        db.query(CourseClassPerson, Person)
        .join(Person, CourseClassPerson.gibbonPersonID == Person.gibbonPersonID)
        .filter(CourseClassPerson.gibbonCourseClassID == class_id)
        .all()
    )
    return [
        {
            "gibbonCourseClassPersonID": m.gibbonCourseClassPersonID,
            "gibbonPersonID": m.gibbonPersonID,
            "role": m.role,
            "student_name": f"{p.surname}{p.firstName}",
            "student_id": p.studentID,
            "username": p.username,
        }
        for m, p in members
    ]


@router.post("/classes/{class_id}/enroll", status_code=status.HTTP_201_CREATED, summary="学生入班")
def enroll_student(
    class_id: int,
    data: EnrollStudentRequest,
    db: Session = Depends(get_db),
    current_user: Person = Depends(get_current_user),
):
    cls = db.query(CourseClass).filter(CourseClass.gibbonCourseClassID == class_id).first()
    if not cls:
        raise HTTPException(status_code=404, detail="班级不存在")

    existing = (
        db.query(CourseClassPerson)
        .filter(
            CourseClassPerson.gibbonCourseClassID == class_id,
            CourseClassPerson.gibbonPersonID == data.gibbonPersonID,
        )
        .first()
    )
    if existing:
        raise HTTPException(status_code=400, detail="该学生已在班级中")

    member = CourseClassPerson(
        gibbonCourseClassID=class_id,
        gibbonPersonID=data.gibbonPersonID,
        role=data.role,
    )
    db.add(member)
    db.commit()
    return {"message": "学生已成功加入班级"}


@router.delete("/classes/{class_id}/students/{person_id}", status_code=status.HTTP_204_NO_CONTENT, summary="移除班级学生")
def remove_student_from_class(
    class_id: int,
    person_id: int,
    db: Session = Depends(get_db),
    current_user: Person = Depends(get_current_user),
):
    member = (
        db.query(CourseClassPerson)
        .filter(
            CourseClassPerson.gibbonCourseClassID == class_id,
            CourseClassPerson.gibbonPersonID == person_id,
        )
        .first()
    )
    if not member:
        raise HTTPException(status_code=404, detail="记录不存在")
    db.delete(member)
    db.commit()
