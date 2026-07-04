SELECT
  student.username,
  student.surname,
  student.preferredName,
  student.gender,
  gibbonRollGroup.name AS Center,
  student.Religion,
  (
    CASE WHEN (@find := LOCATE('s:3:"001";s', student.fields)) > 0 THEN REPLACE(LEFT(@var :=SUBSTRING(student.fields, @find + 15),LOCATE('";', @var)-1
      ),
      '"',
      ''
    ) ELSE '' END
  ) AS Caste,
  gibbonFamily.homeAddress as Address,
  (
    CASE WHEN (
      @find := LOCATE('s:3:"002";s', student.fields)
    ) > 0 THEN REPLACE(
      LEFT(
        @var := SUBSTRING(student.fields, @find + 15),
        LOCATE('";', @var)-1
      ),
      '"',
      ''
    ) ELSE '' END
  ) AS 'Exact DOB',
  student.dob,
  student.dateStart AS 'Date of joining',
  (
    CASE WHEN (
      @find := LOCATE('s:3:"005";s', student.fields)
    ) > 0 THEN REPLACE(
      LEFT(
        @var := SUBSTRING(student.fields, @find + 15),
        LOCATE('";', @var)-1
      ),
      '"',
      ''
    ) ELSE '' END
  ) AS 'Students ocupation',
  (
    CASE WHEN (
      @find := LOCATE('s:3:"006";s', student.fields)
    ) > 0 THEN REPLACE(
      LEFT(
        @var := SUBSTRING(student.fields, @find + 15),
        LOCATE('";', @var)-1
      ),
      '"',
      ''
    ) ELSE '' END
  ) AS 'Salary per month',
  (
    CASE WHEN (
      @find := LOCATE('s:3:"007";s', student.fields)
    ) > 0 THEN REPLACE(
      LEFT(
        @var := SUBSTRING(student.fields, @find + 15),
        LOCATE('";', @var)-1
      ),
      '"',
      ''
    ) ELSE '' END
  ) AS 'Work start time',
  (
    CASE WHEN (
      @find := LOCATE('s:3:"008";s', student.fields)
    ) > 0 THEN REPLACE(
      LEFT(
        @var := SUBSTRING(student.fields, @find + 15),
        LOCATE('";', @var)-1
      ),
      '"',
      ''
    ) ELSE '' END
  ) AS 'Work end time',
  student.emergency1name AS 'Guardian 1 Name',
  student.emergency1Relationship AS 'Guardian 1 Relationship',
  student.emergency1number2 AS 'Guardian 1 Education',
  student.emergency1Number2 AS 'Guardian 1 Contact Number',
  student.emergency1Number1 AS 'Guardian 1 Ocupation',
  student.emergency1Number2 AS 'Guardian 1 Income per month',
  student.emergency2name AS 'Guardian 2 Name',
  student.emergency2Relationship AS 'Guardian 2 Relationship',
  student.emergency2number2 AS 'Guardian 2 Education',
  student.emergency2Number1 AS 'Guardian 2 Contact Number',
  student.emergency2Number1 AS 'Guardian 2 Ocupation',
  student.emergency2Number2 AS 'Guardian 2 Income per month',
  CONCAT(
    adult1.firstName, ' ', adult1.surname
  ) AS 'Adult 1 Name',
  relation1.relationship AS 'Adult 1 Relationship',
  (
    CASE WHEN (
      @find := LOCATE('s:3:"023";s', adult1.fields)
    ) > 0 THEN REPLACE(
      LEFT(
        @var := SUBSTRING(adult1.fields, @find + 15),
        LOCATE('";', @var)-1
      ),
      '"',
      ''
    ) ELSE '' END
  ) AS 'Adult 1 Education',
  adult1.phone1 AS 'Adult 1 Number',
  adult1.profession AS 'Adult 1 Profession',
  (
    CASE WHEN (
      @find := LOCATE('s:3:"006";s', adult1.fields)
    ) > 0 THEN REPLACE(
      LEFT(
        @var := SUBSTRING(adult1.fields, @find + 15),
        LOCATE('";', @var)-1
      ),
      '"',
      ''
    ) ELSE '' END
  ) AS 'Adult 1 Salary',
  CONCAT(
    adult2.firstName, ' ', adult2.surname
  ) AS 'Adult 2 Name',
  relation2.relationship AS 'Adult 2 Relationship',
  (
    CASE WHEN (
      @find := LOCATE('s:3:"023";s', adult2.fields)
    ) > 0 THEN REPLACE(
      LEFT(
        @var := SUBSTRING(adult2.fields, @find + 15),
        LOCATE('";', @var)-1
      ),
      '"',
      ''
    ) ELSE '' END
  ) AS 'Adult 2 Education',
  adult2.phone1 AS 'Adult 2 Number',
  adult2.profession AS 'Adult 2 Profession',
  (
    CASE WHEN (
      @find := LOCATE('s:3:"006";s', adult2.fields)
    ) > 0 THEN REPLACE(
      LEFT(
        @var := SUBSTRING(adult2.fields, @find + 15),
        LOCATE('";', @var)-1
      ),
      '"',
      ''
    ) ELSE '' END
  ) AS 'Adult 2 Salary',
  (
    CASE WHEN (
      @find := LOCATE('s:3:"004";s', student.fields)
    ) > 0 THEN REPLACE(
      LEFT(
        @var := SUBSTRING(student.fields, @find + 15),
        LOCATE('";', @var)-1
      ),
      '"',
      ''
    ) ELSE '' END
  ) AS 'Family income',
  (
    CASE WHEN (
      @find := LOCATE('s:3:"003";s', student.fields)
    ) > 0 THEN REPLACE(
      LEFT(
        @var := SUBSTRING(student.fields, @find + 15),
        LOCATE('";', @var)-1
      ),
      '"',
      ''
    ) ELSE '' END
  ) AS 'Family size',
  (
    CASE WHEN (
      @find := LOCATE('s:3:"011";s', student.fields)
    ) > 0 THEN REPLACE(
      LEFT(
        @var := SUBSTRING(student.fields, @find + 15),
        LOCATE('";', @var)-1
      ),
      '"',
      ''
    ) ELSE '' END
  ) AS 'Is the student in another shool',
  (
    CASE WHEN (
      @find := LOCATE('s:3:"016";s', student.fields)
    ) > 0 THEN REPLACE(
      LEFT(
        @var := SUBSTRING(student.fields, @find + 15),
        LOCATE('";', @var)-1
      ),
      '"',
      ''
    ) ELSE '' END
  ) AS 'Reason for not attending school',
  (
    CASE WHEN (
      @find := LOCATE('s:3:"010";s', student.fields)
    ) > 0 THEN REPLACE(
      LEFT(
        @var := SUBSTRING(student.fields, @find + 15),
        LOCATE('";', @var)-1
      ),
      '"',
      ''
    ) ELSE '' END
  ) AS 'Date student dropped out of school',
  (
    CASE WHEN (
      @find := LOCATE('s:3:"009";s', student.fields)
    ) > 0 THEN REPLACE(
      LEFT(
        @var := SUBSTRING(student.fields, @find + 15),
        LOCATE('";', @var)-1
      ),
      '"',
      ''
    ) ELSE '' END
  ) AS 'When did the student start school',
  (
    CASE WHEN (
      @find := LOCATE('s:3:"013";s', student.fields)
    ) > 0 THEN REPLACE(
      LEFT(
        @var := SUBSTRING(student.fields, @find + 15),
        LOCATE('";', @var)-1
      ),
      '"',
      ''
    ) ELSE '' END
  ) AS 'Type of current school',
  (
    CASE WHEN (
      @find := LOCATE('s:3:"012";s', student.fields)
    ) > 0 THEN REPLACE(
      LEFT(
        @var := SUBSTRING(student.fields, @find + 15),
        LOCATE('";', @var)-1
      ),
      '"',
      ''
    ) ELSE '' END
  ) AS 'Name of current school',
  (
    CASE WHEN (
      @find := LOCATE('s:3:"014";s', student.fields)
    ) > 0 THEN REPLACE(
      LEFT(
        @var := SUBSTRING(student.fields, @find + 15),
        LOCATE('";', @var)-1
      ),
      '"',
      ''
    ) ELSE '' END
  ) AS 'What class is he student enrolled in his-her school',
  (
    CASE WHEN (
      @find := LOCATE('s:3:"015";s', student.fields)
    ) > 0 THEN REPLACE(
      LEFT(
        @var := SUBSTRING(student.fields, @find + 15),
        LOCATE('";', @var)-1
      ),
      '"',
      ''
    ) ELSE '' END
  ) AS 'Reason for coming to the center',
  gibbonYearGroup.name AS 'Level of student'
