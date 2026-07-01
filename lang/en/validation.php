<?php

/*
| Friendly validation copy. One file drives the tone for every Form Request
| and Fortify auth check — no per-request messages() needed. Only the rules
| actually used in app/Http/Requests are overridden; the rest fall back to
| Laravel's defaults. Field labels live in 'attributes' below so each message
| names the real thing the user needs to look at.
|
| Tone: professional, plain, specific. Avoids "failed / error / required /
| validation / submit". :attribute is the friendly label from 'attributes'.
*/

return [

    // Missing one specific field (fires once per empty field → naturally
    // covers the "multiple fields" case as separate, specific toasts).
    'required' => "Please provide your :attribute.",
    'required_if' => "Please provide your :attribute.",

    // Incorrect format
    'email' => "Please enter a valid :attribute.",
    'regex' => "Please check the format of your :attribute.",
    'date' => "Please choose a valid :attribute.",
    'integer' => "Your :attribute should be a whole number.",
    'numeric' => "Your :attribute should be a number.",
    'boolean' => "Please choose an option for your :attribute.",
    'in' => "Please choose a valid :attribute from the list.",

    // Uploads
    'image' => "Your :attribute should be an image (JPG or PNG).",
    'mimes' => "Your :attribute should be a :values file.",
    'max' => [
        'string' => "Your :attribute should be :max characters or fewer.",
        'file' => "Your :attribute should be under :max KB.",
        'array' => "Please choose no more than :max for your :attribute.",
        'numeric' => "Your :attribute should be :max or lower.",
    ],
    'min' => [
        // multi-select "pick at least one" case
        'array' => "Please choose at least :min for your :attribute.",
        'string' => "Your :attribute should be at least :min characters.",
        'numeric' => "Your :attribute should be at least :min.",
    ],
    'between' => [
        'numeric' => "Your :attribute should be between :min and :max.",
        'string' => "Your :attribute should be between :min and :max characters.",
    ],

    // Uniqueness / references
    'unique' => "That :attribute is already in use. Please choose another.",
    'exists' => "Please choose a valid :attribute from the list.",
    'confirmed' => "Your :attribute and its confirmation do not match.",
    'same' => "Your :attribute should match :other.",
    'array' => "Please choose your :attribute from the available options.",
    'string' => "Your :attribute should be text.",

    /*
    | Friendly, specific field labels. Keep these human — they slot straight
    | into every message above as :attribute.
    */
    'attributes' => [
        'user_type' => 'account type',
        'user_id' => 'ID number',
        'given_name' => 'first name',
        'surname' => 'last name',
        'email' => 'email address',
        'is_active' => 'status',
        'is_system' => 'system-role setting',
        'is_shuffle' => 'shuffle setting',
        'role_names' => 'role',
        'permission_ids' => 'permissions',
        'permissions' => 'permissions',
        'name' => 'name',
        'description' => 'description',
        'module' => 'module',
        'resource' => 'resource',
        'action' => 'action',

        'academic_year_id' => 'academic year',
        'academic_term_ids' => 'academic terms',
        'year' => 'year',
        'term' => 'term',
        'term_name' => 'term name',
        'semester' => 'semester',

        'section_id' => 'section',
        'section_ids' => 'sections',
        'section_name' => 'section name',
        'subject_id' => 'subject',
        'subject_ids' => 'subjects',
        'subject_code' => 'subject code',
        'subject_name' => 'subject name',
        'student_id' => 'student',
        'student_ids' => 'students',

        'assessment_id' => 'assessment',
        'assessment_code' => 'assessment code',
        'question' => 'question',
        'quiz_type' => 'question type',
        'choices' => 'answer choices',
        'correct_answer' => 'correct answer',
        'time_limit' => 'time limit',
        'start_date' => 'start date',
        'end_date' => 'end date',
        'start_time' => 'start time',
        'end_time' => 'end time',
        'allow_hint' => 'hint option',
        'hint_count' => 'number of hints',
        'allow_retake' => 'retake option',
        'retake_count' => 'number of retakes',
        'extra_retake_count' => 'extra retakes',
        'allow_review' => 'review option',
        'cheating_attempts' => 'attempt limit',

        'cover_photo' => 'cover photo',
        'profile_picture' => 'profile picture',
        'file_name' => 'file name',
        'files' => 'files',
        'password' => 'password',
    ],

    'custom' => [
        'password' => [
            'confirmed' => "Your password and confirmation do not match.",
        ],
    ],

];
