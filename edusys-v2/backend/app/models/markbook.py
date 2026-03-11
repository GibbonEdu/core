from sqlalchemy import Column, String, Integer, Date, DateTime, Enum, ForeignKey, Text, Numeric
from sqlalchemy.orm import relationship
from app.core.database import Base


class MarkbookColumn(Base):
    __tablename__ = "gibbonMarkbookColumn"

    gibbonMarkbookColumnID = Column(Integer, primary_key=True, autoincrement=True)
    gibbonCourseClassID = Column(Integer, ForeignKey("gibbonCourseClass.gibbonCourseClassID"), nullable=False)
    gibbonPersonIDCreator = Column(Integer)
    name = Column(String(50), nullable=False)
    description = Column(Text)
    type = Column(Enum("Assessment", "Effort", "Rubric", "Mixed", "Comment Only"), nullable=False, default="Assessment")
    attainment = Column(Enum("Y", "N"), default="Y")
    gibbonScaleIDAttainment = Column(Integer)
    effort = Column(Enum("Y", "N"), default="N")
    gibbonScaleIDEffort = Column(Integer)
    comment = Column(Enum("Y", "N"), default="N")
    uploadedResponse = Column(Enum("Y", "N"), default="N")
    date = Column(Date)
    dateOpen = Column(Date)
    dateClose = Column(Date)
    dateSubmissionOpen = Column(Date)
    dateSubmissionClose = Column(Date)
    completeDate = Column(Date)
    complete = Column(Enum("Y", "N"), default="N")
    viewableStudents = Column(Enum("Y", "N"), default="N")
    viewableParents = Column(Enum("Y", "N"), default="N")
    attachment = Column(String(255))
    unitOutcomes = Column(Text)
    sequence = Column(Integer, default=0)

    course_class = relationship("CourseClass", back_populates="markbook_columns")
    entries = relationship("MarkbookEntry", back_populates="column")


class MarkbookEntry(Base):
    __tablename__ = "gibbonMarkbookEntry"

    gibbonMarkbookEntryID = Column(Integer, primary_key=True, autoincrement=True)
    gibbonMarkbookColumnID = Column(Integer, ForeignKey("gibbonMarkbookColumn.gibbonMarkbookColumnID"), nullable=False)
    gibbonPersonIDStudent = Column(Integer, ForeignKey("gibbonPerson.gibbonPersonID"), nullable=False)
    gibbonPersonIDLastEdit = Column(Integer)
    attainmentValue = Column(String(50))
    attainmentValueRaw = Column(Numeric(6, 2))
    effortValue = Column(String(50))
    comment = Column(Text)
    response = Column(String(255))
    timestamp = Column(DateTime)

    column = relationship("MarkbookColumn", back_populates="entries")
    student = relationship("Person", foreign_keys=[gibbonPersonIDStudent])


class MarkbookWeight(Base):
    __tablename__ = "gibbonMarkbookWeight"

    gibbonMarkbookWeightID = Column(Integer, primary_key=True, autoincrement=True)
    gibbonCourseClassID = Column(Integer, ForeignKey("gibbonCourseClass.gibbonCourseClassID"), nullable=False)
    category = Column(String(30), nullable=False)
    weight = Column(Numeric(6, 2), nullable=False, default=1)
    calculateAttainment = Column(Enum("Y", "N"), default="Y")
    calculateEffort = Column(Enum("Y", "N"), default="N")
