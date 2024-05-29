# testbe2904
 
Description
This project is built with the PHP framework Laravel and uses MySQL for the database.

Files
* database.txt: This file contains the database schema.
* Route file: routes/api.php
* Source code:
    * app/Http/Controllers/StudentController.php
    * app/Http/Controllers/UniversityController.php 


Database
```sql
CREATE TABLE university (
    university_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

CREATE TABLE student (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

CREATE TABLE university_student ( 
	university_id INT, 
	student_id INT, 
        PRIMARY KEY (university_id, student_id), 
        FOREIGN KEY (university_id) REFERENCES university(university_id) ON DELETE CASCADE, 
        FOREIGN KEY (student_id) REFERENCES student(student_id) ON DELETE CASCADE );

```


Note: The API POST/PUT for students can include universities together.

POST/PUT format:
* POST: university_id is optional. 
* PUT: name and university_id are optional.
```json
{ 
  "name": "Student Name", 
  "university_id": [1, 2] 
}
```