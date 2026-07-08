# Objective
## Students Score Organization in Educator Side

---

## Description
The objective is to implement a proper organizational structure for student scores on the educator side of the platform. Currently, all score data is displayed without any filtration, making it difficult to navigate. The expected outcome is a filtered, sortable view of scores organized by subject, section, term, and other relevant criteria, with user information included as table columns.

---

## Objectives Breakdown

### 1. Primary Objective
Add filtration capabilities to the educator-side scores view so that data can be filtered by subject, section, term, and other criteria, replacing the current unfiltered display of all scores.

---

### 2. Secondary Objectives
- Include user information as columns within the scores table.
- Display the first column in a specific format showing profile icon, surname, and given name, sorted in ascending order by surname.

---

### 3. Supporting Tasks

#### 3.1 Filtration Implementation
- Add filters for subject
- Add filters for section
- Add filters for term
- Add filters for other criteria (as referenced in the input)

#### 3.2 Table Column Organization
- Include user information as table columns
- Retain existing columns (Assessment, Score, Subject, Section, and others)
- Format the first column to display: (PROFILE ICON) SURNAME GIVEN NAME

#### 3.3 Data Sorting
- Sort the initial data display in ascending order based on surname

---

### 4. Detailed Breakdown

#### 4.1 Current State Problem
The educator side currently displays all scores data without any filtration or proper organization, resulting in an unmanageable view.

#### 4.2 Filtration Requirements
Filters must be added for subject, section, term, and other unspecified criteria mentioned in the input.

#### 4.3 First Column Format
The first column must display data in the following format:
- Profile icon
- Surname (displayed first)
- Given name
- These entries must be sorted in ascending order by surname as the initial default view.

#### 4.4 Table Columns
The table must include the following columns:
- Formatted first column (Profile Icon, Surname, Given Name)
- Assessment
- Score
- Subject
- Section
- Other existing columns (retained as-is)