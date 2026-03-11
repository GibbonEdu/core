from fastapi import APIRouter, Depends, HTTPException, Query, status
from sqlalchemy.orm import Session
from sqlalchemy import func
from typing import List, Optional
from datetime import datetime, timezone

from app.core.database import get_db
from app.core.deps import get_current_user
from app.models.person import Person
from app.models.course import CourseClass, CourseClassPerson
from app.models.markbook import MarkbookColumn, MarkbookEntry
from app.schemas.markbook import (
    MarkbookColumnCreate,
    MarkbookColumnUpdate,
    MarkbookColumnResponse,
    MarkbookEntryUpdate,
    MarkbookEntryResponse,
    BulkMarkbookEntryCreate,
)

router = APIRouter(prefix="/markbook", tags=["成绩管理"])


@router.get("/classes/{class_id}/columns", response_model=List[MarkbookColumnResponse], summary="获取评估项目列表")
def list_columns(
    class_id: int,
    db: Session = Depends(get_db),
    current_user: Person = Depends(get_current_user),
):
    columns = (
        db.query(MarkbookColumn)
        .filter(MarkbookColumn.gibbonCourseClassID == class_id)
        .order_by(MarkbookColumn.sequence, MarkbookColumn.date)
        .all()
    )
    return [MarkbookColumnResponse.model_validate(c) for c in columns]


@router.post("/classes/{class_id}/columns", response_model=MarkbookColumnResponse, status_code=status.HTTP_201_CREATED, summary="新建评估项目")
def create_column(
    class_id: int,
    data: MarkbookColumnCreate,
    db: Session = Depends(get_db),
    current_user: Person = Depends(get_current_user),
):
    cls = db.query(CourseClass).filter(CourseClass.gibbonCourseClassID == class_id).first()
    if not cls:
        raise HTTPException(status_code=404, detail="班级不存在")

    column = MarkbookColumn(
        gibbonCourseClassID=class_id,
        gibbonPersonIDCreator=current_user.gibbonPersonID,
        **data.model_dump(),
    )
    db.add(column)
    db.commit()
    db.refresh(column)
    return MarkbookColumnResponse.model_validate(column)


@router.put("/columns/{column_id}", response_model=MarkbookColumnResponse, summary="更新评估项目")
def update_column(
    column_id: int,
    data: MarkbookColumnUpdate,
    db: Session = Depends(get_db),
    current_user: Person = Depends(get_current_user),
):
    column = db.query(MarkbookColumn).filter(MarkbookColumn.gibbonMarkbookColumnID == column_id).first()
    if not column:
        raise HTTPException(status_code=404, detail="评估项目不存在")
    for field, value in data.model_dump(exclude_unset=True).items():
        setattr(column, field, value)
    db.commit()
    db.refresh(column)
    return MarkbookColumnResponse.model_validate(column)


@router.delete("/columns/{column_id}", status_code=status.HTTP_204_NO_CONTENT, summary="删除评估项目")
def delete_column(
    column_id: int,
    db: Session = Depends(get_db),
    current_user: Person = Depends(get_current_user),
):
    column = db.query(MarkbookColumn).filter(MarkbookColumn.gibbonMarkbookColumnID == column_id).first()
    if not column:
        raise HTTPException(status_code=404, detail="评估项目不存在")
    db.delete(column)
    db.commit()


