<?
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

//This file describes the module, including database tables

//Basic variables
$name="Higher Education" ;
$description="A module to support students as they undertake the higher education application process." ;
$entryURL="index.php" ;
$type="Additional" ;
$category="Other" ;
$version="1.0.01" ;
$author="Ross Parker" ;
$url="http://rossparker.org" ;

//Module tables
$moduleTables[0]="CREATE TABLE `higherEducationMajor` (`higherEducationMajorID` int(6) unsigned zerofill NOT NULL auto_increment, `name` varchar(100) NOT NULL default '', `active` enum('Y','N') NOT NULL default 'Y', PRIMARY KEY  (`higherEducationMajorID`)) ENGINE=MyISAM AUTO_INCREMENT=195 ;" ;
$moduleTables[1]="INSERT INTO `higherEducationMajor` VALUES (000004, 'Accounting', 'Y'), (000005, 'Accounting Management', 'Y'), (000006, 'Actuarial Science', 'Y'), (000007, 'Advertising/Theatre Arts', 'Y'), (000008, 'American Studies', 'Y'), (000009, 'Anthropology', 'Y'), (000010, 'Archeology', 'Y'), (000011, 'Architecture', 'Y'), (000012, 'Architecture - Landscape', 'Y'), (000013, 'Art', 'Y'), (000014, 'Art - Foundation', 'Y'), (000016, 'Art & Design', 'Y'), (000017, 'Arts', 'Y'), (000018, 'Arts - Institution transfer programme', 'Y'), (000019, 'Assoc Degree', 'Y'), (000020, 'Assos Degree - Business', 'Y'), (000021, 'Assos Degree - Hospitality', 'Y'), (000022, 'Astrophysics', 'Y'), (000023, 'Auto Engineering', 'Y'), (000024, 'Biochemistry', 'Y'), (000025, 'Bioinformatics', 'Y'), (000026, 'Biology', 'Y'), (000027, 'Biomedical Science', 'Y'), (000028, 'Bio-medicine', 'Y'), (000029, 'Biotechnology', 'Y'), (000030, 'Business', 'Y'), (000033, 'Business Administration', 'Y'), (000034, 'Business Management', 'Y'), (000035, 'Business Management - European', 'Y'), (000036, 'Business Management & Spanish', 'Y'), (000037, 'Business Marketing', 'Y'), (000038, 'Business Studies', 'Y'), (000039, 'Commerce', 'Y'), (000040, 'Communications', 'Y'), (000041, 'Communications Studies', 'Y'), (000042, 'Computer Engineering', 'Y'), (000043, 'Computer Science', 'Y'), (000044, 'Computer Science & Management', 'Y'), (000045, 'Computer Sciences', 'Y'), (000046, 'Computing', 'Y'), (000047, 'Dentistry', 'Y'), (000048, 'Design', 'Y'), (000049, 'Design - Fashion', 'Y'), (000050, 'Design - Graphic', 'Y'), (000051, 'Design - Textiles', 'Y'), (000052, 'Design Studies', 'Y'), (000053, 'Drama', 'Y'), (000054, 'Drama - Theatre Arts', 'Y'), (000055, 'Economics', 'Y'), (000056, 'Economics & Politics', 'Y'), (000057, 'Economics/Arts', 'Y'), (000058, 'Education', 'Y'), (000059, 'Engineering', 'Y'), (000060, 'Engineering  - Civil', 'Y'), (000061, 'Engineering - Aeronautical', 'Y'), (000063, 'Engineering - Biochemical', 'Y'), (000064, 'Engineering - Biomedical', 'Y'), (000066, 'Engineering - Chemical', 'Y'), (000067, 'Engineering - Civil', 'Y'), (000068, 'Engineering - Computer', 'Y'), (000070, 'Engineering - Computer Software', 'Y'), (000071, 'Engineering - Electrical', 'Y'), (000073, 'Engineering - Mechanical', 'Y'), (000075, 'Engineering - Systems', 'Y'), (000076, 'Engineering, Arts', 'Y'), (000077, 'English', 'Y'), (000078, 'English & Film', 'Y'), (000079, 'English Language', 'Y'), (000080, 'Environmental Design', 'Y'), (000081, 'Environmental Science', 'Y'), (000082, 'Environmental Sciences', 'Y'), (000083, 'Equine Management', 'Y'), (000084, 'Equine Studies', 'Y'), (000085, 'Ethics/Law', 'Y'), (000086, 'Events Management', 'Y'), (000087, 'Exercise Science', 'Y'), (000088, 'Fashion', 'Y'), (000089, 'Fashion Design', 'Y'), (000090, 'Film & TV Studies', 'Y'), (000091, 'Finance', 'Y'), (000092, 'Food Technology', 'Y'), (000093, 'Food/Nutritional Health', 'Y'), (000094, 'Forensic Science', 'Y'), (000095, 'French', 'Y'), (000096, 'French/German', 'Y'), (000097, 'French/Italian', 'Y'), (000098, 'French/Spanish', 'Y'), (000099, 'Genetics', 'Y'), (000100, 'Geography', 'Y'), (000101, 'Geography/Environmental', 'Y'), (000102, 'Geography/Geology', 'Y'), (000103, 'Geology', 'Y'), (000104, 'Graphic Design', 'Y'), (000105, 'High School Diploma programme', 'Y'), (000106, 'History', 'Y'), (000107, 'History & Government', 'Y'), (000108, 'History/Politics', 'Y'), (000109, 'Hospitality', 'Y'), (000110, 'Hospitality Management', 'Y'), (000111, 'Hotel Management', 'Y'), (000112, 'Human Resource Management', 'Y'), (000113, 'Humanities', 'Y'), (000114, 'Illustration', 'Y'), (000115, 'Industrial Design', 'Y'), (000116, 'Industrial Relations', 'Y'), (000117, 'Information System Management', 'Y'), (000118, 'Information Technology', 'Y'), (000119, 'International Business', 'Y'), (000120, 'International Fashion Marketing', 'Y'), (000121, 'International Relations', 'Y'), (000122, 'Japanese Studies & Sociology', 'Y'), (000123, 'Journalism', 'Y'), (000124, 'Kinesiology', 'Y'), (000125, 'Law', 'Y'), (000126, 'Law & German', 'Y'), (000127, 'Law/Criminology', 'Y'), (000128, 'Leisure Management', 'Y'), (000129, 'Leisure/Sports', 'Y'), (000130, 'Liberal Arts', 'Y'), (000131, 'Liberal Arts - Biology', 'Y'), (000132, 'Liberal Arts - Economics', 'Y'), (000133, 'Liberal Arts - Philosophy', 'Y'), (000134, 'Linguistics', 'Y'), (000135, 'Management', 'Y'), (000136, 'Management Studies', 'Y'), (000137, 'Marine Sports Technology', 'Y'), (000138, 'Marketing', 'Y'), (000139, 'Mathematics', 'Y'), (000140, 'Mechanical Studies', 'Y'), (000141, 'Media', 'Y'), (000142, 'Media / Psychology', 'Y'), (000143, 'Media Communication', 'Y'), (000144, 'Medicine', 'Y'), (000145, 'Music', 'Y'), (000146, 'Music & English', 'Y'), (000147, 'Neuroscience', 'Y'), (000148, 'Nursing', 'Y'), (000149, 'Nutrition', 'Y'), (000150, 'Optometry', 'Y'), (000151, 'Pharmacy', 'Y'), (000152, 'Philosophy', 'Y'), (000153, 'Philosophy & Economics', 'Y'), (000154, 'Philosophy & Political Science', 'Y'), (000155, 'Philosophy, Politics & Economics', 'Y'), (000156, 'Photography', 'Y'), (000157, 'Phys/Comp Science', 'Y'), (000158, 'Physical Education', 'Y'), (000159, 'Physics', 'Y'), (000160, 'Physics  &Computer Science', 'Y'), (000161, 'Physiotherapy', 'Y'), (000162, 'Political Science', 'Y'), (000163, 'Politics', 'Y'), (000164, 'Politics & Economics', 'Y'), (000165, 'Politics & International Relations', 'Y'), (000166, 'Product Design', 'Y'), (000167, 'Psychology', 'Y'), (000168, 'Psychology & Linguistics', 'Y'), (000169, 'Psychology, Social Science', 'Y'), (000170, 'Psychology/Philosophy', 'Y'), (000171, 'Real Estate Management', 'Y'), (000172, 'Science', 'Y'), (000173, 'Science - Institution transfer programme', 'Y'), (000174, 'Science with Music', 'Y'), (000175, 'Sciences', 'Y'), (000176, 'Social Science', 'Y'), (000177, 'Socio-cultural Anthropology', 'Y'), (000178, 'Sociology', 'Y'), (000179, 'Spanish/Russian', 'Y'), (000180, 'Speech Science', 'Y'), (000181, 'Sports Development', 'Y'), (000182, 'Sports Management', 'Y'), (000183, 'Sports Science', 'Y'), (000184, 'Sports Studies', 'Y'), (000185, 'Teaching', 'Y'), (000186, 'Tourism & Travel', 'Y'), (000187, 'Tourism / Hospitality', 'Y'), (000188, 'Travel & Tourism', 'Y'), (000189, 'Institution transfer programme', 'Y'), (000190, 'Veterinary Science', 'Y'), (000191, 'Visual & Performing Arts', 'Y'), (000192, 'Visual Communications', 'Y'), (000193, 'Zoology', 'Y'), (000194, 'Life Sciences', 'Y');" ;
$moduleTables[2]="CREATE TABLE `higherEducationInstitution` ( `higherEducationInstitutionID` int(6) unsigned zerofill NOT NULL auto_increment, `name` varchar(150) NOT NULL default '', `country` varchar(80) NOT NULL default '', `active` enum('Y','N') NOT NULL default 'Y', PRIMARY KEY  (`higherEducationInstitutionID`)) ENGINE=MyISAM AUTO_INCREMENT=202 ;" ;
$moduleTables[3]="INSERT INTO `higherEducationInstitution` VALUES (000001, 'University Aberdeen', 'United Kingdom', 'Y'),(000002, 'University of Adelaide', 'Australia', 'Y'),(000003, 'University of Alberta', 'Canada', 'Y'),(000004, 'Baptist University', 'Hong Kong', 'Y'),(000005, 'University of Bath', 'United Kingdom', 'Y'),(000006, 'Bentley College', 'United States', 'Y'),(000007, 'Biola University', 'United States', 'Y'),(000008, 'University of Birmingham', 'United Kingdom', 'Y'),(000009, 'Birmingham College of Food', 'United Kingdom', 'Y'),(000010, 'Boston University', 'United States', 'Y'),(000011, 'Bournemouth Institute of Art & Design', 'United Kingdom', 'Y'),(000012, 'Brandeis University', 'United States', 'Y'),(000013, 'University of Bristol', 'United Kingdom', 'Y'),(000014, 'University of British Columbia', 'Canada', 'Y'),(000015, 'Brunel University', 'United Kingdom', 'Y'),(000016, 'University of Buckingham', 'United Kingdom', 'Y'),(000017, 'University of Cambridge', 'United Kingdom', 'Y'),(000018, 'Cardiff University', 'United Kingdom', 'Y'),(000019, 'Carnegie Mellon University', 'United States', 'Y'),(000020, 'Centennial College', 'Canada', 'Y'),(000021, 'University of Central Lancashire', 'United Kingdom', 'Y'),(000022, 'University of Chicago', 'United States', 'Y'),(000023, 'Chinese University of Hong Kong', 'Hong Kong', 'Y'),(000024, 'City University of Hong Kong', 'Hong Kong', 'Y'),(000025, 'Columbus College of Art and Design', 'United States', 'Y'),(000026, 'Cornell University', 'United States', 'Y'),(000027, 'De Montfort University', 'United Kingdom', 'Y'),(000028, 'Delta College', 'United States', 'Y'),(000029, 'Diablo Valley College', 'United States', 'Y'),(000030, 'Duke University', 'United States', 'Y'),(000031, 'University of Dundee', 'United Kingdom', 'Y'),(000032, 'Durham University', 'United Kingdom', 'Y'),(000033, 'University of East London', 'United Kingdom', 'Y'),(000034, 'Ecole de Hoteliere', 'Switzerland', 'Y'),(000035, 'University of Edinburgh', 'United Kingdom', 'Y'),(000036, 'University of Duisburg-Essen', 'Germany', 'Y'),(000037, 'University of Essex', 'United Kingdom', 'Y'),(000038, 'University of Exeter', 'United Kingdom', 'Y'),(000039, 'Falmouth College of Art', 'United Kingdom', 'Y'),(000040, 'Fashion Institute of Technology', 'United States', 'Y'),(000041, 'Fordham University', 'United States', 'Y'),(000042, 'Georgetown University', 'United States', 'Y'),(000043, 'University of Glasgow', 'United Kingdom', 'Y'),(000044, 'Griffith University', 'Australia', 'Y'),(000046, 'Helsinki UUniversity of Technology', 'Finland', 'Y'),(000047, 'Hong Kong Polytechnic University', 'Hong Kong', 'Y'),(000048, 'HK University of Science and Technology', 'Hong Kong', 'Y'),(000049, 'Hong Kong University', 'Hong Kong', 'Y'),(000050, 'University of Hull', 'United Kingdom', 'Y'),(000051, 'University of Lincoln', 'United Kingdom', 'Y'),(000052, 'Imperial College, University of London', 'United Kingdom', 'Y'),(000053, 'Johns Hopkins University', 'United States', 'Y'),(000054, 'Keele University', 'United Kingdom', 'Y'),(000055, 'University of Kent', 'United Kingdom', 'Y'),(000056, 'Kent Institute of Art & Design', 'United Kingdom', 'Y'),(000057, 'King\'s College London', 'United Kingdom', 'Y'),(000058, 'Kingston University London', 'United Kingdom', 'Y'),(000059, 'La Trobe University', 'Australia', 'Y'),(000060, 'Lancaster University', 'United Kingdom', 'Y'),(000061, 'Leeds Metropolitan University', 'United Kingdom', 'Y'),(000062, 'University of Leeds', 'United Kingdom', 'Y'),(000063, 'University of Leicester', 'United Kingdom', 'Y'),(000064, 'University of Liverpool', 'United Kingdom', 'Y'),(000065, 'London College of Fashion', 'United Kingdom', 'Y'),(000066, 'London College of Printing', 'United Kingdom', 'Y'),(000067, 'London School of Economics', 'United Kingdom', 'Y'),(000068, 'University of Manchester', 'United Kingdom', 'Y'),(000069, 'Manchester Metropolitan University', 'United Kingdom', 'Y'),(000070, 'Maryland College of Art', 'United States', 'Y'),(000071, 'McMaster University', 'Canada', 'Y'),(000072, 'University of Melbourne', 'Australia', 'Y'),(000073, 'Miami University', 'United States', 'Y'),(000074, 'University of Michigan', 'United States', 'Y'),(000075, 'Michigan State University', 'United States', 'Y'),(000076, 'Midrand Graduate Institute', 'South Africa', 'Y'),(000077, 'Mount Alinson College', 'Canada', 'Y'),(000078, 'Mount Holyoke College', 'United States', 'Y'),(000079, 'Murdoch University', 'Australia', 'Y'),(000080, 'University of New South Wales', 'Australia', 'Y'),(000081, 'New York University', 'United States', 'Y'),(000082, 'Newcastle University', 'United Kingdom', 'Y'),(000083, 'University of North London', 'United Kingdom', 'Y'),(000084, 'North Virginia Community College', 'United States', 'Y'),(000085, 'Northeastern University', 'United States', 'Y'),(000086, 'Northwestern University', 'United States', 'Y'),(000087, 'University of Nottingham', 'United Kingdom', 'Y'),(000088, 'Ohio State University', 'United States', 'Y'),(000089, 'University of Oxford', 'United Kingdom', 'Y'),(000090, 'Pasadena City College', 'United States', 'Y'),(000091, 'Pennsylvania State University', 'United States', 'Y'),(000092, 'University of Pittsburgh', 'United States', 'Y'),(000093, 'University of Plymouth', 'United Kingdom', 'Y'),(000095, 'Princeton University', 'United States', 'Y'),(000096, 'Queen Mary College', 'United Kingdom', 'Y'),(000097, 'Queen\'s University', 'Canada', 'Y'),(000098, 'University of Queensland', 'Australia', 'Y'),(000099, 'University of Reading', 'United Kingdom', 'Y'),(000100, 'RMIT', 'Australia', 'Y'),(000101, 'Royal Holloway, University of London', 'United Kingdom', 'Y'),(000102, 'Rutgers', 'United States', 'Y'),(000103, 'School of Oriental and African Studies', 'United Kingdom', 'Y'),(000104, 'University of San Francisco', 'United States', 'Y'),(000105, 'Santa Clara University', 'United States', 'Y'),(000106, 'Sarah Lawrence College', 'United States', 'Y'),(000107, 'University of Sheffield', 'United Kingdom', 'Y'),(000108, 'Sheffield Hallam University', 'United Kingdom', 'Y'),(000109, 'Simon Fraser University', 'Canada', 'Y'),(000110, 'Skyline College', 'United States', 'Y'),(000111, 'University of South Australia', 'Australia', 'Y'),(000112, 'University of Southampton', 'United Kingdom', 'Y'),(000113, 'University of Southern California', 'United States', 'Y'),(000116, 'Stanford University', 'United States', 'Y'),(000117, 'Stellenbosch', 'South Africa', 'Y'),(000118, 'University of Sussex', 'United Kingdom', 'Y'),(000119, 'Swansea University', 'United Kingdom', 'Y'),(000120, 'Swarthmore College', 'United States', 'Y'),(000121, 'University of Sydney', 'Australia', 'Y'),(000122, 'Syracuse University', 'United States', 'Y'),(000123, 'University of West London', 'United Kingdom', 'Y'),(000124, 'University of Toronto', 'Canada', 'Y'),(000126, 'Tufts University', 'United States', 'Y'),(000127, 'University College - London', 'United Kingdom', 'Y'),(000128, 'University of California - Davis', 'United States', 'Y'),(000129, 'University of California - Irvine', 'United States', 'Y'),(000130, 'University of California - Los Angeles', 'United States', 'Y'),(000131, 'University of California - San Diego', 'United States', 'Y'),(000132, 'University of Western England', 'United Kingdom', 'Y'),(000133, 'University of Waikato', 'New Zealand', 'Y'),(000135, 'University of Warwick', 'United Kingdom', 'Y'),(000137, 'University of Waterloo', 'Canada', 'Y'),(000139, 'University of Western Ontario', 'Canada', 'Y'),(000140, 'University of Wolverhampton', 'United Kingdom', 'Y'),(000141, 'Yale University', 'United States', 'Y'),(000142, 'University of York', 'Canada', 'Y'),(000143, 'University of Guelph', 'Canada', 'Y'),(000144, 'McGill University', 'Canada', 'Y'),(000145, 'Ottawa University', 'Canada', 'Y'),(000146, 'Ryerson University', 'Canada', 'Y'),(000147, 'University of Victoria', 'Canada', 'Y'),(000148, 'University of Pennsylvania', 'United States', 'Y'),(000149, 'Rijksuniversiteit Groningen', 'Netherlands', 'Y'),(000150, 'Wageningen University', 'Netherlands', 'Y'),(000151, 'St George\'s, University of London', 'United Kingdom', 'Y'),(000152, 'University of Southern Denmark', 'Denmark', 'Y'),(000153, 'University of Copenhagen', 'Denmark', 'Y'),(000154, 'Aarhus University', 'Denmark', 'Y'),(000155, 'Aalborg University', 'Denmark', 'Y'),(000156, 'Royal Veterinary College - London', 'United Kingdom', 'Y'),(000157, 'University of East Anglia', 'United Kingdom', 'Y'),(000158, 'University of the Arts London', 'United Kingdom', 'Y'),(000159, 'City University London', 'United Kingdom', 'Y'),(000160, 'Wake Forest University', 'United States', 'Y'),(000161, 'School of Hotel and Tourism Management', 'Switzerland', 'Y'),(000162, 'University of California - Berkeley', 'United States', 'Y'),(000164, 'University of Minnesota - Twin Cities', 'United States', 'Y'),(000165, 'Bournemouth University', 'United Kingdom', 'Y'),(000166, 'Southampton Solent University', 'United Kingdom', 'Y'),(000167, 'Staffordshire University', 'United Kingdom', 'Y'),(000169, 'Emory University', 'United States', 'Y'),(000170, 'University of Westminster', 'United Kingdom', 'Y'),(000171, 'Barnard College', 'United States', 'Y'),(000172, 'Vassar College', 'United States,', 'Y'),(000173, 'Bard College', 'United States', 'Y'),(000174, 'National University of Singapore', 'Singapore', 'Y'),(000175, 'Singapore Management University', 'Singapore', 'Y'),(000176, 'Massey University', 'New Zealand', 'Y'),(000177, 'University of Auckland', 'New Zealand', 'Y'),(000178, 'University of Otago', 'New Zealand', 'Y'),(000179, 'University of Connecticut', 'United States', 'Y'),(000180, 'University of Wisconsin - Madison', 'United States', 'Y'),(000181, 'Monash University', 'Australia', 'Y'),(000182, 'Pepperdine University', 'United States', 'Y'),(000183, 'Occidental College', 'United States', 'Y'),(000184, 'American University', 'United States', 'Y'),(000185, 'Claremont McKenna College', 'United States', 'Y'),(000186, 'Harvard University', 'United States', 'Y'),(000187, 'Washington University - St. Louis', 'United States', 'Y'),(000188, 'Ryerson University', 'Canada', 'Y'),(000189, 'York University', 'Canada', 'Y'),(000191, 'César Ritz Colleges', 'Switzerland', 'Y'),(000193, 'School of Audio Engineering', 'United Arab Emirates', 'Y'),(000194, 'Goldsmith College', 'United Kingdom', 'Y'),(000195, 'University of Sunderland', 'United Kingdom', 'Y'),(000196, 'University of Huddersfield', 'United Kingdom', 'Y'),(000197, 'Loughborough University', 'United Kingdom', 'Y'),(000198, 'Nottingham Trent University', 'United Kingdom', 'Y'),(000199, 'Carleton University', 'Canada', 'Y'),(000200, 'Berklee School of Music', 'United States', 'Y'),(000201, 'Parson\'s School of Design', 'United States', 'Y');" ;
$moduleTables[4]="CREATE TABLE `higherEducationStaff` (`higherEducationStaffID` int(4) unsigned zerofill NOT NULL AUTO_INCREMENT, `gibbonPersonID` int(10) unsigned zerofill NOT NULL, `role` enum('Coordinator','Advisor') NOT NULL, PRIMARY KEY (`higherEducationStaffID`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;" ;
$moduleTables[5]="CREATE TABLE `higherEducationStudent` (  `higherEducationStudentID` int(10) unsigned zerofill NOT NULL AUTO_INCREMENT,  `gibbonPersonID` int(10) unsigned zerofill NOT NULL,  `gibbonPersonIDAdvisor` int(10) unsigned zerofill DEFAULT NULL,  `referenceNotes` text NOT NULL,  PRIMARY KEY (`higherEducationStudentID`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;" ;
$moduleTables[6]="CREATE TABLE `higherEducationApplication` ( `higherEducationApplicationID` int(8) unsigned zerofill NOT NULL auto_increment, `gibbonPersonID` int(10) unsigned zerofill NOT NULL, `applying` enum('Y','N') NOT NULL default 'Y', `otherScores` text, `careerInterests` text, `coursesMajors` text, `personalStatement` text NOT NULL, `meetingNotes` text NOT NULL, PRIMARY KEY  (`higherEducationApplicationID`), UNIQUE KEY `gibbonPersonID` (`gibbonPersonID`)) ENGINE=MyISAM ;" ;
$moduleTables[7]="CREATE TABLE `higherEducationApplicationInstitution` ( `higherEducationApplicationInstitutionID` int(10) unsigned zerofill NOT NULL auto_increment, `higherEducationApplicationID` int(8) unsigned zerofill NOT NULL, `higherEducationInstitutionID` int(6) unsigned zerofill NOT NULL, `higherEducationMajorID` int(6) unsigned zerofill NOT NULL, `scholarship` text, `applicationNumber` varchar(50) default '', `rank` varchar(10) default '', `rating` varchar(100) default '', `status` varchar(100) default '', `question` text default '', `answer` text default '', `offer` enum('','First Choice','Backup','Y','N') NOT NULL default '', `offerDetails` text default '', PRIMARY KEY  (`higherEducationApplicationInstitutionID`)) ENGINE=MyISAM ;" ;
$moduleTables[8]="CREATE TABLE `higherEducationReference` (  `higherEducationReferenceID` int(12) unsigned zerofill NOT NULL AUTO_INCREMENT,  `gibbonPersonID` int(10) unsigned zerofill NOT NULL,  `gibbonSchoolYearID` int(3) unsigned zerofill NOT NULL,  `type` enum('Composite Reference','US Reference','') NOT NULL,  `status` enum('Pending','In Progress','Complete','Cancelled') NOT NULL,  `statusNotes` varchar(255) NOT NULL,  `notes` text NOT NULL,  `alertsSent` enum('N','Y') NOT NULL DEFAULT 'N',  `timestamp` timestamp NULL DEFAULT NULL,  PRIMARY KEY (`higherEducationReferenceID`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;" ;
$moduleTables[9]="CREATE TABLE `higherEducationReferenceComponent` (  `higherEducationReferenceComponentID` int(14) unsigned zerofill NOT NULL AUTO_INCREMENT,  `higherEducationReferenceID` int(12) unsigned zerofill NOT NULL,  `gibbonPersonID` int(10) unsigned zerofill NOT NULL COMMENT 'Referee',  `status` enum('Pending','In Progress','Complete') NOT NULL,  `type` enum('General','Academic','Pastoral','Other') NOT NULL,  `title` varchar(100) NOT NULL,  `body` text NOT NULL,  PRIMARY KEY (`higherEducationReferenceComponentID`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 ;" ;

