<?php

namespace Database\Seeders;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Enrolled;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EducatorIsolationSeeder extends Seeder
{
    private const PASSWORD = 'password';

    private const PROGRAM_CODE = 'BSIT';

    private const EDUCATORS = 5;

    private const SECTIONS_PER_EDUCATOR = 5;

    private const SUBJECTS_PER_SECTION = 5;

    private const STUDENTS_PER_SECTION = 40;

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

    public function run(): void
    {
        [$adminRole, $educatorRole, $studentRole] = $this->roles();
        $this->grantEducatorPermissions($educatorRole);

        $this->admin($adminRole);

        $year = AcademicYear::firstOrCreate(['year' => '2026 - 2027'], ['is_active' => true]);
        $terms = $this->terms($year);

        DB::transaction(function () use ($educatorRole, $studentRole, $terms) {
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
                    }
                }
            }
        });

        $this->assertCounts();
        $this->command?->info('EducatorIsolationSeeder: 5 educators, 25 sections, 125 subjects, 1000 students, 5000 enrollments.');
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
        $ids = Permission::whereIn('resource', ['sections', 'subjects'])->pluck('id')->all();
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
        $user = User::withTrashed()
            ->where('email', $email)
            ->orWhere('user_id', $userId)
            ->first() ?? new User;
        $user->forceFill([
            'user_type' => $type,
            'user_id' => $userId,
            'email' => $email,
            'is_active' => true,
            'given_name' => $given,
            'surname' => $surname,
            'password' => self::PASSWORD,
            'email_verified_at' => now(),
        ])->save();
        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user;
    }

    private function assertCounts(): void
    {
        $educatorIds = User::where('user_type', 'educator')
            ->where('email', 'like', 'educator%.seed@qyzen.edu.ph')
            ->pluck('id');

        if ($educatorIds->count() !== self::EDUCATORS) {
            throw new \RuntimeException('EducatorIsolationSeeder expected exactly 5 seeded educators.');
        }

        $studentCount = User::where('user_type', 'student')
            ->where('email', 'like', 'student-e%-s%-%@qyzen.edu.ph')
            ->count();

        if ($studentCount !== self::EDUCATORS * self::SECTIONS_PER_EDUCATOR * self::STUDENTS_PER_SECTION) {
            throw new \RuntimeException('EducatorIsolationSeeder expected exactly 1000 seeded students.');
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
                    $subjectStudentCount = Enrolled::where('educator_id', $educatorId)
                        ->where('subject_id', $subjectId)
                        ->count();

                    if ($subjectStudentCount !== self::STUDENTS_PER_SECTION) {
                        throw new \RuntimeException("Subject {$subjectId} does not have 40 enrolled students.");
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
    }
}
