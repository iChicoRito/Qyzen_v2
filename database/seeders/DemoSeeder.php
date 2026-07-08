<?php

namespace Database\Seeders;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Enrolled;
use App\Models\GroupChat;
use App\Models\GroupChatMessage;
use App\Models\LearningMaterial;
use App\Models\Permission;
use App\Models\Quiz;
use App\Models\Role;
use App\Models\Score;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// Demo dataset so every role's UI is populated for local click-through. Idempotent-ish:
// run after migrate:fresh. All users share the password "password" and are email-verified.
// NOTE: this is fake data for development; real Supabase import is Stage E (separate).
class DemoSeeder extends Seeder
{
    private const PASSWORD = 'password';

    public function run(): void
    {
        [$adminRole, $educatorRole, $studentRole] = $this->roles();
        $this->permissions($educatorRole);

        $admin = $this->user('admin', '2026-0001', 'Ada', 'Admin', 'admin@qyzen.test', [$adminRole]);
        $eduA = $this->user('educator', '2026-0002', 'Ed', 'Ucator', 'educator@qyzen.test', [$educatorRole]);
        $eduB = $this->user('educator', '2026-0003', 'Bea', 'Teacher', 'educator2@qyzen.test', [$educatorRole]);

        $students = collect();
        for ($i = 1; $i <= 8; $i++) {
            $students->push($this->user('student',
                sprintf('2026-1%04d', $i),
                'Student'.$i, 'Surname'.$i,
                "student{$i}@qyzen.test", [$studentRole]));
        }

        // Academic calendar.
        $year = AcademicYear::firstOrCreate(['year' => '2026 - 2027'], ['is_active' => true]);
        $prelim = AcademicTerm::firstOrCreate(['term_name' => 'Prelim', 'semester' => '1st Semester', 'academic_year_id' => $year->id], ['is_active' => true]);
        $midterm = AcademicTerm::firstOrCreate(['term_name' => 'Midterm', 'semester' => '1st Semester', 'academic_year_id' => $year->id], ['is_active' => true]);

        // eduA's classroom (the main demo educator).
        $this->classroomFor($eduA, $prelim, $midterm, $students->take(6)->all());
        // a second educator with their own data, to prove the ownership gate visually.
        $this->classroomFor($eduB, $prelim, $midterm, $students->slice(4)->all());

        $this->command?->info('Demo data seeded. Login: admin@qyzen.test / educator@qyzen.test / student1@qyzen.test — password: "password".');
    }

    /** @return Role[] */
    private function roles(): array
    {
        return [
            Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator', 'is_system' => true, 'is_active' => true]),
            Role::firstOrCreate(['name' => 'educator'], ['description' => 'Educator', 'is_system' => true, 'is_active' => true]),
            Role::firstOrCreate(['name' => 'student'], ['description' => 'Student', 'is_system' => true, 'is_active' => true]),
        ];
    }

    private function permissions(Role $educatorRole): void
    {
        foreach (['sections', 'subjects'] as $resource) {
            foreach (['view', 'create', 'update', 'delete'] as $action) {
                $string = "{$resource}:{$action}";
                $perm = Permission::firstOrCreate(['permission_string' => $string], [
                    'name' => $string, 'resource' => $resource, 'action' => $action,
                    'description' => $string, 'module' => $resource, 'is_active' => true,
                ]);
                $educatorRole->permissions()->syncWithoutDetaching([$perm->id]);
            }
        }
    }

    /** @param Role[] $roles */
    private function user(string $type, string $userId, string $given, string $surname, string $email, array $roles): User
    {
        $user = User::withTrashed()->where('email', $email)->first() ?? new User;
        $user->forceFill([
            'user_type' => $type, 'user_id' => $userId, 'email' => $email, 'is_active' => true,
            'given_name' => $given, 'surname' => $surname,
            'password' => self::PASSWORD, // 'hashed' cast hashes it
            'email_verified_at' => now(),
        ])->save();
        $user->roles()->syncWithoutDetaching(collect($roles)->pluck('id')->all());

        return $user;
    }

