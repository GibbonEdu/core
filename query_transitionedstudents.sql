SELECT student.username,
       student.preferredName AS Name,
       student.surname AS Surname,
       student.gender,
       gibbonRollGroup.name AS Center,
       gibbonYearGroup.name AS LEVEL,
       student.departureReason AS 'Departure Reason',
       student.dob AS 'Date of birth',
       student.dateStart AS 'Date of joining',
       student.dateEnd AS 'Date of departure',
       CONCAT(adult1.firstName, ' ', adult1.surname) AS 'Adult 1 Name',
       relation1.relationship AS 'Adult 1 Relationship',
       adult1.phone1 AS 'Adult 1 Number',
       CONCAT(adult2.firstName, ' ', adult2.surname) AS 'Adult 2 Name',
       relation2.relationship AS 'Adult 2 Relationship',
       adult2.phone1 AS 'Adult 2 Number',
       (CASE
            WHEN (@find := LOCATE('s:3:"016";s', student.fields)) > 0 THEN REPLACE(LEFT(@var := SUBSTRING(student.fields, @find + 15), LOCATE('";', @var)-1), '"', '')
            ELSE ''
        END) AS 'Reason for not attending school',
       (CASE
            WHEN (@find := LOCATE('s:3:"010";s', student.fields)) > 0 THEN REPLACE(LEFT(@var := SUBSTRING(student.fields, @find + 15), LOCATE('";', @var)-1), '"', '')
            ELSE ''
        END) AS 'Date student dropped out of school',
       (CASE
            WHEN (@find := LOCATE('s:3:"009";s', student.fields)) > 0 THEN REPLACE(LEFT(@var := SUBSTRING(student.fields, @find + 15), LOCATE('";', @var)-1), '"', '')
            ELSE ''
        END) AS 'When did the student start school',
       (CASE
            WHEN (@find := LOCATE('s:3:"013";s', student.fields)) > 0 THEN REPLACE(LEFT(@var := SUBSTRING(student.fields, @find + 15), LOCATE('";', @var)-1), '"', '')
            ELSE ''
        END) AS 'Type of current school',
       (CASE
            WHEN (@find := LOCATE('s:3:"012";s', student.fields)) > 0 THEN REPLACE(LEFT(@var := SUBSTRING(student.fields, @find + 15), LOCATE('";', @var)-1), '"', '')
            ELSE ''
        END) AS 'Name of current school',
       (CASE
            WHEN (@find := LOCATE('s:3:"014";s', student.fields)) > 0 THEN REPLACE(LEFT(@var := SUBSTRING(student.fields, @find + 15), LOCATE('";', @var)-1), '"', '')
            ELSE ''
        END) AS 'What class is he student enrolled in his-her school',
       (CASE
            WHEN (@find := LOCATE('s:3:"015";s', student.fields)) > 0 THEN REPLACE(LEFT(@var := SUBSTRING(student.fields, @find + 15), LOCATE('";', @var)-1), '"', '')
            ELSE ''
        END) AS 'Reason for coming to the center'
FROM gibbonPerson AS student
JOIN
    (SELECT gibbonStudentEnrolment.*,
            MAX(gibbonSchoolYearID) AS yearID
     FROM gibbonStudentEnrolment
     GROUP BY gibbonPersonID) gibbonSE ON (student.gibbonPersonID = gibbonSE.gibbonPersonID)
JOIN gibbonRollGroup ON (gibbonSE.gibbonRollGroupID = gibbonRollGroup.gibbonRollGroupID)
JOIN gibbonYearGroup ON (gibbonSE.gibbonYearGroupID = gibbonYearGroup.gibbonYearGroupID)
LEFT JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID = student.gibbonPersonID)
LEFT JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID = gibbonFamily.gibbonFamilyID)
LEFT JOIN gibbonFamilyAdult AS adult1Fam ON (adult1Fam.gibbonFamilyID = gibbonFamily.gibbonFamilyID
                                             AND adult1Fam.contactPriority = 1)
LEFT JOIN gibbonPerson AS adult1 ON (adult1Fam.gibbonPersonID = adult1.gibbonPersonID
                                     AND adult1.status = 'Full')
LEFT JOIN gibbonFamilyRelationship AS relation1 ON (student.gibbonPersonID = relation1.gibbonPersonID2
                                                    AND adult1.gibbonPersonID = relation1.gibbonPersonID1)
LEFT JOIN gibbonFamilyAdult AS adult2Fam ON (adult2Fam.gibbonFamilyID = gibbonFamily.gibbonFamilyID
                                             AND adult2Fam.contactPriority = 2)
LEFT JOIN gibbonPerson AS adult2 ON (adult2Fam.gibbonPersonID = adult2.gibbonPersonID
                                     AND adult2.status = 'Full')
LEFT JOIN gibbonFamilyRelationship AS relation2 ON (student.gibbonPersonID = relation2.gibbonPersonID2
                                                    AND adult2.gibbonPersonID = relation2.gibbonPersonID1)
WHERE student.gibbonRoleIDPrimary = '003'
    AND (student.dateStart IS NULL
         OR student.dateStart <= CURDATE())
    AND (student.departureReason = 'Transitioned')
ORDER BY center,
         student.username,
         student.surname,
         student.preferredName
