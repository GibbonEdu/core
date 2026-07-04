# INSERT MISSING PARENTS BEFORE 21/05/2022



# Affected application forms

INSERT INTO gibbonPerson SET username, password='', passwordStrong=:passwordStrong, passwordStrongSalt=:passwordStrongSalt, gibbonRoleIDPrimary='004', gibbonRoleIDAll='004', status=:status, title=:title, surname=:surname, firstName=:firstName, preferredName=:preferredName, officialName=:officialName, nameInCharacters=:nameInCharacters, gender=:gender, languageFirst=:parent2languageFirst, languageSecond=:parent2languageSecond, email=:email, phone1Type=:phone1Type, phone1CountryCode=:phone1CountryCode, phone1=:phone1, phone2Type=:phone2Type, phone2CountryCode=:phone2CountryCode, phone2=:phone2, profession=:profession, employer=:employer, fields=:parent2fields
VALUES (SELECT FROM gibbonApplicationForm)




SELECT CONCAT('G1-', gibbonPerson.gibbonPersonID), '',  FROM gibbonApplicationForm 
    JOIN gibbonPerson ON (gibbonApplicationForm.username = gibbonPerson.username)


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