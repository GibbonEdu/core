SELECT * FROM gibbonApplicationForm
    JOIN gibbonFamilyChild ON (gibbonApplicationForm.gibbonFamilyID = gibbonFamilyChild.gibbonFamilyID)
    JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID = gibbonPerson.gibbonPersonID)
WHERE gibbonApplicationForm.status = 'Accepted'
    AND gibbonPerson.gibbonPersonID >= 0000013441
    AND gibbonPerson.gibbonPersonID < 0000014122


# INSERT MISSING PARENTS BEFORE 21/05/2022

INSERT INTO gibbonPerson (username, gibbonRoleIDPrimary, gibbonRoleIDAll, status, title,
    surname, firstName, preferredName, 
    officialName, gender, phone1, 
    phone2, profession, fields)
SELECT CONCAT('G1-', gibbonPerson.username), '004', '004', 'Full', gibbonApplicationForm.parent1title, 
    gibbonApplicationForm.parent1surname, gibbonApplicationForm.parent1preferredName, gibbonApplicationForm.parent1preferredName,
    gibbonApplicationForm.parent1officialName, gibbonApplicationForm.parent1gender, gibbonApplicationForm.parent1phone1,
    gibbonApplicationForm.parent1phone2, gibbonApplicationForm.parent1profession, gibbonApplicationForm.parent1fields
 FROM gibbonApplicationForm 
    JOIN gibbonFamilyChild ON (gibbonApplicationForm.gibbonFamilyID = gibbonFamilyChild.gibbonFamilyID)
    JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID = gibbonPerson.gibbonPersonID)
WHERE gibbonApplicationForm.status = 'Accepted'
    AND gibbonPerson.gibbonPersonID >= 0000013441
    AND gibbonPerson.gibbonPersonID < 0000014122
GROUP BY (gibbonFamilyChild.gibbonFamilyID)

INSERT INTO gibbonPerson (username, gibbonRoleIDPrimary, gibbonRoleIDAll, status, title,
    surname, firstName, preferredName, 
    officialName, gender, phone1, 
    phone2, profession, fields)
SELECT CONCAT('G2-', gibbonPerson.username), '004', '004', 'Full', gibbonApplicationForm.parent2title, 
    gibbonApplicationForm.parent2surname, gibbonApplicationForm.parent2firstName, gibbonApplicationForm.parent2firstName,
    gibbonApplicationForm.parent2officialName, gibbonApplicationForm.parent2gender, gibbonApplicationForm.parent2phone1,
    gibbonApplicationForm.parent2phone2, gibbonApplicationForm.parent2profession, gibbonApplicationForm.parent2fields
 FROM gibbonApplicationForm 
    JOIN gibbonFamilyChild ON (gibbonApplicationForm.gibbonFamilyID = gibbonFamilyChild.gibbonFamilyID)
    JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID = gibbonPerson.gibbonPersonID)
WHERE gibbonApplicationForm.status = 'Accepted'
    AND gibbonPerson.gibbonPersonID >= 0000013441
    AND gibbonPerson.gibbonPersonID < 0000014122
GROUP BY (gibbonFamilyChild.gibbonFamilyID)

--

SELECT MIN(gibbonApplicationForm.gibbonApplicationFormID), MAX(gibbonApplicationForm.gibbonApplicationFormID) FROM gibbonApplicationForm
    JOIN gibbonFamilyChild ON (gibbonApplicationForm.gibbonFamilyID = gibbonFamilyChild.gibbonFamilyID)
    JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID = gibbonPerson.gibbonPersonID)
WHERE gibbonApplicationForm.status = 'Accepted'
    AND gibbonPerson.gibbonPersonID >= 0000013441
    AND gibbonPerson.gibbonPersonID < 0000014122

---

# INSERT FAMILY adult 1
INSERT INTO gibbonFamilyAdult (gibbonFamilyID, gibbonPersonID, contactPriority)
SELECT gibbonFamilyID, gibbonPersonID, 1
  FROM gibbonPerson 
  JOIN gibbonApplicationForm ON (gibbonPerson.username = CONCAT('G1-', gibbonApplicationForm.username))
  WHERE gibbonApplicationForm.status = 'Accepted'
    AND gibbonApplicationForm.gibbonApplicationFormID >= 3333
    AND gibbonApplicationForm.gibbonApplicationFormID <= 4115


