from sqlalchemy import Column, String, Integer, Date, Enum, ForeignKey
from sqlalchemy.orm import relationship
from app.core.database import Base


class StudentEnrolment(Base):
    __tablename__ = "gibbonStudentEnrolment"

    gibbonStudentEnrolmentID = Column(Integer, primary_key=True, autoincrement=True)
    gibbonPersonID = Column(Integer, ForeignKey("gibbonPerson.gibbonPersonID"), nullable=False)
    gibbonSchoolYearID = Column(Integer, ForeignKey("gibbonSchoolYear.gibbonSchoolYearID"), nullable=False)
    gibbonYearGroupID = Column(Integer, ForeignKey("gibbonYearGroup.gibbonYearGroupID"), nullable=False)
    gibbonFormGroupID = Column(Integer, ForeignKey("gibbonFormGroup.gibbonFormGroupID"))
    rollOrder = Column(Integer)
    dateStart = Column(Date)
    dateEnd = Column(Date)
    status = Column(String(20))

    person = relationship("Person", back_populates="student_enrolments")
    school_year = relationship("SchoolYear", back_populates="student_enrolments")
    year_group = relationship("YearGroup", back_populates="student_enrolments")
    form_group = relationship("FormGroup", back_populates="student_enrolments")
