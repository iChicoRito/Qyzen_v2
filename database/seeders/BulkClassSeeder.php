<?php

namespace Database\Seeders;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Enrolled;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// Task 15: bulk BSIT dataset — 100 Filipino students, 5 sections, 5 subjects,
// 4 empty assessments per subject/section, 20 disjoint students enrolled each.
// Questions are left blank (no tbl_quizzes rows) for later upload. Run standalone:
//   php artisan db:seed --class=BulkClassSeeder
// Idempotent-ish via firstOrCreate; run after migrate:fresh for clean counts.
class BulkClassSeeder extends Seeder
{
    private const PASSWORD = 'password';

    /** section_name => [subject_code, subject_name] (fixed mapping from task 15). */
    private const SUBJECTS = [
        'BSIT11E1' => ['IT102', 'COMPUTER PROGRAMMING 1'],
        'BSIT12A1' => ['IT206', 'WEB SYSTEM TECHNOLOGIES 2'],
        'BSIT31M4' => ['IT309', 'HUMAN COMPUTER INTERACTION 2'],
        'BSIT41M2' => ['IT439', 'TECHNOPRENEURSHIP'],
        'BSIT21M4' => ['IT202', 'INTERACTIVE MEDIA DESIGN'],
    ];

    private const ASSESSMENTS = ['Quiz #1', 'Quiz #2', 'Quiz #3', 'Long Quiz'];

    private const GIVEN_NAMES = [
        'Juan', 'Jose', 'Maria', 'Ana', 'Antonio', 'Andres', 'Ramon', 'Rizal', 'Emilio', 'Gabriela',
        'Corazon', 'Ferdinand', 'Isabel', 'Rodrigo', 'Manuel', 'Teresita', 'Ricardo', 'Cristina', 'Eduardo', 'Lourdes',
        'Danilo', 'Rosario', 'Alfredo', 'Melchora', 'Benigno', 'Imelda', 'Fernando', 'Josefa', 'Reynaldo', 'Leonor',
        'Arturo', 'Remedios', 'Nestor', 'Soledad', 'Efren', 'Perla', 'Gregorio', 'Marilou', 'Salvador', 'Divina',
    ];

    private const SURNAMES = [
        'Santos', 'Reyes', 'Cruz', 'Bautista', 'Ocampo', 'Garcia', 'Mendoza', 'Torres', 'Tolentino', 'Flores',
        'Villanueva', 'Ramos', 'Aquino', 'Del Rosario', 'Castillo', 'Gonzales', 'Rivera', 'Aguilar', 'Fernandez', 'Domingo',
        'Salazar', 'Mercado', 'Navarro', 'Pascual', 'Dela Cruz', 'Bonifacio', 'Marasigan', 'Alcantara', 'Espinosa', 'Guerrero',
        'Lim', 'Tan', 'Sy', 'Chua', 'Lopez', 'Manalo', 'Panganiban', 'Soriano', 'Valdez', 'Yandoc',
    ];

    public function run(): void
    {
        [$adminRole, $educatorRole, $studentRole] = $this->roles();
        $this->grantEducatorPermissions($educatorRole);

        // Keep an admin login around (migrate:fresh wipes users; this restores one).
        $this->user('admin', '2026-00001-A', 'Ada', 'Admin', 'admin@qyzen.edu.ph', $adminRole);

        // '2026-00000' stays clear of the student range (00001..00100).
        $educator = $this->user('educator', '2026-00000', 'Prof', 'Reyes', 'prof@qyzen.edu.ph', $educatorRole);

        $year = AcademicYear::firstOrCreate(['year' => '2026 - 2027'], ['is_active' => true]);
        $term = AcademicTerm::firstOrCreate(
            ['term_name' => 'Prelim', 'semester' => '1st Semester', 'academic_year_id' => $year->id],
            ['is_active' => true],
        );

        $students = $this->students($studentRole);
        // 5 disjoint groups of 20; group index aligns with the SUBJECTS order.
        $groups = array_chunk($students, 20);

        DB::transaction(function () use ($educator, $term, $groups) {
            $i = 0;
            foreach (self::SUBJECTS as $sectionName => [$code, $name]) {
                $section = Section::firstOrCreate(
                    ['educator_id' => $educator->id, 'section_name' => $sectionName],
                    ['academic_term_id' => $term->id, 'is_active' => true],
                );
                $section->terms()->syncWithoutDetaching([$term->id]);

                $subject = Subject::firstOrCreate(
                    ['educator_id' => $educator->id, 'sections_id' => $section->id, 'subject_code' => $code],
                    ['subject_name' => $name, 'is_active' => true],
                );

                foreach (self::ASSESSMENTS as $assessmentCode) {
                    Assessment::firstOrCreate(
                        ['assessment_code' => $assessmentCode, 'subject_id' => $subject->id, 'section_id' => $section->id, 'term' => $term->id],
                        [
                            'educator_id' => $educator->id, 'time_limit' => '30', 'cheating_attempts' => 3,
                            'is_shuffle' => true, 'is_active' => true,
                            'start_date' => now()->subDay()->toDateString(), 'end_date' => now()->addWeek()->toDateString(),
                            'start_time' => '00:00', 'end_time' => '23:59',
                            // questions left blank — no tbl_quizzes rows created.
                        ],
                    );
                }

                foreach ($groups[$i] ?? [] as $student) {
                    Enrolled::firstOrCreate(
                        ['educator_id' => $educator->id, 'student_id' => $student->id, 'subject_id' => $subject->id],
                        ['is_active' => true],
                    );
                }
                $i++;
            }
        });

        $this->assertCounts($educator);
        $this->command?->info('BulkClassSeeder: 100 students, 5 sections/subjects, 20 assessments, 100 enrollments. Educator: prof@qyzen.edu.ph / "password".');
    }

