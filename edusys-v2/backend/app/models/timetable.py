from sqlalchemy import Column, String, Integer, Date, Enum, ForeignKey, Time
from sqlalchemy.orm import relationship
from app.core.database import Base


class Timetable(Base):
    __tablename__ = "gibbonTT"

    gibbonTTID = Column(Integer, primary_key=True, autoincrement=True)
    gibbonSchoolYearID = Column(Integer, ForeignKey("gibbonSchoolYear.gibbonSchoolYearID"), nullable=False)
    name = Column(String(30), nullable=False)
    nameShort = Column(String(12), nullable=False)
    colour = Column(String(6))
    colourText = Column(String(6))
    active = Column(Enum("Y", "N"), default="Y")

    days = relationship("TimetableDay", back_populates="timetable")


class TimetableDay(Base):
    __tablename__ = "gibbonTTDay"

    gibbonTTDayID = Column(Integer, primary_key=True, autoincrement=True)
    gibbonTTID = Column(Integer, ForeignKey("gibbonTT.gibbonTTID"), nullable=False)
    gibbonTTColumnID = Column(Integer, ForeignKey("gibbonTTColumn.gibbonTTColumnID"))
    name = Column(String(12), nullable=False)
    nameShort = Column(String(4), nullable=False)
    sequence = Column(Integer, nullable=False, default=0)
    colour = Column(String(6))
    colourText = Column(String(6))

    timetable = relationship("Timetable", back_populates="days")
    column = relationship("TimetableColumn", back_populates="days")
    row_classes = relationship("TimetableDayRowClass", back_populates="day")


class TimetableColumn(Base):
    __tablename__ = "gibbonTTColumn"

    gibbonTTColumnID = Column(Integer, primary_key=True, autoincrement=True)
    name = Column(String(30), nullable=False)
    nameShort = Column(String(12), nullable=False)

    days = relationship("TimetableDay", back_populates="column")
    rows = relationship("TimetableColumnRow", back_populates="column")


class TimetableColumnRow(Base):
    __tablename__ = "gibbonTTColumnRow"

    gibbonTTColumnRowID = Column(Integer, primary_key=True, autoincrement=True)
    gibbonTTColumnID = Column(Integer, ForeignKey("gibbonTTColumn.gibbonTTColumnID"), nullable=False)
    name = Column(String(12), nullable=False)
    nameShort = Column(String(8), nullable=False)
    timeStart = Column(Time, nullable=False)
    timeEnd = Column(Time, nullable=False)
    type = Column(Enum("Class", "Lesson", "Break", "Service", "Other"), nullable=False, default="Class")
    sequence = Column(Integer, nullable=False, default=0)

    column = relationship("TimetableColumn", back_populates="rows")


class TimetableDayRowClass(Base):
    __tablename__ = "gibbonTTDayRowClass"

    gibbonTTDayRowClassID = Column(Integer, primary_key=True, autoincrement=True)
    gibbonTTDayID = Column(Integer, ForeignKey("gibbonTTDay.gibbonTTDayID"), nullable=False)
    gibbonTTColumnRowID = Column(Integer, ForeignKey("gibbonTTColumnRow.gibbonTTColumnRowID"), nullable=False)
    gibbonCourseClassID = Column(Integer, ForeignKey("gibbonCourseClass.gibbonCourseClassID"), nullable=False)
    gibbonSpaceID = Column(Integer)

    day = relationship("TimetableDay", back_populates="row_classes")
    column_row = relationship("TimetableColumnRow")
    course_class = relationship("CourseClass")