//Action rows
$actionRows[0]["name"]="Manage Institutions" ;
$actionRows[0]["precedence"]="0";
$actionRows[0]["category"]="Admin" ;
$actionRows[0]["description"]="Allows admins to manage list of institutions." ;
$actionRows[0]["URLList"]="institutions_manage.php, institutions_manage_add.php, institutions_manage_edit.php, institutions_manage_delete.php" ;
$actionRows[0]["entryURL"]="institutions_manage.php" ;
$actionRows[0]["defaultPermissionAdmin"]="Y" ;
$actionRows[0]["defaultPermissionTeacher"]="N" ;
$actionRows[0]["defaultPermissionStudent"]="N" ;
$actionRows[0]["defaultPermissionParent"]="N" ;
$actionRows[0]["defaultPermissionSupport"]="N" ;
$actionRows[0]["categoryPermissionStaff"]="Y" ;
$actionRows[0]["categoryPermissionStudent"]="N" ;
$actionRows[0]["categoryPermissionParent"]="N" ;
$actionRows[0]["categoryPermissionOther"]="N" ;

$actionRows[1]["name"]="Manage Majors" ;
$actionRows[1]["precedence"]="0";
$actionRows[1]["category"]="Admin" ;
$actionRows[1]["description"]="Allows admins to manage list of majors." ;
$actionRows[1]["URLList"]="majors_manage.php, majors_manage_add.php, majors_manage_edit.php, majors_manage_delete.php" ;
$actionRows[1]["entryURL"]="majors_manage.php" ;
$actionRows[1]["defaultPermissionAdmin"]="Y" ;
$actionRows[1]["defaultPermissionTeacher"]="N" ;
$actionRows[1]["defaultPermissionStudent"]="N" ;
$actionRows[1]["defaultPermissionParent"]="N" ;
$actionRows[1]["defaultPermissionSupport"]="N" ;
$actionRows[1]["categoryPermissionStaff"]="Y" ;
$actionRows[1]["categoryPermissionStudent"]="N" ;
$actionRows[1]["categoryPermissionParent"]="N" ;
$actionRows[1]["categoryPermissionOther"]="N" ;