    /** @return Role[] [admin, educator, student] */
    private function roles(): array
    {
        return [
            Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator', 'is_system' => true, 'is_active' => true]),
            Role::firstOrCreate(['name' => 'educator'], ['description' => 'Educator', 'is_system' => true, 'is_active' => true]),
            Role::firstOrCreate(['name' => 'student'], ['description' => 'Student', 'is_system' => true, 'is_active' => true]),
        ];
    }

    // Educators are gated by role permissions (admins bypass via hasRole). Seed the full
    // catalog, then grant the educator role every non-admin resource so the UI stops 403ing.
    private function grantEducatorPermissions(Role $educatorRole): void
    {
        $this->call(PermissionSeeder::class);
        $ids = Permission::whereIn('resource', [
            'sections', 'subjects', 'assessments', 'quizzes', 'materials', 'enrollment', 'scores', 'chats',
        ])->pluck('id')->all();
        $educatorRole->permissions()->syncWithoutDetaching($ids);
    }

    /** @return User[] 100 students, deterministic names, unique email + student number. */
    private function students(Role $studentRole): array
    {
        $usedEmails = [];
        $students = [];
        for ($i = 1; $i <= 100; $i++) {
            $given = self::GIVEN_NAMES[($i - 1) % count(self::GIVEN_NAMES)];
            $surname = self::SURNAMES[intdiv($i - 1, count(self::GIVEN_NAMES)) % count(self::SURNAMES)];

            $slug = fn (string $s) => strtolower(str_replace(' ', '', $s));
            $email = "{$slug($surname)}.{$slug($given)}@qyzen.edu.ph";
            if (isset($usedEmails[$email])) {
                $email = "{$slug($surname)}.{$slug($given)}{$i}@qyzen.edu.ph";
            }
            $usedEmails[$email] = true;

            $students[] = $this->user('student', sprintf('2026-%05d', $i), $given, $surname, $email, $studentRole);
        }

        return $students;
    }

    private function user(string $type, string $userId, string $given, string $surname, string $email, Role $role): User
    {
        $user = User::withTrashed()->where('email', $email)->first() ?? new User();
        $user->forceFill([
            'user_type' => $type, 'user_id' => $userId, 'email' => $email, 'is_active' => true,
            'given_name' => $given, 'surname' => $surname,
            'password' => self::PASSWORD, // 'hashed' cast hashes it
            'email_verified_at' => now(),
        ])->save();
        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user;
    }

    private function assertCounts(User $educator): void
    {
        $counts = [
            'students' => User::where('user_type', 'student')->count(),
            'sections' => Section::where('educator_id', $educator->id)->count(),
            'subjects' => Subject::where('educator_id', $educator->id)->count(),
            'assessments' => Assessment::where('educator_id', $educator->id)->count(),
            'enrolled' => Enrolled::where('educator_id', $educator->id)->count(),
        ];
        $expected = ['students' => 100, 'sections' => 5, 'subjects' => 5, 'assessments' => 20, 'enrolled' => 100];
        foreach ($expected as $key => $want) {
            if ($counts[$key] < $want) {
                throw new \RuntimeException("BulkClassSeeder count check failed: {$key} = {$counts[$key]}, expected >= {$want}.");
            }
        }
    }
}
