from sqlalchemy import Column, String, Integer, Date, DateTime, Enum, Text, Boolean
from sqlalchemy.orm import relationship
from app.core.database import Base


class Person(Base):
    __tablename__ = "gibbonPerson"

    gibbonPersonID = Column(Integer, primary_key=True, autoincrement=True)
    title = Column(String(5))
    surname = Column(String(60), nullable=False, default="")
    firstName = Column(String(60), nullable=False, default="")
    preferredName = Column(String(60), nullable=False, default="")
    officialName = Column(String(150), nullable=False, default="")
    nameInCharacters = Column(String(60))
    gender = Column(Enum("M", "F", "Unspecified", "Other"), nullable=False, default="Unspecified")
    username = Column(String(20), nullable=False, unique=True, default="")
    password = Column(String(255))
    passwordStrong = Column(String(255))
    passwordForceReset = Column(Enum("Y", "N"), default="N")
    status = Column(Enum("Full", "Expected", "Left", "Pending Approval"), nullable=False, default="Full")
    email = Column(String(75))
    emailAlternate = Column(String(75))
    phone1 = Column(String(20))
    phone1CountryCode = Column(String(7))
    phone1Type = Column(Enum("Mobile", "Home", "Work", "Fax", "Pager", "Satellite", "Other", ""))
    dob = Column(Date)
    image_240 = Column(String(255))
    gibbonRoleIDPrimary = Column(Integer)
    gibbonRoleIDAll = Column(String(255), default="")
    lastIPAddress = Column(String(45))
    lastTimestamp = Column(DateTime)
    lastFailIPAddress = Column(String(45))
    lastFailTimestamp = Column(DateTime)
    failCount = Column(Integer, default=0)
    website = Column(String(255))
    address1 = Column(String(255))
    city = Column(String(60))
    country = Column(String(60))
    languageFirst = Column(String(30))
    languageSecond = Column(String(30))
    emergency1Name = Column(String(120))
    emergency1Relationship = Column(String(30))
    emergency1Number1 = Column(String(20))
    emergency1Number2 = Column(String(20))
    studentID = Column(String(20))
    dateStart = Column(Date)
    dateEnd = Column(Date)
    vehicleRegistration = Column(String(20))
    transport = Column(String(60))
    calendarFeedPersonal = Column(String(40))
    viewCalendarSchool = Column(Enum("Y", "N"), default="Y")
    viewCalendarPersonal = Column(Enum("Y", "N"), default="Y")
    privacy = Column(String(255))
    dayType = Column(String(50))
    customField1 = Column(Text)
    customField2 = Column(Text)

    student_enrolments = relationship("StudentEnrolment", back_populates="person")
    attendance_logs = relationship("AttendanceLogPerson", back_populates="person")

    @property
    def full_name(self):
        return f"{self.surname} {self.firstName}"

    @property
    def display_name(self):
        return self.preferredName or self.firstName

    @property
    def role_names(self):
        return [r.strip() for r in (self.gibbonRoleIDAll or "").split(",") if r.strip()]


class Role(Base):
    __tablename__ = "gibbonRole"

    gibbonRoleID = Column(Integer, primary_key=True, autoincrement=True)
    type = Column(Enum("Core", "Additional"), nullable=False, default="Additional")
    name = Column(String(20), nullable=False, default="")
    nameShort = Column(String(4), nullable=False, default="")
    description = Column(String(255))
    canLoginRole = Column(Enum("Y", "N"), default="Y")
    futureYearsLogin = Column(Enum("Y", "N"), default="N")
    pastYearsLogin = Column(Enum("Y", "N"), default="N")
    restriction = Column(Enum("None", "Same Role", "Granted Roles"), default="None")
    category = Column(Enum("Student", "Parent", "Staff", "Other"), nullable=False, default="Other")


class SchoolYear(Base):
    __tablename__ = "gibbonSchoolYear"

    gibbonSchoolYearID = Column(Integer, primary_key=True, autoincrement=True)
    name = Column(String(9), nullable=False)
    status = Column(Enum("Past", "Current", "Upcoming"), nullable=False, default="Upcoming")
    sequence = Column(Integer, nullable=False, default=0)
    firstDay = Column(Date)
    lastDay = Column(Date)
    countUpcoming = Column(Integer, default=0)
    lastDayExceptions = Column(Text)

    year_groups = relationship("YearGroup", back_populates="school_year")
    student_enrolments = relationship("StudentEnrolment", back_populates="school_year")
    courses = relationship("Course", back_populates="school_year")


class YearGroup(Base):
    __tablename__ = "gibbonYearGroup"

    gibbonYearGroupID = Column(Integer, primary_key=True, autoincrement=True)
    name = Column(String(15), nullable=False)
    nameShort = Column(String(4), nullable=False)
    sequence = Column(Integer, nullable=False, default=0)
    gibbonSchoolYearID = Column(Integer)

    school_year = relationship("SchoolYear", back_populates="year_groups", foreign_keys=[gibbonSchoolYearID])
    form_groups = relationship("FormGroup", back_populates="year_group")
    student_enrolments = relationship("StudentEnrolment", back_populates="year_group")


class FormGroup(Base):
    __tablename__ = "gibbonFormGroup"

    gibbonFormGroupID = Column(Integer, primary_key=True, autoincrement=True)
    gibbonSchoolYearID = Column(Integer, nullable=False)
    gibbonYearGroupID = Column(Integer)
    name = Column(String(15), nullable=False)
    nameShort = Column(String(15))
    tutorsDays = Column(String(5))
    attendanceable = Column(Enum("Y", "N"), default="Y")
    gibbonPersonIDTutor = Column(Integer)
    gibbonPersonIDTutor2 = Column(Integer)
    gibbonPersonIDTutor3 = Column(Integer)
    gibbonSpaceID = Column(Integer)
    website = Column(String(255))

    year_group = relationship("YearGroup", back_populates="form_groups")
    student_enrolments = relationship("StudentEnrolment", back_populates="form_group")
