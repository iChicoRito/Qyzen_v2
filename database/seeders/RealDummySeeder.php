<?php

namespace Database\Seeders;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Enrolled;
use App\Models\Permission;
use App\Models\Quiz;
use App\Models\Role;
use App\Models\Score;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RealDummySeeder extends Seeder
{
    private const PASSWORD = 'password';

    private const PROGRAM_CODE = 'BSIT';

    private const EDUCATORS = 5;

    private const SECTIONS_PER_EDUCATOR = 5;

    private const SUBJECTS_PER_SECTION = 5;

    private const STUDENTS_PER_SECTION = 40;

    private const QUIZZES_PER_ASSESSMENT = 4;

    // Every 4th student in a section (by seed order) retakes the assessment once more,
    // producing an older + a newer Score row so multi-attempt history/best-score logic has data.
    private const RETAKE_EVERY_NTH_STUDENT = 4;

    private int $extraAttemptsSeeded = 0;

    private const SECTION_BLUEPRINTS = [
        ['year' => 1, 'semester' => 1, 'time' => 'M', 'number' => 1],
        ['year' => 2, 'semester' => 1, 'time' => 'A', 'number' => 2],
        ['year' => 3, 'semester' => 2, 'time' => 'E', 'number' => 3],
        ['year' => 4, 'semester' => 1, 'time' => 'M', 'number' => 4],
        ['year' => 1, 'semester' => 2, 'time' => 'A', 'number' => 5],
    ];

    private const SUBJECT_BLUEPRINTS = [
        ['code' => 'IT101', 'name' => 'Programming Fundamentals'],
        ['code' => 'IT102', 'name' => 'Web Development'],
        ['code' => 'IT103', 'name' => 'Database Systems'],
        ['code' => 'IT104', 'name' => 'Networking Basics'],
        ['code' => 'IT105', 'name' => 'Systems Analysis'],
    ];

    private const QUESTIONS = [
        ['What is 2 + 2?', 'multiple_choice', ['A' => '3', 'B' => '4', 'C' => '5', 'D' => '6'], 'B'],
        ['What is 5 x 3?', 'multiple_choice', ['A' => '15', 'B' => '8', 'C' => '53', 'D' => '12'], 'A'],
        ['What is 10 / 2?', 'multiple_choice', ['A' => '2', 'B' => '20', 'C' => '5', 'D' => '8'], 'C'],
        ['Name the result of 7 - 4.', 'identification', null, '3'],
    ];

    public function run(): void
    {
        [$adminRole, $educatorRole, $studentRole] = $this->roles();
        $this->grantEducatorPermissions($educatorRole);

        $this->admin($adminRole);

        $year = AcademicYear::firstOrCreate(['year' => '2026 - 2027'], ['is_active' => true]);
        $terms = $this->terms($year);

        for ($educatorIndex = 1; $educatorIndex <= self::EDUCATORS; $educatorIndex++) {
            $educator = $this->educator($educatorIndex, $educatorRole);

            foreach (self::SECTION_BLUEPRINTS as $sectionIndex => $blueprint) {
                $section = Section::firstOrCreate(
                    [
                        'educator_id' => $educator->id,
                        'section_name' => $this->sectionName($blueprint),
                    ],
                    [
                        'academic_term_id' => $terms[$blueprint['semester']]->id,
                        'is_active' => true,
                    ],
                );

                $section->terms()->syncWithoutDetaching([$terms[$blueprint['semester']]->id]);

                $students = $this->sectionStudents($educatorIndex, $sectionIndex + 1, $studentRole);

                foreach (self::SUBJECT_BLUEPRINTS as $subjectBlueprint) {
                    $subjectIndex = array_search($subjectBlueprint, self::SUBJECT_BLUEPRINTS, true);
                    $subject = Subject::firstOrCreate(
                        [
                            'educator_id' => $educator->id,
                            'sections_id' => $section->id,
                            'subject_code' => $subjectBlueprint['code'],
                        ],
                        [
                            'subject_name' => $subjectBlueprint['name'],
                            'is_active' => true,
                        ],
                    );

                    $schedule = $this->assessmentSchedule($educatorIndex, $sectionIndex, $subjectIndex);

                    $assessment = Assessment::firstOrCreate(
                        [
                            'assessment_code' => 'QUIZ1',
                            'subject_id' => $subject->id,
                            'section_id' => $section->id,
                            'term' => $terms[$blueprint['semester']]->id,
                        ],
                        [
                            'educator_id' => $educator->id,
                            'time_limit' => '30',
                            'cheating_attempts' => 3,
                            'is_shuffle' => true,
                            'is_active' => true,
                            'allow_review' => true,
                            'allow_retake' => true,
                            'retake_count' => 1,
                            'allow_hint' => false,
                            'hint_count' => 0,
                            'start_date' => $schedule['start_date'],
                            'end_date' => $schedule['end_date'],
                            'start_time' => $schedule['start_time'],
                            'end_time' => $schedule['end_time'],
                        ],
                    );
                    $assessment->forceFill([
                        'start_date' => $schedule['start_date'],
                        'end_date' => $schedule['end_date'],
                        'start_time' => $schedule['start_time'],
                        'end_time' => $schedule['end_time'],
                    ])->save();

                    $quizzes = $this->quizzes($assessment, $subject, $educator);

                    foreach ($students as $studentPosition => $student) {
                        Enrolled::firstOrCreate(
                            [
                                'educator_id' => $educator->id,
                                'student_id' => $student->id,
                                'subject_id' => $subject->id,
                            ],
                            ['is_active' => true],
                        );

                        $latestAttempt = $this->scoreAttempt($assessment, $subject, $section, $student, $quizzes);

                        if (($studentPosition + 1) % self::RETAKE_EVERY_NTH_STUDENT === 0) {
                            $this->seedRetake($assessment, $subject, $section, $student, $quizzes, $latestAttempt);
                        }

                        Score::updateOrCreate(
                            [
                                'student_id' => $student->id,
                                'assessment_id' => $assessment->id,
                                'submitted_at' => $latestAttempt['submitted_at'],
                            ],
                            $latestAttempt,
                        );
                    }
                }
            }
        }

        $this->assertCounts();
        $totalScores = self::EDUCATORS * self::SECTIONS_PER_EDUCATOR * self::SUBJECTS_PER_SECTION * (self::STUDENTS_PER_SECTION + $this->retakesPerAssessment());
        $this->command?->info("RealDummySeeder: 5 educators, 25 sections, 125 subjects, 1000 students, {$totalScores} scores ({$this->extraAttemptsSeeded} retake attempts).");
    }

    /** @return array{0: Role, 1: Role, 2: Role} */
    private function roles(): array
    {
        return [
            Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator', 'is_system' => true, 'is_active' => true]),
            Role::firstOrCreate(['name' => 'educator'], ['description' => 'Educator', 'is_system' => true, 'is_active' => true]),
            Role::firstOrCreate(['name' => 'student'], ['description' => 'Student', 'is_system' => true, 'is_active' => true]),
        ];
    }

    private function grantEducatorPermissions(Role $educatorRole): void
    {
        $this->call(PermissionSeeder::class);
        $ids = Permission::whereIn('resource', ['sections', 'subjects', 'assessments', 'quizzes', 'scores'])->pluck('id')->all();
        $educatorRole->permissions()->syncWithoutDetaching($ids);
    }

    private function admin(Role $adminRole): void
    {
        $this->user('admin', '2026-ADMIN-SEED', 'Ada', 'Admin', 'admin@qyzen.edu.ph', $adminRole);
    }

    /** @return array<int, AcademicTerm> */
    private function terms(AcademicYear $year): array
    {
        return [
            1 => AcademicTerm::firstOrCreate(
                ['term_name' => 'Prelim', 'semester' => '1st Semester', 'academic_year_id' => $year->id],
                ['is_active' => true],
            ),
            2 => AcademicTerm::firstOrCreate(
                ['term_name' => 'Prelim', 'semester' => '2nd Semester', 'academic_year_id' => $year->id],
                ['is_active' => true],
            ),
        ];
    }

    private function educator(int $educatorIndex, Role $educatorRole): User
    {
        return $this->user(
            'educator',
            sprintf('2026-EDU-%03d', $educatorIndex),
            'Educator'.$educatorIndex,
            'Seed',
            "educator{$educatorIndex}.seed@qyzen.edu.ph",
            $educatorRole,
        );
    }

    /** @return User[] */
    private function sectionStudents(int $educatorIndex, int $sectionIndex, Role $studentRole): array
    {
        $students = [];

        for ($studentIndex = 1; $studentIndex <= self::STUDENTS_PER_SECTION; $studentIndex++) {
            $studentNumber = (($educatorIndex - 1) * self::SECTIONS_PER_EDUCATOR * self::STUDENTS_PER_SECTION)
                + (($sectionIndex - 1) * self::STUDENTS_PER_SECTION)
                + $studentIndex;

            $students[] = $this->user(
                'student',
                sprintf('2026-STU-%04d', $studentNumber),
                sprintf('Stu%d%d', $educatorIndex, $sectionIndex),
                sprintf('Learner%02d', $studentIndex),
                sprintf('student-e%d-s%d-%02d@qyzen.edu.ph', $educatorIndex, $sectionIndex, $studentIndex),
                $studentRole,
            );
        }

        return $students;
    }

    private function sectionName(array $blueprint): string
    {
        return self::PROGRAM_CODE
            .$blueprint['year']
            .$blueprint['semester']
            .$blueprint['time']
            .$blueprint['number'];
    }

    private function user(string $type, string $userId, string $given, string $surname, string $email, Role $role): User
    {
        DB::table('tbl_users')->updateOrInsert(
            ['user_id' => $userId],
            [
                'user_type' => $type,
                'email' => $email,
                'is_active' => true,
                'given_name' => $given,
                'surname' => $surname,
                'password' => Hash::make(self::PASSWORD),
                'email_verified_at' => now(),
                'deleted_at' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        $user = User::withTrashed()->where('user_id', $userId)->firstOrFail();
        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user;
    }

    /** @return array{start_date: string, end_date: string, start_time: string, end_time: string} */
    private function assessmentSchedule(int $educatorIndex, int $sectionIndex, int $subjectIndex): array
    {
        $offsetDays = (($educatorIndex - 1) * self::SECTIONS_PER_EDUCATOR) + $sectionIndex + $subjectIndex;
        $startDate = now()->startOfDay()->subDays(2)->addDays($offsetDays);
        $oneDayWindow = (($sectionIndex + $subjectIndex) % 2) === 0;
        $endDate = $oneDayWindow ? $startDate->copy() : $startDate->copy()->addDays(2);
        $timeSlots = [
            ['08:00', '17:00'],
            ['09:00', '18:00'],
            ['13:00', '20:00'],
        ];
        $slot = $timeSlots[($educatorIndex + $sectionIndex + $subjectIndex) % count($timeSlots)];

        return [
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'start_time' => $slot[0],
            'end_time' => $slot[1],
        ];
    }

    /** @return Quiz[] */
    private function quizzes(Assessment $assessment, Subject $subject, User $educator): array
    {
        $existing = $assessment->eligibleQuizzes()->orderBy('tbl_quizzes.id')->get();
        if ($existing->isNotEmpty()) {
            return $existing->all();
        }

        $quizzes = [];
        foreach (self::QUESTIONS as [$question, $type, $choices, $correct]) {
            $quizzes[] = Quiz::create([
                'subject_id' => $subject->id,
                'educator_id' => $educator->id,
                'question' => $question,
                'quiz_type' => $type,
                'choices' => $choices,
                'correct_answer' => $correct,
            ]);
        }

        $quizIds = collect($quizzes)->pluck('id')->all();
        $assessment->eligibleQuizzes()->sync($quizIds);
        $assessment->update(['pool_size' => count($quizIds)]);

        return $quizzes;
    }

    /** @param Quiz[] $quizzes */
    private function scoreAttempt(Assessment $assessment, Subject $subject, Section $section, User $student, array $quizzes): array
    {
        $total = count($quizzes);
        $studentNumber = (int) substr((string) $student->user_id, -2);
        $studentSequence = max(1, (int) substr((string) $student->user_id, -4));
        $correctCount = max(1, ($studentNumber - 1) % ($total + 1));
        $isPassed = ($correctCount / $total) >= 0.75;
        $startDate = $assessment->start_date->copy();
        $windowDays = max(1, $assessment->start_date->diffInDays($assessment->end_date) + 1);
        $dayOffset = ($studentSequence - 1) % $windowDays;
        $minuteOffset = (($studentSequence - 1) * 17) % 540;
        $submittedAt = $startDate
            ->copy()
            ->addDays($dayOffset)
            ->setTime(8, 0)
            ->addMinutes($minuteOffset);

        $answers = [];
        foreach ($quizzes as $index => $quiz) {
            $answers[$quiz->id] = $index < $correctCount ? $quiz->correct_answer : 'wrong';
        }

        return [
            'educator_id' => $assessment->educator_id,
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'score' => $correctCount,
            'total_questions' => $total,
            'student_answer' => $answers,
            'drawn_quiz_ids' => collect($quizzes)->pluck('id')->all(),
            'warning_attempts' => 0,
            'status' => $isPassed ? 'passed' : 'failed',
            'is_passed' => $isPassed,
            'taken_at' => $submittedAt->copy()->subMinutes(15),
            'submitted_at' => $submittedAt,
        ];
    }

    /** @param Quiz[] $quizzes */
    private function seedRetake(Assessment $assessment, Subject $subject, Section $section, User $student, array $quizzes, array $latestAttempt): void
    {
        $total = count($quizzes);
        $retakeCorrectCount = max(1, $latestAttempt['score'] - 1);
        $retakeSubmittedAt = $latestAttempt['submitted_at']->copy()->subDay()->subHours(2);
        $retakeAnswers = [];
        foreach ($quizzes as $index => $quiz) {
            $retakeAnswers[$quiz->id] = $index < $retakeCorrectCount ? $quiz->correct_answer : 'wrong';
        }
        $isPassed = ($retakeCorrectCount / $total) >= 0.75;

        Score::updateOrCreate(
            [
                'student_id' => $student->id,
                'assessment_id' => $assessment->id,
                'submitted_at' => $retakeSubmittedAt,
            ],
            [
                'educator_id' => $assessment->educator_id,
                'subject_id' => $subject->id,
                'section_id' => $section->id,
                'score' => $retakeCorrectCount,
                'total_questions' => $total,
                'student_answer' => $retakeAnswers,
                'drawn_quiz_ids' => collect($quizzes)->pluck('id')->all(),
                'warning_attempts' => 0,
                'status' => $isPassed ? 'passed' : 'failed',
                'is_passed' => $isPassed,
                'taken_at' => $retakeSubmittedAt->copy()->subMinutes(15),
                'submitted_at' => $retakeSubmittedAt,
            ],
        );

        $this->extraAttemptsSeeded++;
    }

    private function assertCounts(): void
    {
        $educatorIds = User::where('user_type', 'educator')
            ->where('email', 'like', 'educator%.seed@qyzen.edu.ph')
            ->pluck('id');

        if ($educatorIds->count() !== self::EDUCATORS) {
            throw new \RuntimeException('RealDummySeeder expected exactly 5 seeded educators.');
        }

        $studentCount = User::where('user_type', 'student')
            ->where('email', 'like', 'student-e%-s%-%@qyzen.edu.ph')
            ->count();

        if ($studentCount !== self::EDUCATORS * self::SECTIONS_PER_EDUCATOR * self::STUDENTS_PER_SECTION) {
            throw new \RuntimeException('RealDummySeeder expected exactly 1000 seeded students.');
        }

        foreach ($educatorIds as $educatorId) {
            $sectionIds = Section::where('educator_id', $educatorId)->pluck('id');

            if ($sectionIds->count() !== self::SECTIONS_PER_EDUCATOR) {
                throw new \RuntimeException("Educator {$educatorId} does not have 5 sections.");
            }

            foreach ($sectionIds as $sectionId) {
                $subjectIds = Subject::where('educator_id', $educatorId)
                    ->where('sections_id', $sectionId)
                    ->pluck('id');

                if ($subjectIds->count() !== self::SUBJECTS_PER_SECTION) {
                    throw new \RuntimeException("Section {$sectionId} does not have 5 subjects.");
                }

                $studentsInSection = Enrolled::where('educator_id', $educatorId)
                    ->whereIn('subject_id', $subjectIds)
                    ->distinct('student_id')
                    ->count('student_id');

                if ($studentsInSection !== self::STUDENTS_PER_SECTION) {
                    throw new \RuntimeException("Section {$sectionId} does not have 40 unique students shared across its subjects.");
                }

                foreach ($subjectIds as $subjectId) {
                    $assessmentCount = Assessment::where('educator_id', $educatorId)
                        ->where('subject_id', $subjectId)
                        ->where('section_id', $sectionId)
                        ->count();

                    if ($assessmentCount !== 1) {
                        throw new \RuntimeException("Subject {$subjectId} does not have exactly 1 assessment.");
                    }

                    $assessmentId = Assessment::where('educator_id', $educatorId)
                        ->where('subject_id', $subjectId)
                        ->where('section_id', $sectionId)
                        ->value('id');

                    $quizCount = Assessment::find($assessmentId)->eligibleQuizzes()->count();

                    if ($quizCount !== self::QUIZZES_PER_ASSESSMENT) {
                        throw new \RuntimeException("Assessment {$assessmentId} does not have 4 quizzes.");
                    }

                    $subjectStudentCount = Enrolled::where('educator_id', $educatorId)
                        ->where('subject_id', $subjectId)
                        ->count();

                    if ($subjectStudentCount !== self::STUDENTS_PER_SECTION) {
                        throw new \RuntimeException("Subject {$subjectId} does not have 40 enrolled students.");
                    }

                    $scoreCount = Score::where('educator_id', $educatorId)
                        ->where('subject_id', $subjectId)
                        ->where('section_id', $sectionId)
                        ->where('assessment_id', $assessmentId)
                        ->count();

                    $expectedScoreCount = self::STUDENTS_PER_SECTION + $this->retakesPerAssessment();

                    if ($scoreCount !== $expectedScoreCount) {
                        throw new \RuntimeException("Assessment {$assessmentId} does not have {$expectedScoreCount} score rows (attempts + retakes).");
                    }
                }
            }

            $educatorStudentCount = Enrolled::where('educator_id', $educatorId)
                ->join('tbl_users', 'tbl_users.id', '=', 'tbl_enrolled.student_id')
                ->where('tbl_users.email', 'like', 'student-e%-s%-%@qyzen.edu.ph')
                ->distinct('tbl_enrolled.student_id')
                ->count('tbl_enrolled.student_id');

            if ($educatorStudentCount !== self::SECTIONS_PER_EDUCATOR * self::STUDENTS_PER_SECTION) {
                throw new \RuntimeException("Educator {$educatorId} does not have 200 exclusive students.");
            }
        }

        $assessmentCount = Assessment::whereIn('educator_id', $educatorIds)->count();
        $quizCount = Quiz::whereIn('educator_id', $educatorIds)->count();
        $scoreCount = Score::whereIn('educator_id', $educatorIds)->count();

        if ($assessmentCount !== self::EDUCATORS * self::SECTIONS_PER_EDUCATOR * self::SUBJECTS_PER_SECTION) {
            throw new \RuntimeException('RealDummySeeder expected exactly 125 assessments.');
        }

        if ($quizCount !== $assessmentCount * self::QUIZZES_PER_ASSESSMENT) {
            throw new \RuntimeException('RealDummySeeder expected exactly 500 quizzes.');
        }

        $expectedTotalScores = $assessmentCount * (self::STUDENTS_PER_SECTION + $this->retakesPerAssessment());

        if ($scoreCount !== $expectedTotalScores) {
            throw new \RuntimeException("RealDummySeeder expected exactly {$expectedTotalScores} scores.");
        }
    }

    private function retakesPerAssessment(): int
    {
        return intdiv(self::STUDENTS_PER_SECTION, self::RETAKE_EVERY_NTH_STUDENT);
    }
}
