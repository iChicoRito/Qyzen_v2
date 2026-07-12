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
use Database\Seeders\Data\SubjectQuestions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RealDummySeeder extends Seeder
{
    private const PASSWORD = 'password';

    private const STUDENTS_PER_SECTION = 40;

    // Every 4th student in a section (by seed order) retakes the assessment once more,
    // producing an older + a newer Score row so multi-attempt history/best-score logic has data.
    private const RETAKE_EVERY_NTH_STUDENT = 4;

    private int $extraAttemptsSeeded = 0;

    private int $studentCounter = 0;

    // Real faculty loads. Educator 1 is Mark Adrianne Salunga's actual NCST schedule
    // (S.Y. 2026-2027, 1st sem); educators 2-3 are realistic filler so cross-educator
    // ownership isolation stays demonstrable. section_name already encodes
    // program+year+semester+shift+block, so it's used verbatim. All loads are 1st sem.
    private const LOAD = [
        [
            'given' => 'Mark Adrianne', 'surname' => 'Salunga', 'email' => 'mark.salunga@qyzen.edu.ph',
            'sections' => [
                ['name' => 'BSIT11A3', 'subjects' => [['IT102', 'Computer Programming 1']]],
                ['name' => 'BSIT11E1', 'subjects' => [['IT102', 'Computer Programming 1']]],
                ['name' => 'BSIT21A1', 'subjects' => [['IT202', 'Interactive Media Design']]],
                ['name' => 'BSIT21A2', 'subjects' => [['IT202', 'Interactive Media Design'], ['IT206', 'Web Systems and Technologies 2']]],
                ['name' => 'BSIT21M3', 'subjects' => [['IT202', 'Interactive Media Design']]],
                ['name' => 'BSIT21M4', 'subjects' => [['IT202', 'Interactive Media Design']]],
                ['name' => 'BSCS21A1', 'subjects' => [['CS202', 'Graphics and Visual Computing']]],
                ['name' => 'BSIT31M4', 'subjects' => [['IT301', 'Human-Computer Interaction 2']]],
                ['name' => 'BSIT41M2', 'subjects' => [['IT402', 'Technopreneurship']]],
            ],
        ],
        [
            'given' => 'Katrina', 'surname' => 'Adlawan', 'email' => 'katrina.adlawan@qyzen.edu.ph',
            'sections' => [
                ['name' => 'BSIT12M1', 'subjects' => [['IT103', 'Discrete Mathematics']]],
                ['name' => 'BSIT22A3', 'subjects' => [['IT204', 'Data Structures and Algorithms']]],
                ['name' => 'BSIT32M2', 'subjects' => [['IT305', 'Information Assurance and Security']]],
                ['name' => 'BSCS31A2', 'subjects' => [['CS301', 'Automata Theory and Formal Languages']]],
            ],
        ],
        [
            'given' => 'Enrico', 'surname' => 'Regalado', 'email' => 'enrico.regalado@qyzen.edu.ph',
            'sections' => [
                ['name' => 'BSIT12M2', 'subjects' => [['IT101', 'Computer Programming 2']]],
                ['name' => 'BSIT31A1', 'subjects' => [['IT302', 'Systems Integration and Architecture']]],
                ['name' => 'BSIT41E1', 'subjects' => [['IT401', 'Capstone Project 2']]],
                ['name' => 'BSCS41M1', 'subjects' => [['CS401', 'Thesis Writing 2']]],
            ],
        ],
    ];

    // Deterministic Filipino name pools — sizes are coprime-ish so given+surname both
    // vary as the running student counter advances (no faker; seeding stays reproducible).
    private const GIVEN_NAMES = [
        'Juan', 'Maria', 'Jose', 'Andrea', 'Gabriel', 'Sofia', 'Angelo', 'Bianca', 'Miguel', 'Camille',
        'Rafael', 'Isabela', 'Paulo', 'Nicole', 'Diego', 'Patricia', 'Emmanuel', 'Trisha', 'Lorenzo', 'Kyla',
        'Sebastian', 'Danica', 'Vincent', 'Althea', 'Joaquin', 'Erika', 'Nathaniel', 'Jasmine', 'Francis', 'Reyna',
        'Christian', 'Lianne', 'Marco', 'Shaira', 'Dominic', 'Yohan', 'Aaron', 'Mikaela', 'Ivan', 'Precious',
        'Kenneth', 'Charmaine',
    ];

    private const SURNAMES = [
        'Dela Cruz', 'Santos', 'Reyes', 'Bautista', 'Ocampo', 'Villanueva', 'Mendoza', 'Garcia', 'Ramos', 'Aquino',
        'Torres', 'Flores', 'Castillo', 'Rivera', 'Gonzales', 'Domingo', 'Manalo', 'Salazar', 'Navarro', 'Padilla',
        'Aguilar', 'Fernandez', 'Cabrera', 'Espiritu', 'Pascual', 'Del Rosario', 'Lopez', 'Marquez', 'Trinidad', 'Velasco',
        'Cortez', 'Bernardo', 'Yalung', 'Guevara', 'Sarmiento', 'Alonzo', 'Rosario', 'Concepcion', 'Macaraeg', 'Lacsamana',
    ];

    // Each subject gets a 30-question bank; these four assessments draw from it. Quiz 1/2/3 take
    // distinct thirds (10 each); the Long Quiz is cumulative (all 30). offset/size index the bank.
    private const ASSESSMENTS = [
        ['code' => 'QUIZ1', 'offset' => 0, 'size' => 10, 'time_limit' => '20'],
        ['code' => 'QUIZ2', 'offset' => 10, 'size' => 10, 'time_limit' => '20'],
        ['code' => 'QUIZ3', 'offset' => 20, 'size' => 10, 'time_limit' => '20'],
        ['code' => 'LONGQUIZ', 'offset' => 0, 'size' => 30, 'time_limit' => '60'],
    ];

    private const BANK_SIZE = 30;

    public function run(): void
    {
        [$adminRole, $educatorRole, $studentRole] = $this->roles();
        $this->grantEducatorPermissions($educatorRole);

        $this->admin($adminRole);

        $year = AcademicYear::firstOrCreate(['year' => '2026 - 2027'], ['is_active' => true]);
        $terms = $this->terms($year);
        $firstSem = $terms[1];

        foreach (self::LOAD as $educatorIndex => $educatorBlueprint) {
            $educator = $this->educator($educatorIndex + 1, $educatorBlueprint, $educatorRole);

            foreach ($educatorBlueprint['sections'] as $sectionBlueprint) {
                $section = Section::firstOrCreate(
                    [
                        'educator_id' => $educator->id,
                        'section_name' => $sectionBlueprint['name'],
                    ],
                    [
                        'academic_term_id' => $firstSem->id,
                        'is_active' => true,
                    ],
                );

                $section->terms()->syncWithoutDetaching([$firstSem->id]);

                $students = $this->sectionStudents($studentRole);

                foreach ($sectionBlueprint['subjects'] as [$code, $name]) {
                    $subject = Subject::firstOrCreate(
                        [
                            'educator_id' => $educator->id,
                            'sections_id' => $section->id,
                            'subject_code' => $code,
                        ],
                        [
                            'subject_name' => $name,
                            'is_active' => true,
                        ],
                    );

                    // One 30-question bank per subject; the four assessments draw slices of it.
                    $bank = $this->questionBank($subject, $educator);

                    // Enroll the section's students into this subject once (not per assessment).
                    foreach ($students as $student) {
                        Enrolled::firstOrCreate(
                            [
                                'educator_id' => $educator->id,
                                'student_id' => $student->id,
                                'subject_id' => $subject->id,
                            ],
                            ['is_active' => true],
                        );
                    }

                    foreach (self::ASSESSMENTS as $assessmentIndex => $assessmentDef) {
                        $poolQuizzes = array_slice($bank, $assessmentDef['offset'], $assessmentDef['size']);
                        $poolIds = collect($poolQuizzes)->pluck('id')->all();
                        $schedule = $this->assessmentSchedule($educatorIndex, $section->id, $assessmentIndex);

                        $assessment = Assessment::firstOrCreate(
                            [
                                'assessment_code' => $assessmentDef['code'],
                                'subject_id' => $subject->id,
                                'section_id' => $section->id,
                                'term' => $firstSem->id,
                            ],
                            [
                                'educator_id' => $educator->id,
                                'time_limit' => $assessmentDef['time_limit'],
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

                        $assessment->eligibleQuizzes()->sync($poolIds);
                        $assessment->update(['pool_size' => $assessmentDef['size']]);

                        foreach ($students as $studentPosition => $student) {
                            $latestAttempt = $this->scoreAttempt($assessment, $subject, $section, $student, $poolQuizzes, $studentPosition + 1);

                            if (($studentPosition + 1) % self::RETAKE_EVERY_NTH_STUDENT === 0) {
                                $this->seedRetake($assessment, $subject, $section, $student, $poolQuizzes, $latestAttempt);
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
        }

        $this->assertCounts();
        $sections = $this->totalSections();
        $students = $sections * self::STUDENTS_PER_SECTION;
        $assessments = $this->totalSubjectLoads() * count(self::ASSESSMENTS);
        $scores = $assessments * (self::STUDENTS_PER_SECTION + $this->retakesPerAssessment());
        $this->command?->info(sprintf(
            'RealDummySeeder: %d educators, %d sections, %d students, %d assessments, %d quizzes, %d scores (%d retakes).',
            count(self::LOAD), $sections, $students, $assessments,
            $this->totalSubjectLoads() * self::BANK_SIZE, $scores, $this->extraAttemptsSeeded,
        ));
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
        $this->user('admin', '2026-0000', 'Ada', 'Administrator', 'admin@qyzen.edu.ph', $adminRole);
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

    /** @param array{given: string, surname: string, email: string} $blueprint */
    private function educator(int $educatorNumber, array $blueprint, Role $educatorRole): User
    {
        return $this->user(
            'educator',
            sprintf('2026-%04d', $educatorNumber),
            $blueprint['given'],
            $blueprint['surname'],
            $blueprint['email'],
            $educatorRole,
        );
    }

    /** @return User[] */
    private function sectionStudents(Role $studentRole): array
    {
        $students = [];

        for ($studentIndex = 1; $studentIndex <= self::STUDENTS_PER_SECTION; $studentIndex++) {
            $n = ++$this->studentCounter;
            $given = self::GIVEN_NAMES[$n % count(self::GIVEN_NAMES)];
            // Stride coprime with the pool size (7 vs 40) so consecutive students walk the whole
            // surname list — every student in a section gets a distinct surname, and given+surname
            // pairings don't lock-step (given cycles at 42, surname at 40).
            $surname = self::SURNAMES[($n * 7) % count(self::SURNAMES)];
            $emailLocal = strtolower(str_replace(' ', '', $given.'.'.$surname)).'.'.$n;

            $students[] = $this->user(
                'student',
                sprintf('2026-%05d', $n),
                $given,
                $surname,
                $emailLocal.'@student.qyzen.edu.ph',
                $studentRole,
            );
        }

        return $students;
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
    private function assessmentSchedule(int $educatorIndex, int $sectionId, int $subjectIndex): array
    {
        $offsetDays = ($educatorIndex * 3) + ($sectionId % 5) + $subjectIndex;
        $startDate = now()->startOfDay()->subDays(2)->addDays($offsetDays);
        $oneDayWindow = (($sectionId + $subjectIndex) % 2) === 0;
        $endDate = $oneDayWindow ? $startDate->copy() : $startDate->copy()->addDays(2);
        $timeSlots = [
            ['08:00', '17:00'],
            ['09:00', '18:00'],
            ['13:00', '20:00'],
        ];
        $slot = $timeSlots[($educatorIndex + $sectionId + $subjectIndex) % count($timeSlots)];

        return [
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'start_time' => $slot[0],
            'end_time' => $slot[1],
        ];
    }

    /**
     * The subject's 30-question bank, keyed to its subject_code (realistic per-topic content).
     * Idempotent: reuses existing rows if the bank was already seeded.
     *
     * @return Quiz[]
     */
    private function questionBank(Subject $subject, User $educator): array
    {
        $existing = Quiz::where('subject_id', $subject->id)->orderBy('id')->get();
        if ($existing->count() >= self::BANK_SIZE) {
            return $existing->all();
        }

        $quizzes = [];
        foreach (SubjectQuestions::for($subject->subject_code) as [$question, $type, $choices, $correct]) {
            $quizzes[] = Quiz::create([
                'subject_id' => $subject->id,
                'educator_id' => $educator->id,
                'question' => $question,
                'quiz_type' => $type,
                'choices' => $choices,
                'correct_answer' => $correct,
            ]);
        }

        return $quizzes;
    }

    /** @param Quiz[] $quizzes */
    private function scoreAttempt(Assessment $assessment, Subject $subject, Section $section, User $student, array $quizzes, int $studentPosition): array
    {
        $total = count($quizzes);
        $correctCount = max(1, ($studentPosition - 1) % ($total + 1));
        $isPassed = ($correctCount / $total) >= 0.75;
        $startDate = $assessment->start_date->copy();
        $windowDays = max(1, $assessment->start_date->diffInDays($assessment->end_date) + 1);
        $dayOffset = ($studentPosition - 1) % $windowDays;
        $minuteOffset = (($studentPosition - 1) * 17) % 540;
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

    private function totalSections(): int
    {
        return array_sum(array_map(fn ($e) => count($e['sections']), self::LOAD));
    }

    private function totalSubjectLoads(): int
    {
        $count = 0;
        foreach (self::LOAD as $educator) {
            foreach ($educator['sections'] as $section) {
                $count += count($section['subjects']);
            }
        }

        return $count;
    }

    private function assertCounts(): void
    {
        $educatorEmails = array_column(self::LOAD, 'email');
        $educatorIds = User::where('user_type', 'educator')
            ->whereIn('email', $educatorEmails)
            ->pluck('id');

        if ($educatorIds->count() !== count(self::LOAD)) {
            throw new \RuntimeException('RealDummySeeder expected exactly '.count(self::LOAD).' seeded educators.');
        }

        $expectedStudents = $this->totalSections() * self::STUDENTS_PER_SECTION;
        $studentCount = User::where('user_type', 'student')
            ->where('email', 'like', '%@student.qyzen.edu.ph')
            ->count();

        if ($studentCount !== $expectedStudents) {
            throw new \RuntimeException("RealDummySeeder expected exactly {$expectedStudents} seeded students.");
        }

        foreach (self::LOAD as $educatorIndex => $educatorBlueprint) {
            $educatorId = User::where('email', $educatorBlueprint['email'])->value('id');
            $sectionCount = Section::where('educator_id', $educatorId)->count();

            if ($sectionCount !== count($educatorBlueprint['sections'])) {
                throw new \RuntimeException("Educator {$educatorId} does not have ".count($educatorBlueprint['sections']).' sections.');
            }

            foreach ($educatorBlueprint['sections'] as $sectionBlueprint) {
                $sectionId = Section::where('educator_id', $educatorId)
                    ->where('section_name', $sectionBlueprint['name'])
                    ->value('id');
                $expectedSubjects = count($sectionBlueprint['subjects']);

                $subjectIds = Subject::where('educator_id', $educatorId)
                    ->where('sections_id', $sectionId)
                    ->pluck('id');

                if ($subjectIds->count() !== $expectedSubjects) {
                    throw new \RuntimeException("Section {$sectionId} does not have {$expectedSubjects} subjects.");
                }

                // 40 unique students shared across the section's subjects.
                $studentsInSection = Enrolled::where('educator_id', $educatorId)
                    ->whereIn('subject_id', $subjectIds)
                    ->distinct('student_id')
                    ->count('student_id');

                if ($studentsInSection !== self::STUDENTS_PER_SECTION) {
                    throw new \RuntimeException("Section {$sectionId} does not have ".self::STUDENTS_PER_SECTION.' unique students.');
                }

                foreach ($subjectIds as $subjectId) {
                    $bankCount = Quiz::where('subject_id', $subjectId)->count();
                    if ($bankCount !== self::BANK_SIZE) {
                        throw new \RuntimeException("Subject {$subjectId} bank does not have ".self::BANK_SIZE.' questions.');
                    }

                    $expectedScoreCount = self::STUDENTS_PER_SECTION + $this->retakesPerAssessment();
                    foreach (self::ASSESSMENTS as $assessmentDef) {
                        $assessment = Assessment::where('educator_id', $educatorId)
                            ->where('subject_id', $subjectId)
                            ->where('section_id', $sectionId)
                            ->where('assessment_code', $assessmentDef['code'])
                            ->first();

                        if (! $assessment) {
                            throw new \RuntimeException("Subject {$subjectId} is missing the {$assessmentDef['code']} assessment.");
                        }

                        if ($assessment->eligibleQuizzes()->count() !== $assessmentDef['size']) {
                            throw new \RuntimeException("Assessment {$assessment->id} pool is not {$assessmentDef['size']} questions.");
                        }

                        if (Score::where('assessment_id', $assessment->id)->count() !== $expectedScoreCount) {
                            throw new \RuntimeException("Assessment {$assessment->id} does not have {$expectedScoreCount} score rows.");
                        }
                    }
                }
            }
        }

        $loads = $this->totalSubjectLoads();
        $expectedAssessments = $loads * count(self::ASSESSMENTS);
        $expectedQuizzes = $loads * self::BANK_SIZE;
        $assessmentCount = Assessment::whereIn('educator_id', $educatorIds)->count();
        $quizCount = Quiz::whereIn('educator_id', $educatorIds)->count();
        $scoreCount = Score::whereIn('educator_id', $educatorIds)->count();

        if ($assessmentCount !== $expectedAssessments) {
            throw new \RuntimeException("RealDummySeeder expected exactly {$expectedAssessments} assessments.");
        }

        if ($quizCount !== $expectedQuizzes) {
            throw new \RuntimeException("RealDummySeeder expected exactly {$expectedQuizzes} quizzes.");
        }

        $expectedTotalScores = $expectedAssessments * (self::STUDENTS_PER_SECTION + $this->retakesPerAssessment());
        if ($scoreCount !== $expectedTotalScores) {
            throw new \RuntimeException("RealDummySeeder expected exactly {$expectedTotalScores} scores.");
        }
    }

    private function retakesPerAssessment(): int
    {
        return intdiv(self::STUDENTS_PER_SECTION, self::RETAKE_EVERY_NTH_STUDENT);
    }
}