INSERT INTO gibbonFamilyAdult (gibbonFamilyID, gibbonPersonID, contactPriority)
SELECT gibbonFamilyID, gibbonPersonID, 2
  FROM gibbonPerson 
  JOIN gibbonApplicationForm ON (gibbonPerson.username = CONCAT('G2-', gibbonApplicationForm.username))
  WHERE gibbonApplicationForm.status = 'Accepted'
    AND gibbonApplicationForm.gibbonApplicationFormID >= 3333
    AND gibbonApplicationForm.gibbonApplicationFormID <= 4115


# INSERT FAMILY ADULT Relationship
INSERT INTO gibbonFamilyRelationship (gibbonFamilyID, gibbonPersonID1, gibbonPersonID2, relationship)
SELECT gibbonFamilyAdult.gibbonFamilyID, gibbonFamilyAdult.gibbonPersonID, gibbonFamilyChild.gibbonPersonID, gibbonApplicationForm.parent1relationship
  FROM gibbonApplicationForm
  JOIN gibbonFamilyAdult ON (gibbonApplicationForm.gibbonFamilyID = gibbonFamilyAdult.gibbonFamilyID)
  JOIN gibbonFamilyChild ON (gibbonApplicationForm.gibbonFamilyID = gibbonFamilyChild.gibbonFamilyID)
  JOIN gibbonPerson ON (gibbonPerson.username = CONCAT('G1-', gibbonApplicationForm.username))
  WHERE gibbonApplicationForm.status = 'Accepted'
    AND gibbonApplicationForm.gibbonApplicationFormID >= 3333
    AND gibbonApplicationForm.gibbonApplicationFormID <= 4115
    GROUP BY(gibbonPerson.username)


INSERT INTO gibbonFamilyRelationship (gibbonFamilyID, gibbonPersonID1, gibbonPersonID2, relationship)
SELECT gibbonFamilyAdult.gibbonFamilyID, gibbonFamilyAdult.gibbonPersonID, gibbonFamilyChild.gibbonPersonID, gibbonApplicationForm.parent2relationship
  FROM gibbonApplicationForm
  JOIN gibbonFamilyAdult ON (gibbonApplicationForm.gibbonFamilyID = gibbonFamilyAdult.gibbonFamilyID)
  JOIN gibbonFamilyChild ON (gibbonApplicationForm.gibbonFamilyID = gibbonFamilyChild.gibbonFamilyID)
  JOIN gibbonPerson ON (gibbonPerson.username = CONCAT('G2-', gibbonApplicationForm.username))
  WHERE gibbonApplicationForm.status = 'Accepted'
    AND gibbonApplicationForm.gibbonApplicationFormID >= 3333
    AND gibbonApplicationForm.gibbonApplicationFormID <= 4115
    GROUP BY(gibbonPerson.username)




ORDER BY `gibbonApplicationForm`.`gibbonApplicationFormID`  DESC



    JOIN gibbonFamilyChild ON (gibbonPerson.gibbonPersonID = gibbonFamilyChild.gibbonPersonID)
    JOIN gibbonFamilyAdult ON (gibbonFamilyChild.gibbonFamilyID = gibbonFamilyAdult.gibbonFamilyID)
    
WHERE gibbonApplicationForm.status = 'Accepted'
    AND gibbonPerson.gibbonPersonID >= 0000013441
    AND gibbonPerson.gibbonPersonID < 0000014122






# FAMILY
SELECT * FROM gibbonFamilyChild 
    JOIN gibbonFamilyAdult ON (gibbonFamilyChild.gibbonFamilyID = gibbonFamilyAdult.gibbonFamilyID)
WHERE gibbonFamilyChild.gibbonPersonID >= 0000013441
    AND gibbonFamilyChild.gibbonPersonID <= 0000014122


# INSERT FAMILY Adult



# INSERT gibbonFamilyRelationship