//Action rows
$actionRows[2]["name"]="Manage Staff" ;
$actionRows[2]["precedence"]="2";
$actionRows[2]["category"]="Admin" ;
$actionRows[2]["description"]="Allows admins to manage staff roles." ;
$actionRows[2]["URLList"]="staff_manage.php, staff_manage_add.php, staff_manage_edit.php, staff_manage_delete.php" ;
$actionRows[2]["entryURL"]="staff_manage.php" ;
$actionRows[2]["defaultPermissionAdmin"]="Y" ;
$actionRows[2]["defaultPermissionTeacher"]="N" ;
$actionRows[2]["defaultPermissionStudent"]="N" ;
$actionRows[2]["defaultPermissionParent"]="N" ;
$actionRows[2]["defaultPermissionSupport"]="N" ;
$actionRows[2]["categoryPermissionStaff"]="Y" ;
$actionRows[2]["categoryPermissionStudent"]="N" ;
$actionRows[2]["categoryPermissionParent"]="N" ;
$actionRows[2]["categoryPermissionOther"]="N" ;

$actionRows[3]["name"]="Manage Students" ;
$actionRows[3]["precedence"]="0";
$actionRows[3]["category"]="Admin" ;
$actionRows[3]["description"]="Allows admins to manage students." ;
$actionRows[3]["URLList"]="student_manage.php, student_manage_add.php, student_manage_edit.php, student_manage_delete.php" ;
$actionRows[3]["entryURL"]="student_manage.php" ;
$actionRows[3]["defaultPermissionAdmin"]="Y" ;
$actionRows[3]["defaultPermissionTeacher"]="N" ;
$actionRows[3]["defaultPermissionStudent"]="N" ;
$actionRows[3]["defaultPermissionParent"]="N" ;
$actionRows[3]["defaultPermissionSupport"]="N" ;
$actionRows[3]["categoryPermissionStaff"]="Y" ;
$actionRows[3]["categoryPermissionStudent"]="N" ;
$actionRows[3]["categoryPermissionParent"]="N" ;
$actionRows[3]["categoryPermissionOther"]="N" ;

