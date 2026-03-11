from sqlalchemy import Column, String, Integer, Date, DateTime, Enum, ForeignKey, Time
from sqlalchemy.orm import relationship
from app.core.database import Base


class AttendanceCode(Base):
    __tablename__ = "gibbonAttendanceCode"

    gibbonAttendanceCodeID = Column(Integer, primary_key=True, autoincrement=True)
    name = Column(String(30), nullable=False)
    nameShort = Column(String(4), nullable=False)
    type = Column(Enum("Present", "Absent", "Late", "Left Early", "Remote Learning", "Off Site"), nullable=False)
    scope = Column(Enum("Onsite", "School", "Class"), nullable=False, default="School")
    active = Column(Enum("Y", "N"), default="Y")
    reportable = Column(Enum("Y", "N"), default="Y")
    reason = Column(Enum("Y", "N"), default="N")
    sequence = Column(Integer, default=0)
    direction = Column(Enum("In", "Out", ""), default="")
    colour = Column(String(7))
    colourBackground = Column(String(7))


class AttendanceLogPerson(Base):
    __tablename__ = "gibbonAttendanceLogPerson"

    gibbonAttendanceLogPersonID = Column(Integer, primary_key=True, autoincrement=True)
    gibbonAttendanceCodeID = Column(Integer, ForeignKey("gibbonAttendanceCode.gibbonAttendanceCodeID"))
    gibbonPersonID = Column(Integer, ForeignKey("gibbonPerson.gibbonPersonID"), nullable=False)
    gibbonCourseClassID = Column(Integer)
    direction = Column(Enum("In", "Out", ""), default="")
    date = Column(Date, nullable=False)
    timestampTaken = Column(DateTime)
    gibbonPersonIDTaken = Column(Integer)
    reason = Column(String(50))
    comment = Column(String(255))
    context = Column(Enum("School", "Class", "Future"), nullable=False, default="School")
    type = Column(String(50))

    person = relationship("Person", back_populates="attendance_logs", foreign_keys=[gibbonPersonID])
    attendance_code = relationship("AttendanceCode")


class AttendanceLogCourseClass(Base):
    __tablename__ = "gibbonAttendanceLogCourseClass"

    gibbonAttendanceLogCourseClassID = Column(Integer, primary_key=True, autoincrement=True)
    gibbonCourseClassID = Column(Integer, ForeignKey("gibbonCourseClass.gibbonCourseClassID"), nullable=False)
    gibbonPersonIDTaken = Column(Integer)
    date = Column(Date, nullable=False)
    timestampTaken = Column(DateTime)
    gibbonTTDayRowClassID = Column(Integer)

    course_class = relationship("CourseClass")