@router.get("/columns/{column_id}/entries", summary="获取评估项目的学生成绩")
def list_entries(
    column_id: int,
    db: Session = Depends(get_db),
    current_user: Person = Depends(get_current_user),
):
    column = db.query(MarkbookColumn).filter(MarkbookColumn.gibbonMarkbookColumnID == column_id).first()
    if not column:
        raise HTTPException(status_code=404, detail="评估项目不存在")

    # 获取班级所有学生
    students = (
        db.query(CourseClassPerson, Person)
        .join(Person, CourseClassPerson.gibbonPersonID == Person.gibbonPersonID)
        .filter(
            CourseClassPerson.gibbonCourseClassID == column.gibbonCourseClassID,
            CourseClassPerson.role == "Student",
        )
        .all()
    )

    # 获取成绩条目
    entries = {
        e.gibbonPersonIDStudent: e
        for e in db.query(MarkbookEntry)
        .filter(MarkbookEntry.gibbonMarkbookColumnID == column_id)
        .all()
    }

    return [
        {
            "gibbonPersonID": person.gibbonPersonID,
            "student_name": f"{person.surname}{person.firstName}",
            "student_id": person.studentID,
            "attainmentValue": entries[person.gibbonPersonID].attainmentValue
            if person.gibbonPersonID in entries
            else None,
            "attainmentValueRaw": float(entries[person.gibbonPersonID].attainmentValueRaw)
            if person.gibbonPersonID in entries and entries[person.gibbonPersonID].attainmentValueRaw
            else None,
            "effortValue": entries[person.gibbonPersonID].effortValue
            if person.gibbonPersonID in entries
            else None,
            "comment": entries[person.gibbonPersonID].comment
            if person.gibbonPersonID in entries
            else None,
        }
        for _, person in students
    ]


@router.post("/columns/{column_id}/entries/bulk", summary="批量录入成绩")
def bulk_update_entries(
    column_id: int,
    data: BulkMarkbookEntryCreate,
    db: Session = Depends(get_db),
    current_user: Person = Depends(get_current_user),
):
    column = db.query(MarkbookColumn).filter(MarkbookColumn.gibbonMarkbookColumnID == column_id).first()
    if not column:
        raise HTTPException(status_code=404, detail="评估项目不存在")

    now = datetime.now(timezone.utc)
    for item in data.entries:
        entry = (
            db.query(MarkbookEntry)
            .filter(
                MarkbookEntry.gibbonMarkbookColumnID == column_id,
                MarkbookEntry.gibbonPersonIDStudent == item.gibbonPersonIDStudent,
            )
            .first()
        )
        if entry:
            if item.attainmentValue is not None:
                entry.attainmentValue = item.attainmentValue
            if item.attainmentValueRaw is not None:
                entry.attainmentValueRaw = item.attainmentValueRaw
            if item.effortValue is not None:
                entry.effortValue = item.effortValue
            if item.comment is not None:
                entry.comment = item.comment
            entry.gibbonPersonIDLastEdit = current_user.gibbonPersonID
            entry.timestamp = now
        else:
            entry = MarkbookEntry(
                gibbonMarkbookColumnID=column_id,
                gibbonPersonIDStudent=item.gibbonPersonIDStudent,
                gibbonPersonIDLastEdit=current_user.gibbonPersonID,
                attainmentValue=item.attainmentValue,
                attainmentValueRaw=item.attainmentValueRaw,
                effortValue=item.effortValue,
                comment=item.comment,
                timestamp=now,
            )
            db.add(entry)
    db.commit()
    return {"message": f"已保存{len(data.entries)}条成绩记录"}


@router.get("/students/{student_id}/summary", summary="获取学生成绩汇总")
def get_student_grade_summary(
    student_id: int,
    school_year_id: Optional[int] = Query(None),
    db: Session = Depends(get_db),
    current_user: Person = Depends(get_current_user),
):
    from app.models.course import Course

    entries = (
        db.query(MarkbookEntry, MarkbookColumn, CourseClass, Course)
        .join(MarkbookColumn, MarkbookEntry.gibbonMarkbookColumnID == MarkbookColumn.gibbonMarkbookColumnID)
        .join(CourseClass, MarkbookColumn.gibbonCourseClassID == CourseClass.gibbonCourseClassID)
        .join(Course, CourseClass.gibbonCourseID == Course.gibbonCourseID)
        .filter(MarkbookEntry.gibbonPersonIDStudent == student_id)
    )
    if school_year_id:
        entries = entries.filter(Course.gibbonSchoolYearID == school_year_id)

    results = entries.all()
    summary = {}
    for entry, column, cls, course in results:
        key = f"{course.name} - {cls.name}"
        if key not in summary:
            summary[key] = {"course": course.name, "class": cls.name, "grades": []}
        summary[key]["grades"].append(
            {
                "assessment": column.name,
                "date": str(column.date) if column.date else None,
                "attainment": entry.attainmentValue,
                "raw_score": float(entry.attainmentValueRaw) if entry.attainmentValueRaw else None,
            }
        )

    return list(summary.values())