$actionRows[4]["name"]="Track Applications" ;
$actionRows[4]["precedence"]="0";
$actionRows[4]["category"]="Applications" ;
$actionRows[4]["description"]="Allows users to track their own higher education applications." ;
$actionRows[4]["URLList"]="applications_track.php, applications_track_add.php,  applications_track_edit.php,  applications_track_delete.php" ;
$actionRows[4]["entryURL"]="applications_track.php" ;
$actionRows[4]["defaultPermissionAdmin"]="N" ;
$actionRows[4]["defaultPermissionTeacher"]="N" ;
$actionRows[4]["defaultPermissionStudent"]="Y" ;
$actionRows[4]["defaultPermissionParent"]="N" ;
$actionRows[4]["defaultPermissionSupport"]="N" ;
$actionRows[4]["categoryPermissionStaff"]="N" ;
$actionRows[4]["categoryPermissionStudent"]="Y" ;
$actionRows[4]["categoryPermissionParent"]="N" ;
$actionRows[4]["categoryPermissionOther"]="N" ;

$actionRows[5]["name"]="View Applications" ;
$actionRows[5]["precedence"]="0";
$actionRows[5]["category"]="Applications" ;
$actionRows[5]["description"]="Allows staff to view student higher education applications." ;
$actionRows[5]["URLList"]="applications_view.php, applications_view_details.php" ;
$actionRows[5]["entryURL"]="applications_view.php" ;
$actionRows[5]["defaultPermissionAdmin"]="Y" ;
$actionRows[5]["defaultPermissionTeacher"]="Y" ;
$actionRows[5]["defaultPermissionStudent"]="N" ;
$actionRows[5]["defaultPermissionParent"]="N" ;
$actionRows[5]["defaultPermissionSupport"]="N" ;
$actionRows[5]["categoryPermissionStaff"]="Y" ;
$actionRows[5]["categoryPermissionStudent"]="N" ;
$actionRows[5]["categoryPermissionParent"]="N" ;
$actionRows[5]["categoryPermissionOther"]="N" ;

