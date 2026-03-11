from sqlalchemy import Column, String, Integer, Enum, ForeignKey, Text
from sqlalchemy.orm import relationship
from app.core.database import Base


class Course(Base):
    __tablename__ = "gibbonCourse"

    gibbonCourseID = Column(Integer, primary_key=True, autoincrement=True)
    gibbonSchoolYearID = Column(Integer, ForeignKey("gibbonSchoolYear.gibbonSchoolYearID"), nullable=False)
    gibbonDepartmentID = Column(Integer)
    name = Column(String(60), nullable=False)
    nameShort = Column(String(14), nullable=False)
    description = Column(Text)
    map = Column(Enum("Y", "N"), default="Y")
    orderBy = Column(String(30))
    gibbonYearGroupIDList = Column(String(255))
    allowAttendance = Column(Enum("Y", "N"), default="N")

    school_year = relationship("SchoolYear", back_populates="courses")
    classes = relationship("CourseClass", back_populates="course")


class CourseClass(Base):
    __tablename__ = "gibbonCourseClass"

    gibbonCourseClassID = Column(Integer, primary_key=True, autoincrement=True)
    gibbonCourseID = Column(Integer, ForeignKey("gibbonCourse.gibbonCourseID"), nullable=False)
    name = Column(String(12), nullable=False)
    nameShort = Column(String(8), nullable=False)
    attendance = Column(Enum("Y", "N"), default="N")
    reportable = Column(Enum("Y", "N"), default="Y")
    enrolmentMin = Column(Integer)
    enrolmentMax = Column(Integer)
    website = Column(String(255))

    course = relationship("Course", back_populates="classes")
    class_persons = relationship("CourseClassPerson", back_populates="course_class")
    markbook_columns = relationship("MarkbookColumn", back_populates="course_class")


class CourseClassPerson(Base):
    __tablename__ = "gibbonCourseClassPerson"

    gibbonCourseClassPersonID = Column(Integer, primary_key=True, autoincrement=True)
    gibbonCourseClassID = Column(Integer, ForeignKey("gibbonCourseClass.gibbonCourseClassID"), nullable=False)
    gibbonPersonID = Column(Integer, ForeignKey("gibbonPerson.gibbonPersonID"), nullable=False)
    role = Column(Enum("Student", "Teacher", "Assistant", "Parent", "Observer"), nullable=False, default="Student")
    dateEnrolled = Column(String(10))
    dateUnenrolled = Column(String(10))
    reportable = Column(Enum("Y", "N"), default="Y")
    fromSchoolYear = Column(Enum("Y", "N"), default="N")

    course_class = relationship("CourseClass", back_populates="class_persons")
    person = relationship("Person")