FROM
  gibbonPerson AS student
  JOIN gibbonStudentEnrolment ON (
    gibbonStudentEnrolment.gibbonPersonID = student.gibbonPersonID
  )
  JOIN gibbonRollGroup ON (
    gibbonStudentEnrolment.gibbonRollGroupID = gibbonRollGroup.gibbonRollGroupID
  )
  JOIN gibbonYearGroup ON (
    gibbonStudentEnrolment.gibbonYearGroupID = gibbonYearGroup.gibbonYearGroupID
  )
  LEFT JOIN gibbonFamilyChild ON (
    gibbonFamilyChild.gibbonPersonID = student.gibbonPersonID
  )
  LEFT JOIN gibbonFamily ON (
    gibbonFamilyChild.gibbonFamilyID = gibbonFamily.gibbonFamilyID
  )
  LEFT JOIN gibbonFamilyAdult AS adult1Fam ON (
    adult1Fam.gibbonFamilyID = gibbonFamily.gibbonFamilyID
    AND adult1Fam.contactPriority = 1
  )
  LEFT JOIN gibbonPerson AS adult1 ON (
    adult1Fam.gibbonPersonID = adult1.gibbonPersonID
    AND adult1.status = 'Full'
  )
  LEFT JOIN gibbonFamilyRelationship AS relation1 ON (
    student.gibbonPersonID = relation1.gibbonPersonID2
    AND adult1.gibbonPersonID = relation1.gibbonPersonID1
  )
  LEFT JOIN gibbonFamilyAdult AS adult2Fam ON (
    adult2Fam.gibbonFamilyID = gibbonFamily.gibbonFamilyID
    AND adult2Fam.contactPriority = 2
  )
  LEFT JOIN gibbonPerson AS adult2 ON (
    adult2Fam.gibbonPersonID = adult2.gibbonPersonID
    AND adult2.status = 'Full'
  )
  LEFT JOIN gibbonFamilyRelationship AS relation2 ON (
    student.gibbonPersonID = relation2.gibbonPersonID2
    AND adult2.gibbonPersonID = relation2.gibbonPersonID1
  )
WHERE
  student.status = 'Full'
  AND (
    student.dateStart IS NULL
    OR student.dateStart <= CURDATE()
  )
  AND (
    student.dateEnd IS NULL
    OR student.dateEnd >= CURDATE()
  )
  AND gibbonStudentEnrolment.gibbonSchoolYearID = (
    SELECT
      gibbonSchoolYearID
    FROM
      gibbonSchoolYear
    WHERE
      status = 'Current'
  )
ORDER BY
  center,
  surname,
  preferredName