$actionRows[6]["name"]="Edit My Reference Notes" ;
$actionRows[6]["precedence"]="0";
$actionRows[6]["category"]="References" ;
$actionRows[6]["description"]="Allows students to share some notes with referees, outlining their achievements." ;
$actionRows[6]["URLList"]="references_myNotes.php" ;
$actionRows[6]["entryURL"]="references_myNotes.php" ;
$actionRows[6]["defaultPermissionAdmin"]="N" ;
$actionRows[6]["defaultPermissionTeacher"]="N" ;
$actionRows[6]["defaultPermissionStudent"]="Y" ;
$actionRows[6]["defaultPermissionParent"]="N" ;
$actionRows[6]["defaultPermissionSupport"]="N" ;
$actionRows[6]["categoryPermissionStaff"]="N" ;
$actionRows[6]["categoryPermissionStudent"]="Y" ;
$actionRows[6]["categoryPermissionParent"]="N" ;
$actionRows[6]["categoryPermissionOther"]="N" ;

$actionRows[7]["name"]="Request References" ;
$actionRows[7]["precedence"]="0";
$actionRows[7]["category"]="References" ;
$actionRows[7]["description"]="Allows students to request that a reference be written for them." ;
$actionRows[7]["URLList"]="references_request.php, references_request_add.php" ;
$actionRows[7]["entryURL"]="references_request.php" ;
$actionRows[7]["defaultPermissionAdmin"]="N" ;
$actionRows[7]["defaultPermissionTeacher"]="N" ;
$actionRows[7]["defaultPermissionStudent"]="Y" ;
$actionRows[7]["defaultPermissionParent"]="N" ;
$actionRows[7]["defaultPermissionSupport"]="N" ;
$actionRows[7]["categoryPermissionStaff"]="N" ;
$actionRows[7]["categoryPermissionStudent"]="Y" ;
$actionRows[7]["categoryPermissionParent"]="N" ;
$actionRows[7]["categoryPermissionOther"]="N" ;

