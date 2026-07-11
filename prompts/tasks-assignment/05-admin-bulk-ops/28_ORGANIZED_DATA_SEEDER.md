# Objective
## Organized Data Seeder

---

## Description
This objective refines the database seeding process to produce more realistic and complete sample data. The goal is to ensure that seeded student records have distinct Filipino surnames, every subject and section receives a complete set of assessments, and those assessments are populated with appropriate questions. Finally, user scores and answer records must be stored realistically and reflected in the notification system. The overall outcome is a test dataset that accurately mirrors production data in structure, variety, and behavior.

---

## Primary Objective
Refine the data seeder to generate distinct, realistic Filipino student records, complete assessments for all subjects and sections, populate those assessments with properly sized question sets, and store detailed user scores and answers that appear in notifications.

---

## Secondary Objectives
- Ensure all seeded students have unique Filipino surnames.
- Seed a full set of assessments (Quiz #1, #2, #3, and Long Quiz) for every subject and section combination.
- Populate each assessment with the correct number of questions: 10 items for Quizzes #1–3, and 30 items for the Long Quiz.
- Record user scores and answer details for realism, and ensure this data surfaces correctly in the notifications feature.

---

## Success Criteria
- All seeded student surnames are distinct Filipino names.
- Every subject-section pair has exactly four assessments: Quiz #1, Quiz #2, Quiz #3, and Long Quiz.
- Quizzes #1, #2, and #3 each contain exactly 10 questions.
- The Long Quiz contains exactly 30 questions.
- Seeded score records include both the final score and the user's specific answers.
- Score and answer data is visible in the notification system.

---

## Constraints
- The existing seeded section and subject data must remain unchanged.

---

## Context & Dependencies
- The current seeder generates student records with repetitive surnames.
- Not all subjects currently receive a full set of seeded assessments.
- The notification system is designed to display user scores and answer information once it is available.