    /** @param User[] $students */
    private function classroomFor(User $educator, AcademicTerm $prelim, AcademicTerm $midterm, array $students): void
    {
        DB::transaction(function () use ($educator, $prelim, $midterm, $students) {
            $section = Section::create([
                'educator_id' => $educator->id, 'academic_term_id' => $prelim->id,
                'section_name' => 'Section '.$educator->given_name, 'is_active' => true,
            ]);
            $section->terms()->sync([$prelim->id, $midterm->id]);

            $subject = Subject::create([
                'educator_id' => $educator->id, 'sections_id' => $section->id,
                'subject_code' => 'MATH'.$educator->id, 'subject_name' => 'Mathematics', 'is_active' => true,
            ]);

            foreach ($students as $student) {
                Enrolled::firstOrCreate([
                    'educator_id' => $educator->id, 'student_id' => $student->id, 'subject_id' => $subject->id,
                ], ['is_active' => true]);
            }

            // An open, active assessment with 4 questions.
            $assessment = Assessment::create([
                'educator_id' => $educator->id, 'subject_id' => $subject->id, 'section_id' => $section->id,
                'assessment_code' => 'QUIZ1', 'time_limit' => '30', 'cheating_attempts' => 3,
                'is_shuffle' => true, 'is_active' => true, 'allow_review' => true,
                'allow_retake' => true, 'retake_count' => 2, 'allow_hint' => false, 'hint_count' => 0,
                'start_date' => now()->subDay()->toDateString(), 'end_date' => now()->addWeek()->toDateString(),
                'start_time' => '00:00', 'end_time' => '23:59', 'term' => $prelim->id,
            ]);

            $questions = [
                ['What is 2 + 2?', 'multiple_choice', ['A' => '3', 'B' => '4', 'C' => '5', 'D' => '6'], 'B'],
                ['What is 5 × 3?', 'multiple_choice', ['A' => '15', 'B' => '8', 'C' => '53', 'D' => '12'], 'A'],
                ['What is 10 ÷ 2?', 'multiple_choice', ['A' => '2', 'B' => '20', 'C' => '5', 'D' => '8'], 'C'],
                ['Name the result of 7 - 4.', 'identification', null, '3'],
            ];
            $quizzes = [];
            foreach ($questions as [$q, $type, $choices, $correct]) {
                $quizzes[] = Quiz::create([
                    'subject_id' => $subject->id, 'educator_id' => $educator->id,
                    'question' => $q, 'quiz_type' => $type, 'choices' => $choices, 'correct_answer' => $correct,
                ]);
            }

            // The whole bank is eligible; pool draws all 4 every time (matches the old fixed set).
            $quizIds = collect($quizzes)->pluck('id')->all();
            $assessment->eligibleQuizzes()->sync($quizIds);
            $assessment->update(['pool_size' => count($quizIds)]);

            // A couple of submitted scores so dashboards / history populate.
            foreach (array_slice($students, 0, 3) as $idx => $student) {
                $correctCount = 4 - $idx; // 4, 3, 2 correct → pass, pass, fail
                $answers = [];
                foreach ($quizzes as $qi => $quiz) {
                    $answers[$quiz->id] = $qi < $correctCount ? $quiz->correct_answer : 'wrong';
                }
                $isPassed = $correctCount / 4 >= 0.75;
                Score::create([
                    'student_id' => $student->id, 'educator_id' => $educator->id, 'assessment_id' => $assessment->id,
                    'subject_id' => $subject->id, 'section_id' => $section->id,
                    'score' => $correctCount, 'total_questions' => 4, 'student_answer' => $answers,
                    'drawn_quiz_ids' => $quizIds,
                    'warning_attempts' => 0, 'status' => $isPassed ? 'passed' : 'failed', 'is_passed' => $isPassed,
                    'taken_at' => now()->subHour(), 'submitted_at' => now()->subHour(),
                ]);
            }

            // A learning material row (metadata only; no real file on disk for demo).
            LearningMaterial::create([
                'educator_id' => $educator->id, 'subject_id' => $subject->id, 'section_id' => $section->id,
                'storage_bucket' => 'local', 'storage_path' => 'learning-materials/demo/syllabus.pdf',
                'file_name' => 'syllabus.pdf', 'file_extension' => 'pdf', 'mime_type' => 'application/pdf',
                'file_size' => 102400, 'is_active' => true,
            ]);

            // A group chat with a seed message.
            $chat = GroupChat::create(['educator_id' => $educator->id, 'subject_id' => $subject->id, 'section_id' => $section->id]);
            GroupChatMessage::create([
                'group_chat_id' => $chat->id, 'sender_user_id' => $educator->id,
                'content' => 'Welcome to '.$subject->subject_name.'! Post questions here.',
            ]);
        });
    }
}