$actionRows[8]["name"]="Manage References" ;
$actionRows[8]["precedence"]="0";
$actionRows[8]["category"]="References" ;
$actionRows[8]["description"]="Allows coordinators to see, approve and edit all references." ;
$actionRows[8]["URLList"]="references_manage.php, references_manage_edit.php, references_manage_delete.php" ;
$actionRows[8]["entryURL"]="references_manage.php" ;
$actionRows[8]["defaultPermissionAdmin"]="Y" ;
$actionRows[8]["defaultPermissionTeacher"]="N" ;
$actionRows[8]["defaultPermissionStudent"]="N" ;
$actionRows[8]["defaultPermissionParent"]="N" ;
$actionRows[8]["defaultPermissionSupport"]="N" ;
$actionRows[8]["categoryPermissionStaff"]="Y" ;
$actionRows[8]["categoryPermissionStudent"]="N" ;
$actionRows[8]["categoryPermissionParent"]="N" ;
$actionRows[8]["categoryPermissionOther"]="N" ;

$actionRows[9]["name"]="Write References" ;
$actionRows[9]["precedence"]="0";
$actionRows[9]["category"]="References" ;
$actionRows[9]["description"]="Allows teachers to contribute to those references that have been assigned to them." ;
$actionRows[9]["URLList"]="references_write.php, references_write_edit.php" ;
$actionRows[9]["entryURL"]="references_write.php" ;
$actionRows[9]["defaultPermissionAdmin"]="Y" ;
$actionRows[9]["defaultPermissionTeacher"]="Y" ;
$actionRows[9]["defaultPermissionStudent"]="N" ;
$actionRows[9]["defaultPermissionParent"]="N" ;
$actionRows[9]["defaultPermissionSupport"]="N" ;
$actionRows[9]["categoryPermissionStaff"]="Y" ;
$actionRows[9]["categoryPermissionStudent"]="N" ;
$actionRows[9]["categoryPermissionParent"]="N" ;
$actionRows[9]["categoryPermissionOther"]="N" ;



?>