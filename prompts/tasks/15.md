# Objective
## Data Seeder Objective

---

## Description
Seed data for students, sections, subjects, and assessments into the system. The seeding includes generating 100 student records with specified naming and identification formats. It also involves creating 5 sections linked to teachers, assigning specific subjects to each section, and creating 4 assessment records per subject/section. The questions for assessments are to be left blank.

---

## Primary Objective
Seed data into the system for students, sections, subjects, and assessments.

---

## Secondary Objectives
- Seed 100 student records
- Seed 5 sections linked to teachers
- Seed 5 subjects assigned to specific sections
- Seed 4 assessments for each subject/section combination

---

## Supporting Tasks

### Student Data Seeding
- Generate 100 student records
- Use actual Filipino realistic names
- Assign student numbers in the format: 2026-XXXXX
- Generate email addresses combining first name and surname in the format: surname.firstname@qyzen.edu.ph

### Section Data Seeding
- Seed 5 sections with the following names: BSIT12A1, BSIT21M4, BSIT31M4, BSIT41M2, BSIT11E1
- Ensure each section is bound to a teacher

### Subject Data Seeding
- Seed 5 subjects with the following specifications:
  - Code: IT102, Subject: COMPUTER PROGRAMMING 1, Designated: BSIT11E1
  - Code: IT206, Subject: WEB SYSTEM TECHNOLOGIES 2, Designated: BSIT12A1
  - Code: IT309, Subject: HUMAN COMPUTER INTERACTION 2, Designated: BSIT31M4
  - Code: IT439, Subject: TECHNOPRENEURSHIP, Designated: BSIT41M2
  - Code: IT202, Subject: INTERACTIVE MEDIA DESIGN, Designated: BSIT21M4

### Assessment Data Seeding
- Create 4 assessments for each subject/section combination
- Assessments: Quiz #1, Quiz #2, Quiz #3, and Long Quiz
- Leave questions for these assessments blank

### Student Enrollment
- Enroll the 100 students into sections
- Different enrolled students per subject/section

---

## Detailed Breakdown

### Student Generation
Generate 100 students with Filipino realistic names. Each student must have a student number following the format 2026-XXXXX. Email addresses must be created by combining the first name and surname in the format surname.firstname@qyzen.edu.ph.

### Section and Teacher Binding
Create 5 specific sections: BSIT12A1, BSIT21M4, BSIT31M4, BSIT41M2, and BSIT11E1. Each section must be bound to a teacher.

### Subject Assignment
Seed the 5 specified subjects, each assigned to its designated section as provided.

### Assessment Creation
For each subject/section, create 4 assessments: Quiz #1, Quiz #2, Quiz #3, and Long Quiz.

#### Nested Details
- Assessment questions are to be left blank for later upload by the user

### Student Enrollment
Enroll students into sections. Ensure that different students are enrolled per subject/section.