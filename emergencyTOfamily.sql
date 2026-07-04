6831 total

2693 family
4138 nulls

 470 nulls with    emergency1name
3668 nulls without emergency1name


GET GUARDIANS
--------------
SELECT username,
CONCAT('E1-',gibbonPerson.username) AS username1,
CONCAT('E2-',gibbonPerson.username) AS username2,
gibbonPerson.emergency1name AS name1,
gibbonPerson.emergency2name AS name2,
gibbonPerson.emergency1Relationship AS Relationship1,
gibbonPerson.emergency2Relationship AS Relationship2,
gibbonPerson.emergency1Number1 AS phone1_,
gibbonPerson.emergency2Number1 AS phone2_,
gibbonPerson.address1 AS address_,
gibbonPerson.address1District AS district_,
gibbonPerson.address1Country AS country_,
gibbonPerson.religion AS religion_
FROM gibbonPerson
LEFT JOIN gibbonFamilyChild ON gibbonPerson.gibbonPersonID = gibbonFamilyChild.gibbonPersonID
WHERE gibbonFamilyChild.gibbonPersonID IS NULL
AND gibbonRoleIDPrimary = '003'
AND gibbonPerson.emergency1Name <> ''


GET gibbonFamily
----------------
SELECT
child.gibbonPersonID AS gibbonFamily.


GET gibbonFamilyChild
---------------------



GET gibbonFamilyAdult 1

GET gibbonFamilyAdult 2

GET gibbonFamilyRelationship
