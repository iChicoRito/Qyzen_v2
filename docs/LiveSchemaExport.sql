-- =============================================================================
-- Qyzen — Live Database Schema Export
-- =============================================================================
-- Source : Supabase project "Qyzen" (ref: tjjaastncbzzsddgmqsh, ap-southeast-1)
-- Engine : PostgreSQL 17.6
-- Scope  : public schema only (auth/storage/realtime system schemas excluded)
-- Date   : 2026-06-29
--
-- Reconstructed from the live catalog: columns, defaults, PK/FK/UNIQUE/CHECK
-- constraints (with ON DELETE rules), indexes, RLS policies, SQL functions,
-- triggers, and the supabase_realtime publication.
--
-- NOTE: This is a STRUCTURE export (DDL), not data. Identity columns show their
-- live default (some use explicit sequences, others are catalog identities);
-- recreate sequences/identities as needed on import.
-- =============================================================================

-- ============================ 1. TABLES ======================================

-- ---- tbl_academic_year ----
CREATE TABLE public.tbl_academic_year (
  id          bigint  NOT NULL,
  year        text    NOT NULL,
  is_active   boolean NOT NULL DEFAULT true,
  CONSTRAINT academic_year_new_pkey     PRIMARY KEY (id),
  CONSTRAINT academic_year_new_year_key UNIQUE (year)
);

-- ---- tbl_academic_term ----
CREATE TABLE public.tbl_academic_term (
  id               bigint  NOT NULL,
  term_name        text    NOT NULL,
  semester         text    NOT NULL,
  academic_year_id bigint  NOT NULL,
  is_active        boolean NOT NULL DEFAULT true,
  CONSTRAINT academic_term_new_pkey         PRIMARY KEY (id),
  CONSTRAINT academic_term_new_unique_term  UNIQUE (term_name, semester, academic_year_id),
  CONSTRAINT academic_term_new_semester_check CHECK (semester = ANY (ARRAY['1st Semester','2nd Semester'])),
  CONSTRAINT academic_term_new_academic_year_id_fkey
    FOREIGN KEY (academic_year_id) REFERENCES public.tbl_academic_year(id) ON DELETE RESTRICT
);

-- ---- tbl_users ----
CREATE TABLE public.tbl_users (
  id              bigint  NOT NULL DEFAULT nextval('tbl_users_id_seq'::regclass),
  user_type       text    NOT NULL,
  user_id         text    NOT NULL,
  given_name      text    NOT NULL,
  surname         text    NOT NULL,
  email           text    NOT NULL,
  is_active       boolean NOT NULL DEFAULT true,
  created_at      timestamptz NOT NULL DEFAULT now(),
  updated_at      timestamptz NOT NULL DEFAULT now(),
  deleted_at      timestamptz,
  profile_picture text,
  cover_photo     text,
  CONSTRAINT users_pkey        PRIMARY KEY (id),
  CONSTRAINT users_email_key   UNIQUE (email),
  CONSTRAINT users_user_id_key UNIQUE (user_id),
  CONSTRAINT users_user_type_check CHECK (user_type = ANY (ARRAY['admin','student','educator']))
);

-- ---- tbl_roles ----
CREATE TABLE public.tbl_roles (
  id          bigint  NOT NULL,
  name        text    NOT NULL,
  description text    NOT NULL,
  is_system   boolean NOT NULL DEFAULT false,
  is_active   boolean NOT NULL DEFAULT true,
  CONSTRAINT roles_pkey      PRIMARY KEY (id),
  CONSTRAINT roles_name_key  UNIQUE (name),
  CONSTRAINT roles_name_check CHECK (name ~ '^[a-z]+(_[a-z]+)*$')
);

-- ---- tbl_permissions ----
CREATE TABLE public.tbl_permissions (
  id                bigint  NOT NULL,
  name              text    NOT NULL,
  resource          text    NOT NULL,
  action            text    NOT NULL,
  description       text    NOT NULL,
  module            text    NOT NULL,
  is_active         boolean NOT NULL DEFAULT true,
  permission_string text,
  CONSTRAINT permissions_pkey                   PRIMARY KEY (id),
  CONSTRAINT permissions_permission_string_key  UNIQUE (permission_string)
);

-- ---- tbl_role_permissions (M:N roles↔permissions) ----
CREATE TABLE public.tbl_role_permissions (
  id            bigint NOT NULL,
  role_id       bigint NOT NULL,
  permission_id bigint NOT NULL,
  CONSTRAINT role_permissions_pkey        PRIMARY KEY (id),
  CONSTRAINT role_permissions_unique_pair UNIQUE (role_id, permission_id),
  CONSTRAINT role_permissions_role_id_fkey
    FOREIGN KEY (role_id) REFERENCES public.tbl_roles(id) ON DELETE CASCADE,
  CONSTRAINT role_permissions_permission_id_fkey
    FOREIGN KEY (permission_id) REFERENCES public.tbl_permissions(id) ON DELETE CASCADE
);

-- ---- tbl_user_roles (M:N users↔roles, soft-delete) ----
CREATE TABLE public.tbl_user_roles (
  id         bigint NOT NULL DEFAULT nextval('tbl_user_roles_id_seq'::regclass),
  user_id    bigint NOT NULL,
  role_id    bigint NOT NULL,
  created_at timestamptz NOT NULL DEFAULT now(),
  updated_at timestamptz NOT NULL DEFAULT now(),
  deleted_at timestamptz,
  CONSTRAINT user_roles_pkey            PRIMARY KEY (id),
  CONSTRAINT user_roles_user_role_unique UNIQUE (user_id, role_id),
  CONSTRAINT user_roles_user_id_fkey
    FOREIGN KEY (user_id) REFERENCES public.tbl_users(id) ON DELETE CASCADE,
  CONSTRAINT user_roles_role_id_fkey
    FOREIGN KEY (role_id) REFERENCES public.tbl_roles(id) ON DELETE CASCADE
);

-- ---- tbl_sections ----
CREATE TABLE public.tbl_sections (
  id               bigint  NOT NULL,
  educator_id      bigint  NOT NULL,
  academic_term_id bigint  NOT NULL,
  section_name     text    NOT NULL,
  is_active        boolean NOT NULL DEFAULT true,
  created_at       timestamptz NOT NULL DEFAULT now(),
  updated_at       timestamptz NOT NULL DEFAULT now(),
  CONSTRAINT tbl_sections_pkey PRIMARY KEY (id),
  CONSTRAINT tbl_sections_educator_id_fkey
    FOREIGN KEY (educator_id) REFERENCES public.tbl_users(id) ON DELETE CASCADE,
  CONSTRAINT tbl_sections_academic_term_id_fkey
    FOREIGN KEY (academic_term_id) REFERENCES public.tbl_academic_term(id) ON DELETE RESTRICT
);

-- ---- tbl_sections_term (M:N sections↔terms) ----
CREATE TABLE public.tbl_sections_term (
  id               bigint NOT NULL,
  section_id       bigint NOT NULL,
  academic_term_id bigint NOT NULL,
  CONSTRAINT tbl_sections_term_pkey        PRIMARY KEY (id),
  CONSTRAINT tbl_sections_term_unique_pair UNIQUE (section_id, academic_term_id),
  CONSTRAINT tbl_sections_term_section_id_fkey
    FOREIGN KEY (section_id) REFERENCES public.tbl_sections(id) ON DELETE CASCADE,
  CONSTRAINT tbl_sections_term_academic_term_id_fkey
    FOREIGN KEY (academic_term_id) REFERENCES public.tbl_academic_term(id) ON DELETE CASCADE
);

-- ---- tbl_subjects ----
CREATE TABLE public.tbl_subjects (
  id           bigint  NOT NULL,
  educator_id  bigint  NOT NULL,
  sections_id  bigint  NOT NULL,
  subject_code text    NOT NULL,
  subject_name text    NOT NULL,
  is_active    boolean NOT NULL DEFAULT true,
  created_at   timestamptz NOT NULL DEFAULT now(),
  updated_at   timestamptz NOT NULL DEFAULT now(),
  CONSTRAINT tbl_subjects_pkey PRIMARY KEY (id),
  CONSTRAINT tbl_subjects_unique_code_per_section UNIQUE (educator_id, sections_id, subject_code),
  CONSTRAINT tbl_subjects_unique_name_per_section UNIQUE (educator_id, sections_id, subject_name),
  CONSTRAINT tbl_subjects_educator_id_fkey
    FOREIGN KEY (educator_id) REFERENCES public.tbl_users(id) ON DELETE CASCADE,
  CONSTRAINT tbl_subjects_sections_id_fkey
    FOREIGN KEY (sections_id) REFERENCES public.tbl_sections(id) ON DELETE CASCADE
);

-- ---- tbl_enrolled (student↔subject per educator) ----
CREATE TABLE public.tbl_enrolled (
  id          bigint  NOT NULL DEFAULT nextval('tbl_enrolled_id_seq'::regclass),
  student_id  bigint  NOT NULL,
  educator_id bigint  NOT NULL,
  subject_id  bigint  NOT NULL,
  is_active   boolean NOT NULL DEFAULT true,
  created_at  timestamptz NOT NULL DEFAULT now(),
  updated_at  timestamptz NOT NULL DEFAULT now(),
  CONSTRAINT tbl_enrolled_pkey PRIMARY KEY (id),
  CONSTRAINT tbl_enrolled_unique_student_subject_per_educator UNIQUE (educator_id, student_id, subject_id),
  CONSTRAINT tbl_enrolled_student_id_fkey
    FOREIGN KEY (student_id) REFERENCES public.tbl_users(id) ON DELETE CASCADE,
  CONSTRAINT tbl_enrolled_educator_id_fkey
    FOREIGN KEY (educator_id) REFERENCES public.tbl_users(id) ON DELETE CASCADE,
  CONSTRAINT tbl_enrolled_subject_id_fkey
    FOREIGN KEY (subject_id) REFERENCES public.tbl_subjects(id) ON DELETE CASCADE
);

-- ---- tbl_assessments ----
-- NOTE: ordinal_position 5 is absent in the live catalog (a dropped column);
-- columns below are listed in catalog order.
CREATE TABLE public.tbl_assessments (
  id                bigint  NOT NULL,
  educator_id       bigint  NOT NULL,
  subject_id        bigint  NOT NULL,
  section_id        bigint  NOT NULL,
  assessment_code   text    NOT NULL,
  time_limit        text    NOT NULL,
  cheating_attempts integer NOT NULL DEFAULT 0,
  is_shuffle        boolean NOT NULL DEFAULT false,
  is_active         boolean NOT NULL DEFAULT true,
  start_date        date    NOT NULL,
  end_date          date    NOT NULL,
  start_time        time    NOT NULL,
  end_time          time    NOT NULL,
  created_at        timestamptz NOT NULL DEFAULT now(),
  updated_at        timestamptz NOT NULL DEFAULT now(),
  term              bigint  NOT NULL,
  allow_review      boolean NOT NULL DEFAULT false,
  allow_hint        boolean NOT NULL DEFAULT false,
  hint_count        integer NOT NULL DEFAULT 0,
  allow_retake      boolean NOT NULL DEFAULT false,
  retake_count      integer NOT NULL DEFAULT 0,
  CONSTRAINT tbl_assessments_pkey PRIMARY KEY (id),
  CONSTRAINT tbl_assessments_unique_code_per_subject_section_term
    UNIQUE (assessment_code, subject_id, section_id, term),
  CONSTRAINT tbl_assessments_educator_id_fkey
    FOREIGN KEY (educator_id) REFERENCES public.tbl_users(id) ON DELETE CASCADE,
  CONSTRAINT tbl_assessments_subject_id_fkey
    FOREIGN KEY (subject_id) REFERENCES public.tbl_subjects(id) ON DELETE CASCADE,
  CONSTRAINT tbl_assessments_section_id_fkey
    FOREIGN KEY (section_id) REFERENCES public.tbl_sections(id) ON DELETE CASCADE,
  CONSTRAINT tbl_assessments_term_fkey
    FOREIGN KEY (term) REFERENCES public.tbl_academic_term(id) ON DELETE CASCADE
);

-- ---- tbl_quizzes (questions) ----
CREATE TABLE public.tbl_quizzes (
  id             bigint NOT NULL,
  assessment_id  bigint NOT NULL,
  subject_id     bigint NOT NULL,
  section_id     bigint NOT NULL,
  educator_id    bigint NOT NULL,
  question       text   NOT NULL,
  quiz_type      text   NOT NULL,
  choices        jsonb,
  correct_answer text   NOT NULL,
  created_at     timestamptz NOT NULL DEFAULT now(),
  updated_at     timestamptz NOT NULL DEFAULT now(),
  CONSTRAINT tbl_quizzes_pkey PRIMARY KEY (id),
  CONSTRAINT tbl_quizzes_quiz_type_check CHECK (quiz_type = ANY (ARRAY['multiple_choice','identification'])),
  CONSTRAINT tbl_quizzes_assessment_id_fkey
    FOREIGN KEY (assessment_id) REFERENCES public.tbl_assessments(id) ON DELETE CASCADE,
  CONSTRAINT tbl_quizzes_subject_id_fkey
    FOREIGN KEY (subject_id) REFERENCES public.tbl_subjects(id) ON DELETE CASCADE,
  CONSTRAINT tbl_quizzes_section_id_fkey
    FOREIGN KEY (section_id) REFERENCES public.tbl_sections(id) ON DELETE CASCADE,
  CONSTRAINT tbl_quizzes_educator_id_fkey
    FOREIGN KEY (educator_id) REFERENCES public.tbl_users(id) ON DELETE CASCADE
);

-- ---- tbl_scores (one row per attempt) ----
CREATE TABLE public.tbl_scores (
  id               bigint  NOT NULL DEFAULT nextval('tbl_scores_id_seq'::regclass),
  student_id       bigint  NOT NULL,
  educator_id      bigint  NOT NULL,
  assessment_id    bigint  NOT NULL,
  subject_id       bigint  NOT NULL,
  section_id       bigint  NOT NULL,
  score            integer,
  total_questions  integer NOT NULL DEFAULT 0,
  student_answer   jsonb   NOT NULL DEFAULT '{}'::jsonb,
  warning_attempts integer NOT NULL DEFAULT 0,
  status           text    NOT NULL DEFAULT 'in_progress',
  is_passed        boolean NOT NULL DEFAULT false,
  taken_at         timestamptz NOT NULL DEFAULT now(),
  submitted_at     timestamptz,
  created_at       timestamptz NOT NULL DEFAULT now(),
  updated_at       timestamptz NOT NULL DEFAULT now(),
  CONSTRAINT tbl_scores_pkey PRIMARY KEY (id),
  CONSTRAINT tbl_scores_status_check CHECK (status = ANY (ARRAY['in_progress','submitted','passed','failed'])),
  CONSTRAINT tbl_scores_student_id_fkey
    FOREIGN KEY (student_id) REFERENCES public.tbl_users(id) ON DELETE CASCADE,
  CONSTRAINT tbl_scores_educator_id_fkey
    FOREIGN KEY (educator_id) REFERENCES public.tbl_users(id) ON DELETE CASCADE,
  CONSTRAINT tbl_scores_assessment_id_fkey
    FOREIGN KEY (assessment_id) REFERENCES public.tbl_assessments(id) ON DELETE CASCADE,
  CONSTRAINT tbl_scores_subject_id_fkey
    FOREIGN KEY (subject_id) REFERENCES public.tbl_subjects(id) ON DELETE CASCADE,
  CONSTRAINT tbl_scores_section_id_fkey
    FOREIGN KEY (section_id) REFERENCES public.tbl_sections(id) ON DELETE CASCADE
);

-- ---- tbl_student_assessment_retakes (educator-granted extra attempts) ----
CREATE TABLE public.tbl_student_assessment_retakes (
  id                 bigint  NOT NULL DEFAULT nextval('tbl_student_assessment_retakes_id_seq'::regclass),
  educator_id        bigint  NOT NULL,
  student_id         bigint  NOT NULL,
  assessment_id      bigint  NOT NULL,
  extra_retake_count integer NOT NULL DEFAULT 0,
  is_active          boolean NOT NULL DEFAULT true,
  created_at         timestamptz NOT NULL DEFAULT now(),
  updated_at         timestamptz NOT NULL DEFAULT now(),
  CONSTRAINT tbl_student_assessment_retakes_pkey PRIMARY KEY (id),
  CONSTRAINT idx_tbl_student_assessment_retakes_unique_pair UNIQUE (educator_id, student_id, assessment_id),
  CONSTRAINT tbl_student_assessment_retakes_educator_id_fkey
    FOREIGN KEY (educator_id) REFERENCES public.tbl_users(id) ON DELETE CASCADE,
  CONSTRAINT tbl_student_assessment_retakes_student_id_fkey
    FOREIGN KEY (student_id) REFERENCES public.tbl_users(id) ON DELETE CASCADE,
  CONSTRAINT tbl_student_assessment_retakes_assessment_id_fkey
    FOREIGN KEY (assessment_id) REFERENCES public.tbl_assessments(id) ON DELETE CASCADE
);

-- ---- tbl_student_presence (heartbeat) ----
CREATE TABLE public.tbl_student_presence (
  id           bigint NOT NULL DEFAULT nextval('tbl_student_presence_id_seq'::regclass),
  student_id   bigint NOT NULL,
  last_seen_at timestamptz NOT NULL DEFAULT now(),
  current_path text,
  created_at   timestamptz NOT NULL DEFAULT now(),
  updated_at   timestamptz NOT NULL DEFAULT now(),
  CONSTRAINT tbl_student_presence_pkey PRIMARY KEY (id),
  -- unique student_id is enforced by unique index idx_tbl_student_presence_student_id
  CONSTRAINT tbl_student_presence_student_id_fkey
    FOREIGN KEY (student_id) REFERENCES public.tbl_users(id) ON DELETE CASCADE
);

-- ---- tbl_group_chats (one per educator+subject+section) ----
CREATE TABLE public.tbl_group_chats (
  id          bigint NOT NULL,
  educator_id bigint NOT NULL,
  subject_id  bigint NOT NULL,
  section_id  bigint NOT NULL,
  created_at  timestamptz NOT NULL DEFAULT now(),
  updated_at  timestamptz NOT NULL DEFAULT now(),
  CONSTRAINT tbl_group_chats_pkey PRIMARY KEY (id),
  CONSTRAINT tbl_group_chats_unique_classroom UNIQUE (educator_id, subject_id, section_id),
  CONSTRAINT tbl_group_chats_educator_id_fkey
    FOREIGN KEY (educator_id) REFERENCES public.tbl_users(id) ON DELETE CASCADE,
  CONSTRAINT tbl_group_chats_subject_id_fkey
    FOREIGN KEY (subject_id) REFERENCES public.tbl_subjects(id) ON DELETE CASCADE,
  CONSTRAINT tbl_group_chats_section_id_fkey
    FOREIGN KEY (section_id) REFERENCES public.tbl_sections(id) ON DELETE CASCADE
);

-- ---- tbl_group_chat_messages ----
CREATE TABLE public.tbl_group_chat_messages (
  id             bigint NOT NULL,
  group_chat_id  bigint NOT NULL,
  sender_user_id bigint NOT NULL,
  content        text   NOT NULL,
  created_at     timestamptz NOT NULL DEFAULT now(),
  updated_at     timestamptz NOT NULL DEFAULT now(),
  CONSTRAINT tbl_group_chat_messages_pkey PRIMARY KEY (id),
  CONSTRAINT tbl_group_chat_messages_content_check CHECK (char_length(btrim(content)) > 0),
  CONSTRAINT tbl_group_chat_messages_group_chat_id_fkey
    FOREIGN KEY (group_chat_id) REFERENCES public.tbl_group_chats(id) ON DELETE CASCADE,
  CONSTRAINT tbl_group_chat_messages_sender_user_id_fkey
    FOREIGN KEY (sender_user_id) REFERENCES public.tbl_users(id) ON DELETE CASCADE
);

-- ---- tbl_group_chat_reads (per-user read marker) ----
CREATE TABLE public.tbl_group_chat_reads (
  id            bigint NOT NULL,
  group_chat_id bigint NOT NULL,
  user_id       bigint NOT NULL,
  last_read_at  timestamptz NOT NULL,
  created_at    timestamptz NOT NULL DEFAULT now(),
  updated_at    timestamptz NOT NULL DEFAULT now(),
  CONSTRAINT tbl_group_chat_reads_pkey        PRIMARY KEY (id),
  CONSTRAINT tbl_group_chat_reads_unique_pair UNIQUE (group_chat_id, user_id),
  CONSTRAINT tbl_group_chat_reads_group_chat_id_fkey
    FOREIGN KEY (group_chat_id) REFERENCES public.tbl_group_chats(id) ON DELETE CASCADE,
  CONSTRAINT tbl_group_chat_reads_user_id_fkey
    FOREIGN KEY (user_id) REFERENCES public.tbl_users(id) ON DELETE CASCADE
);

-- ---- tbl_learning_materials ----
CREATE TABLE public.tbl_learning_materials (
  id             bigint  NOT NULL,
  educator_id    bigint  NOT NULL,
  subject_id     bigint  NOT NULL,
  section_id     bigint  NOT NULL,
  storage_bucket text    NOT NULL DEFAULT 'learning-materials',
  storage_path   text    NOT NULL,
  file_name      text    NOT NULL,
  file_extension text    NOT NULL,
  mime_type      text    NOT NULL,
  file_size      bigint  NOT NULL DEFAULT 0,
  is_active      boolean NOT NULL DEFAULT true,
  created_at     timestamptz NOT NULL DEFAULT now(),
  updated_at     timestamptz NOT NULL DEFAULT now(),
  CONSTRAINT tbl_learning_materials_pkey PRIMARY KEY (id),
  CONSTRAINT tbl_learning_materials_educator_id_fkey
    FOREIGN KEY (educator_id) REFERENCES public.tbl_users(id) ON DELETE CASCADE,
  CONSTRAINT tbl_learning_materials_subject_id_fkey
    FOREIGN KEY (subject_id) REFERENCES public.tbl_subjects(id) ON DELETE CASCADE,
  CONSTRAINT tbl_learning_materials_section_id_fkey
    FOREIGN KEY (section_id) REFERENCES public.tbl_sections(id) ON DELETE CASCADE
);

-- ---- tbl_notifications ----
CREATE TABLE public.tbl_notifications (
  id                bigint  NOT NULL,
  recipient_user_id bigint  NOT NULL,
  actor_user_id     bigint  NOT NULL,
  event_type        text    NOT NULL,
  title             text    NOT NULL,
  message           text    NOT NULL,
  link_path         text,
  assessment_id     bigint,
  subject_id        bigint,
  section_id        bigint,
  metadata          jsonb,
  is_read           boolean NOT NULL DEFAULT false,
  read_at           timestamptz,
  created_at        timestamptz NOT NULL DEFAULT now(),
  updated_at        timestamptz NOT NULL DEFAULT now(),
  CONSTRAINT tbl_notifications_pkey PRIMARY KEY (id),
  CONSTRAINT tbl_notifications_event_type_check CHECK (event_type = ANY (ARRAY[
    'assessment_created','assessment_updated','assessment_deleted',
    'learning_material_uploaded','learning_material_deleted',
    'quiz_created','quiz_uploaded','quiz_updated','quiz_deleted',
    'enrollment_created','enrollment_updated','enrollment_deleted',
    'retake_updated','quiz_submitted'])),
  CONSTRAINT tbl_notifications_recipient_user_id_fkey
    FOREIGN KEY (recipient_user_id) REFERENCES public.tbl_users(id) ON DELETE CASCADE,
  CONSTRAINT tbl_notifications_actor_user_id_fkey
    FOREIGN KEY (actor_user_id) REFERENCES public.tbl_users(id) ON DELETE CASCADE,
  CONSTRAINT tbl_notifications_assessment_id_fkey
    FOREIGN KEY (assessment_id) REFERENCES public.tbl_assessments(id) ON DELETE SET NULL,
  CONSTRAINT tbl_notifications_subject_id_fkey
    FOREIGN KEY (subject_id) REFERENCES public.tbl_subjects(id) ON DELETE SET NULL,
  CONSTRAINT tbl_notifications_section_id_fkey
    FOREIGN KEY (section_id) REFERENCES public.tbl_sections(id) ON DELETE SET NULL
);

-- ============================ 2. INDEXES =====================================
-- (PK/UNIQUE-constraint-backing indexes omitted; created with the constraints above.)

CREATE INDEX idx_tbl_assessments_assessment_code ON public.tbl_assessments USING btree (assessment_code);
CREATE INDEX idx_tbl_assessments_educator_id     ON public.tbl_assessments USING btree (educator_id);
CREATE INDEX idx_tbl_assessments_section_id      ON public.tbl_assessments USING btree (section_id);
CREATE INDEX idx_tbl_assessments_start_date      ON public.tbl_assessments USING btree (start_date);
CREATE INDEX idx_tbl_assessments_subject_id      ON public.tbl_assessments USING btree (subject_id);
CREATE INDEX idx_tbl_assessments_term            ON public.tbl_assessments USING btree (term);

CREATE INDEX idx_tbl_enrolled_educator_id ON public.tbl_enrolled USING btree (educator_id);
CREATE INDEX idx_tbl_enrolled_student_id  ON public.tbl_enrolled USING btree (student_id);
CREATE INDEX idx_tbl_enrolled_subject_id  ON public.tbl_enrolled USING btree (subject_id);

CREATE INDEX idx_tbl_group_chat_messages_group_chat_created_at ON public.tbl_group_chat_messages USING btree (group_chat_id, created_at DESC);
CREATE INDEX idx_tbl_group_chat_messages_sender_user_id        ON public.tbl_group_chat_messages USING btree (sender_user_id, created_at DESC);

CREATE INDEX idx_tbl_group_chat_reads_group_chat_last_read_at ON public.tbl_group_chat_reads USING btree (group_chat_id, last_read_at DESC);
CREATE INDEX idx_tbl_group_chat_reads_user_id                 ON public.tbl_group_chat_reads USING btree (user_id);

CREATE INDEX idx_tbl_group_chats_educator_id ON public.tbl_group_chats USING btree (educator_id);
CREATE INDEX idx_tbl_group_chats_section_id  ON public.tbl_group_chats USING btree (section_id);
CREATE INDEX idx_tbl_group_chats_subject_id  ON public.tbl_group_chats USING btree (subject_id);

CREATE INDEX idx_tbl_learning_materials_educator_id  ON public.tbl_learning_materials USING btree (educator_id);
CREATE INDEX idx_tbl_learning_materials_section_id   ON public.tbl_learning_materials USING btree (section_id);
CREATE INDEX idx_tbl_learning_materials_storage_path ON public.tbl_learning_materials USING btree (storage_bucket, storage_path);
CREATE INDEX idx_tbl_learning_materials_subject_id   ON public.tbl_learning_materials USING btree (subject_id);
CREATE INDEX idx_tbl_learning_materials_updated_at   ON public.tbl_learning_materials USING btree (updated_at DESC);

CREATE INDEX idx_tbl_notifications_actor_user_id              ON public.tbl_notifications USING btree (actor_user_id);
CREATE INDEX idx_tbl_notifications_assessment_id             ON public.tbl_notifications USING btree (assessment_id);
CREATE INDEX idx_tbl_notifications_recipient_created_at      ON public.tbl_notifications USING btree (recipient_user_id, created_at DESC);
CREATE INDEX idx_tbl_notifications_recipient_unread_created_at ON public.tbl_notifications USING btree (recipient_user_id, is_read, created_at DESC);

CREATE INDEX idx_tbl_quizzes_assessment_id ON public.tbl_quizzes USING btree (assessment_id);
CREATE INDEX idx_tbl_quizzes_educator_id   ON public.tbl_quizzes USING btree (educator_id);
CREATE INDEX idx_tbl_quizzes_quiz_type     ON public.tbl_quizzes USING btree (quiz_type);
CREATE INDEX idx_tbl_quizzes_section_id    ON public.tbl_quizzes USING btree (section_id);
CREATE INDEX idx_tbl_quizzes_subject_id    ON public.tbl_quizzes USING btree (subject_id);

CREATE INDEX idx_tbl_scores_assessment_id      ON public.tbl_scores USING btree (assessment_id);
CREATE INDEX idx_tbl_scores_section_id         ON public.tbl_scores USING btree (section_id);
CREATE INDEX idx_tbl_scores_status             ON public.tbl_scores USING btree (status);
CREATE INDEX idx_tbl_scores_student_assessment ON public.tbl_scores USING btree (student_id, assessment_id);
CREATE INDEX idx_tbl_scores_student_id         ON public.tbl_scores USING btree (student_id);
CREATE INDEX idx_tbl_scores_subject_id         ON public.tbl_scores USING btree (subject_id);

CREATE INDEX idx_tbl_sections_academic_term_id ON public.tbl_sections USING btree (academic_term_id);
CREATE INDEX idx_tbl_sections_educator_id      ON public.tbl_sections USING btree (educator_id);
CREATE INDEX idx_tbl_sections_section_name     ON public.tbl_sections USING btree (section_name);

CREATE INDEX idx_tbl_sections_term_academic_term_id ON public.tbl_sections_term USING btree (academic_term_id);
CREATE INDEX idx_tbl_sections_term_section_id       ON public.tbl_sections_term USING btree (section_id);

CREATE INDEX idx_tbl_student_assessment_retakes_assessment_id        ON public.tbl_student_assessment_retakes USING btree (assessment_id);
CREATE INDEX idx_tbl_student_assessment_retakes_educator_id          ON public.tbl_student_assessment_retakes USING btree (educator_id);
CREATE INDEX idx_tbl_student_assessment_retakes_student_assessment   ON public.tbl_student_assessment_retakes USING btree (student_id, assessment_id);

-- Enforces one presence row per student:
CREATE UNIQUE INDEX idx_tbl_student_presence_student_id ON public.tbl_student_presence USING btree (student_id);
CREATE INDEX idx_tbl_student_presence_last_seen_at      ON public.tbl_student_presence USING btree (last_seen_at);

CREATE INDEX idx_tbl_subjects_educator_id  ON public.tbl_subjects USING btree (educator_id);
CREATE INDEX idx_tbl_subjects_sections_id  ON public.tbl_subjects USING btree (sections_id);
CREATE INDEX idx_tbl_subjects_subject_code ON public.tbl_subjects USING btree (subject_code);
CREATE INDEX idx_tbl_subjects_subject_name ON public.tbl_subjects USING btree (subject_name);

CREATE INDEX idx_user_roles_role_id ON public.tbl_user_roles USING btree (role_id);
CREATE INDEX idx_user_roles_user_id ON public.tbl_user_roles USING btree (user_id);

CREATE INDEX idx_users_email     ON public.tbl_users USING btree (email);
CREATE INDEX idx_users_user_id   ON public.tbl_users USING btree (user_id);
CREATE INDEX idx_users_user_type ON public.tbl_users USING btree (user_type);

-- ============================ 3. FUNCTIONS ===================================
-- SECURITY DEFINER helpers used by RLS policies + RPCs called from the app.

-- get_current_tbl_user_id() -> maps the Supabase auth user (auth.email()) to tbl_users.id
CREATE OR REPLACE FUNCTION public.get_current_tbl_user_id()
 RETURNS bigint
 LANGUAGE sql
 STABLE SECURITY DEFINER
 SET search_path TO 'public'
AS $function$
  SELECT id
  FROM public.tbl_users
  WHERE email = auth.email()
    AND deleted_at IS NULL
  LIMIT 1;
$function$;

-- has_role(role_name) -> does the current auth user hold this active role?
CREATE OR REPLACE FUNCTION public.has_role(role_name text)
 RETURNS boolean
 LANGUAGE sql
 STABLE SECURITY DEFINER
 SET search_path TO 'public'
AS $function$
  SELECT EXISTS (
    SELECT 1
    FROM public.tbl_user_roles ur
    INNER JOIN public.tbl_roles r ON r.id = ur.role_id
    INNER JOIN public.tbl_users u ON u.id = ur.user_id
    WHERE u.email = auth.email()
      AND u.deleted_at IS NULL
      AND ur.deleted_at IS NULL
      AND r.is_active = TRUE
      AND r.name = role_name
  );
$function$;

-- user_has_permission(required_permission) -> roles→permissions grant check
CREATE OR REPLACE FUNCTION public.user_has_permission(required_permission text)
 RETURNS boolean
 LANGUAGE sql
 STABLE SECURITY DEFINER
 SET search_path TO 'public'
AS $function$
  SELECT EXISTS (
    SELECT 1
    FROM public.tbl_user_roles user_roles
    INNER JOIN public.tbl_roles roles
      ON roles.id = user_roles.role_id
    INNER JOIN public.tbl_role_permissions role_permissions
      ON role_permissions.role_id = roles.id
    INNER JOIN public.tbl_permissions permissions
      ON permissions.id = role_permissions.permission_id
    WHERE user_roles.user_id = public.get_current_tbl_user_id()
      AND user_roles.deleted_at IS NULL
      AND roles.is_active = TRUE
      AND permissions.is_active = TRUE
      AND permissions.permission_string = required_permission
  );
$function$;

-- validate_section_term_uniqueness(section_id, term_id) -> used by the section-term trigger
CREATE OR REPLACE FUNCTION public.validate_section_term_uniqueness(input_section_id bigint, input_academic_term_id bigint)
 RETURNS boolean
 LANGUAGE sql
 STABLE SECURITY DEFINER
 SET search_path TO 'public'
AS $function$
  SELECT NOT EXISTS (
    SELECT 1
    FROM public.tbl_sections current_section
    INNER JOIN public.tbl_sections_term current_term
      ON current_term.section_id = current_section.id
    INNER JOIN public.tbl_sections compare_section
      ON compare_section.educator_id = current_section.educator_id
     AND compare_section.section_name = current_section.section_name
     AND compare_section.id <> current_section.id
    INNER JOIN public.tbl_sections_term compare_term
      ON compare_term.section_id = compare_section.id
     AND compare_term.academic_term_id = input_academic_term_id
    WHERE current_section.id = input_section_id
  );
$function$;

-- enforce_section_term_uniqueness() -> trigger fn wrapping the validator
CREATE OR REPLACE FUNCTION public.enforce_section_term_uniqueness()
 RETURNS trigger
 LANGUAGE plpgsql
 SECURITY DEFINER
 SET search_path TO 'public'
AS $function$
BEGIN
  IF NOT public.validate_section_term_uniqueness(NEW.section_id, NEW.academic_term_id) THEN
    RAISE EXCEPTION 'Section name already exists in the selected academic term.';
  END IF;
  RETURN NEW;
END;
$function$;

-- get_group_chat_list() -> RPC: visible group chats + unread/online counts for current user
CREATE OR REPLACE FUNCTION public.get_group_chat_list()
 RETURNS TABLE(group_chat_id bigint, subject_id bigint, section_id bigint, educator_id bigint, subject_name text, section_name text, student_count integer, online_student_count integer, last_message_preview text, last_message_at timestamp with time zone, last_message_sender_user_id bigint, last_message_sender_display_name text, unread_count integer)
 LANGUAGE sql
 STABLE SECURITY DEFINER
 SET search_path TO 'public'
AS $function$
  WITH accessible_chats AS (
    SELECT
      chats.id, chats.subject_id, chats.section_id, chats.educator_id, chats.created_at,
      subjects.subject_name, sections.section_name
    FROM public.tbl_group_chats AS chats
    INNER JOIN public.tbl_subjects AS subjects ON subjects.id = chats.subject_id
    INNER JOIN public.tbl_sections AS sections ON sections.id = chats.section_id
    WHERE (
      (public.has_role('educator') AND chats.educator_id = public.get_current_tbl_user_id())
      OR (public.has_role('student') AND EXISTS (
          SELECT 1 FROM public.tbl_enrolled AS enrolled
          WHERE enrolled.educator_id = chats.educator_id
            AND enrolled.subject_id = chats.subject_id
            AND enrolled.student_id = public.get_current_tbl_user_id()
            AND enrolled.is_active = TRUE))
    )
  ),
  active_counts AS (
    SELECT chats.id AS group_chat_id, COUNT(DISTINCT enrolled.student_id)::INT AS student_count
    FROM accessible_chats AS chats
    INNER JOIN public.tbl_enrolled AS enrolled
      ON enrolled.educator_id = chats.educator_id AND enrolled.subject_id = chats.subject_id AND enrolled.is_active = TRUE
    INNER JOIN public.tbl_users AS student_user
      ON student_user.id = enrolled.student_id AND student_user.user_type = 'student'
     AND student_user.is_active = TRUE AND student_user.deleted_at IS NULL
    GROUP BY chats.id
  ),
  online_counts AS (
    SELECT chats.id AS group_chat_id, COUNT(DISTINCT presence.student_id)::INT AS online_student_count
    FROM accessible_chats AS chats
    INNER JOIN public.tbl_enrolled AS enrolled
      ON enrolled.educator_id = chats.educator_id AND enrolled.subject_id = chats.subject_id AND enrolled.is_active = TRUE
    INNER JOIN public.tbl_users AS student_user
      ON student_user.id = enrolled.student_id AND student_user.user_type = 'student'
     AND student_user.is_active = TRUE AND student_user.deleted_at IS NULL
    INNER JOIN public.tbl_student_presence AS presence
      ON presence.student_id = enrolled.student_id AND presence.last_seen_at >= NOW() - INTERVAL '60 seconds'
    GROUP BY chats.id
  )
  SELECT
    chats.id AS group_chat_id, chats.subject_id, chats.section_id, chats.educator_id,
    chats.subject_name, chats.section_name, counts.student_count,
    COALESCE(online.online_student_count, 0) AS online_student_count,
    last_message.content AS last_message_preview, last_message.created_at AS last_message_at,
    last_message.sender_user_id AS last_message_sender_user_id,
    last_message.sender_display_name AS last_message_sender_display_name,
    COALESCE(unread.unread_count, 0) AS unread_count
  FROM accessible_chats AS chats
  INNER JOIN active_counts AS counts ON counts.group_chat_id = chats.id
  LEFT JOIN online_counts AS online ON online.group_chat_id = chats.id
  LEFT JOIN public.tbl_group_chat_reads AS reads
    ON reads.group_chat_id = chats.id AND reads.user_id = public.get_current_tbl_user_id()
  LEFT JOIN LATERAL (
    SELECT message.content, message.created_at, message.sender_user_id,
      TRIM(CONCAT(sender_user.given_name, ' ', sender_user.surname)) AS sender_display_name
    FROM public.tbl_group_chat_messages AS message
    INNER JOIN public.tbl_users AS sender_user ON sender_user.id = message.sender_user_id AND sender_user.deleted_at IS NULL
    WHERE message.group_chat_id = chats.id
    ORDER BY message.created_at DESC, message.id DESC LIMIT 1
  ) AS last_message ON TRUE
  LEFT JOIN LATERAL (
    SELECT COUNT(*)::INT AS unread_count
    FROM public.tbl_group_chat_messages AS message
    WHERE message.group_chat_id = chats.id
      AND message.sender_user_id <> public.get_current_tbl_user_id()
      AND message.created_at > COALESCE(reads.last_read_at, 'epoch'::TIMESTAMPTZ)
  ) AS unread ON TRUE
  ORDER BY COALESCE(last_message.created_at, chats.created_at) DESC, chats.subject_name ASC;
$function$;

-- get_group_chat_messages(target_group_chat_id) -> RPC: message history for an authorized chat
CREATE OR REPLACE FUNCTION public.get_group_chat_messages(target_group_chat_id bigint)
 RETURNS TABLE(message_id bigint, group_chat_id bigint, sender_user_id bigint, sender_display_name text, sender_role text, content text, created_at timestamp with time zone)
 LANGUAGE sql
 STABLE SECURITY DEFINER
 SET search_path TO 'public'
AS $function$
  WITH authorized_chat AS (
    SELECT chats.id
    FROM public.tbl_group_chats AS chats
    WHERE chats.id = target_group_chat_id
      AND (
        (public.has_role('educator') AND chats.educator_id = public.get_current_tbl_user_id())
        OR (public.has_role('student') AND EXISTS (
            SELECT 1 FROM public.tbl_enrolled AS enrolled
            WHERE enrolled.educator_id = chats.educator_id
              AND enrolled.subject_id = chats.subject_id
              AND enrolled.student_id = public.get_current_tbl_user_id()
              AND enrolled.is_active = TRUE))
      )
  )
  SELECT
    message.id AS message_id, message.group_chat_id, message.sender_user_id,
    TRIM(CONCAT(sender_user.given_name, ' ', sender_user.surname)) AS sender_display_name,
    sender_user.user_type AS sender_role, message.content, message.created_at
  FROM authorized_chat
  INNER JOIN public.tbl_group_chat_messages AS message ON message.group_chat_id = authorized_chat.id
  INNER JOIN public.tbl_users AS sender_user ON sender_user.id = message.sender_user_id AND sender_user.deleted_at IS NULL
  ORDER BY message.created_at ASC, message.id ASC;
$function$;

-- export_public_schema_data() -> utility: dumps every public table as jsonb (data export helper)
CREATE OR REPLACE FUNCTION public.export_public_schema_data()
 RETURNS TABLE(table_name text, rows jsonb)
 LANGUAGE plpgsql
 SET search_path TO 'public', 'pg_temp'
AS $function$
DECLARE
  current_table record;
  current_rows jsonb;
BEGIN
  FOR current_table IN
    SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename
  LOOP
    EXECUTE format(
      'SELECT COALESCE(jsonb_agg(to_jsonb(source_rows)), ''[]''::jsonb) FROM public.%I AS source_rows',
      current_table.tablename
    ) INTO current_rows;
    table_name := current_table.tablename;
    rows := current_rows;
    RETURN NEXT;
  END LOOP;
END;
$function$;

-- rls_auto_enable() -> EVENT TRIGGER fn: auto-enables RLS on any new public table
CREATE OR REPLACE FUNCTION public.rls_auto_enable()
 RETURNS event_trigger
 LANGUAGE plpgsql
 SECURITY DEFINER
 SET search_path TO 'pg_catalog'
AS $function$
DECLARE
  cmd record;
BEGIN
  FOR cmd IN
    SELECT * FROM pg_event_trigger_ddl_commands()
    WHERE command_tag IN ('CREATE TABLE', 'CREATE TABLE AS', 'SELECT INTO')
      AND object_type IN ('table','partitioned table')
  LOOP
     IF cmd.schema_name IS NOT NULL AND cmd.schema_name IN ('public')
        AND cmd.schema_name NOT IN ('pg_catalog','information_schema')
        AND cmd.schema_name NOT LIKE 'pg_toast%' AND cmd.schema_name NOT LIKE 'pg_temp%' THEN
      BEGIN
        EXECUTE format('alter table if exists %s enable row level security', cmd.object_identity);
        RAISE LOG 'rls_auto_enable: enabled RLS on %', cmd.object_identity;
      EXCEPTION WHEN OTHERS THEN
        RAISE LOG 'rls_auto_enable: failed to enable RLS on %', cmd.object_identity;
      END;
     END IF;
  END LOOP;
END;
$function$;

-- ============================ 4. TRIGGERS ====================================

CREATE TRIGGER trg_enforce_section_term_uniqueness
  BEFORE INSERT OR UPDATE ON public.tbl_sections_term
  FOR EACH ROW EXECUTE FUNCTION public.enforce_section_term_uniqueness();

-- NOTE: an event trigger backs rls_auto_enable() (fires on CREATE TABLE in public).

-- ============================ 5. ROW-LEVEL SECURITY ==========================
-- RLS is ENABLED on all 21 public tables. Policies below are TO authenticated.
-- USING = read/visibility predicate; WITH CHECK = write predicate.

ALTER TABLE public.tbl_academic_year             ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.tbl_academic_term             ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.tbl_users                     ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.tbl_roles                     ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.tbl_permissions               ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.tbl_role_permissions          ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.tbl_user_roles                ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.tbl_sections                  ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.tbl_sections_term             ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.tbl_subjects                  ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.tbl_enrolled                  ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.tbl_assessments               ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.tbl_quizzes                   ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.tbl_scores                    ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.tbl_student_assessment_retakes ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.tbl_student_presence          ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.tbl_group_chats               ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.tbl_group_chat_messages       ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.tbl_group_chat_reads          ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.tbl_learning_materials        ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.tbl_notifications             ENABLE ROW LEVEL SECURITY;

-- ---- tbl_academic_year ----
CREATE POLICY "Admin full access on tbl_academic_year" ON public.tbl_academic_year
  AS PERMISSIVE FOR ALL TO authenticated USING (has_role('admin')) WITH CHECK (has_role('admin'));
CREATE POLICY "Authenticated users can read active academic years" ON public.tbl_academic_year
  AS PERMISSIVE FOR SELECT TO authenticated USING (is_active = true);

-- ---- tbl_academic_term ----
CREATE POLICY "Admin full access on tbl_academic_term" ON public.tbl_academic_term
  AS PERMISSIVE FOR ALL TO authenticated USING (has_role('admin')) WITH CHECK (has_role('admin'));
CREATE POLICY "Authenticated users can read active academic terms" ON public.tbl_academic_term
  AS PERMISSIVE FOR SELECT TO authenticated USING (is_active = true);

-- ---- tbl_users ----
CREATE POLICY "Admin full access on tbl_users" ON public.tbl_users
  AS PERMISSIVE FOR ALL TO authenticated USING (has_role('admin')) WITH CHECK (has_role('admin'));
CREATE POLICY "Users can read own profile" ON public.tbl_users
  AS PERMISSIVE FOR SELECT TO authenticated USING (email = auth.email());
CREATE POLICY "Users can update own profile" ON public.tbl_users
  AS PERMISSIVE FOR UPDATE TO authenticated
  USING ((id = get_current_tbl_user_id()) AND (deleted_at IS NULL))
  WITH CHECK ((id = get_current_tbl_user_id()) AND (deleted_at IS NULL));
CREATE POLICY "Educators can read students for enrollment" ON public.tbl_users
  AS PERMISSIVE FOR SELECT TO authenticated
  USING (has_role('educator') AND (user_type = 'student') AND (deleted_at IS NULL));
CREATE POLICY "Students can read assessment educators" ON public.tbl_users
  AS PERMISSIVE FOR SELECT TO authenticated
  USING (has_role('student') AND (user_type = 'educator') AND (deleted_at IS NULL)
    AND EXISTS (SELECT 1 FROM tbl_enrolled enrolled
      WHERE enrolled.student_id = get_current_tbl_user_id()
        AND enrolled.educator_id = tbl_users.id AND enrolled.is_active = true));

-- ---- tbl_roles ----
CREATE POLICY "Admin full access on tbl_roles" ON public.tbl_roles
  AS PERMISSIVE FOR ALL TO authenticated USING (has_role('admin')) WITH CHECK (has_role('admin'));
CREATE POLICY "Authenticated users can read active roles" ON public.tbl_roles
  AS PERMISSIVE FOR SELECT TO authenticated USING (is_active = true);

-- ---- tbl_permissions ----
CREATE POLICY "Admin full access on tbl_permissions" ON public.tbl_permissions
  AS PERMISSIVE FOR ALL TO authenticated USING (has_role('admin')) WITH CHECK (has_role('admin'));
CREATE POLICY "Authenticated users can read active permissions" ON public.tbl_permissions
  AS PERMISSIVE FOR SELECT TO authenticated USING (is_active = true);

-- ---- tbl_role_permissions ----
CREATE POLICY "Admin full access on tbl_role_permissions" ON public.tbl_role_permissions
  AS PERMISSIVE FOR ALL TO authenticated USING (has_role('admin')) WITH CHECK (has_role('admin'));
CREATE POLICY "Users can read permissions for their roles" ON public.tbl_role_permissions
  AS PERMISSIVE FOR SELECT TO authenticated
  USING (EXISTS (SELECT 1 FROM tbl_user_roles ur
    WHERE ur.role_id = tbl_role_permissions.role_id
      AND ur.user_id = get_current_tbl_user_id() AND ur.deleted_at IS NULL));

-- ---- tbl_user_roles ----
CREATE POLICY "Admin full access on tbl_user_roles" ON public.tbl_user_roles
  AS PERMISSIVE FOR ALL TO authenticated USING (has_role('admin')) WITH CHECK (has_role('admin'));
CREATE POLICY "Users can read own role links" ON public.tbl_user_roles
  AS PERMISSIVE FOR SELECT TO authenticated USING (user_id = get_current_tbl_user_id());

-- ---- tbl_sections (educator gated by user_has_permission) ----
CREATE POLICY "Admin full access on tbl_sections" ON public.tbl_sections
  AS PERMISSIVE FOR ALL TO authenticated USING (has_role('admin')) WITH CHECK (has_role('admin'));
CREATE POLICY "Educator section view access" ON public.tbl_sections
  AS PERMISSIVE FOR SELECT TO authenticated
  USING (has_role('educator') AND (educator_id = get_current_tbl_user_id()) AND user_has_permission('sections:view'));
CREATE POLICY "Educator section create access" ON public.tbl_sections
  AS PERMISSIVE FOR INSERT TO authenticated
  WITH CHECK (has_role('educator') AND (educator_id = get_current_tbl_user_id()) AND user_has_permission('sections:create'));
CREATE POLICY "Educator section update access" ON public.tbl_sections
  AS PERMISSIVE FOR UPDATE TO authenticated
  USING (has_role('educator') AND (educator_id = get_current_tbl_user_id()) AND user_has_permission('sections:update'))
  WITH CHECK (has_role('educator') AND (educator_id = get_current_tbl_user_id()) AND user_has_permission('sections:update'));
CREATE POLICY "Educator section delete access" ON public.tbl_sections
  AS PERMISSIVE FOR DELETE TO authenticated
  USING (has_role('educator') AND (educator_id = get_current_tbl_user_id()) AND user_has_permission('sections:delete'));
CREATE POLICY "Student section view access" ON public.tbl_sections
  AS PERMISSIVE FOR SELECT TO authenticated
  USING (has_role('student') AND EXISTS (
    SELECT 1 FROM tbl_enrolled enrolled JOIN tbl_subjects subject ON subject.id = enrolled.subject_id
    WHERE enrolled.student_id = get_current_tbl_user_id()
      AND enrolled.educator_id = tbl_sections.educator_id
      AND subject.sections_id = tbl_sections.id AND enrolled.is_active = true));

-- ---- tbl_sections_term (educator gated by user_has_permission + section ownership) ----
CREATE POLICY "Admin full access on tbl_sections_term" ON public.tbl_sections_term
  AS PERMISSIVE FOR ALL TO authenticated USING (has_role('admin')) WITH CHECK (has_role('admin'));
CREATE POLICY "Educator section term view access" ON public.tbl_sections_term
  AS PERMISSIVE FOR SELECT TO authenticated
  USING (has_role('educator') AND user_has_permission('sections:view') AND EXISTS (
    SELECT 1 FROM tbl_sections WHERE tbl_sections.id = tbl_sections_term.section_id AND tbl_sections.educator_id = get_current_tbl_user_id()));
CREATE POLICY "Educator section term create access" ON public.tbl_sections_term
  AS PERMISSIVE FOR INSERT TO authenticated
  WITH CHECK (has_role('educator') AND user_has_permission('sections:create') AND EXISTS (
    SELECT 1 FROM tbl_sections WHERE tbl_sections.id = tbl_sections_term.section_id AND tbl_sections.educator_id = get_current_tbl_user_id()));
CREATE POLICY "Educator section term update access" ON public.tbl_sections_term
  AS PERMISSIVE FOR UPDATE TO authenticated
  USING (has_role('educator') AND user_has_permission('sections:update') AND EXISTS (
    SELECT 1 FROM tbl_sections WHERE tbl_sections.id = tbl_sections_term.section_id AND tbl_sections.educator_id = get_current_tbl_user_id()))
  WITH CHECK (has_role('educator') AND user_has_permission('sections:update') AND EXISTS (
    SELECT 1 FROM tbl_sections WHERE tbl_sections.id = tbl_sections_term.section_id AND tbl_sections.educator_id = get_current_tbl_user_id()));
CREATE POLICY "Educator section term delete access" ON public.tbl_sections_term
  AS PERMISSIVE FOR DELETE TO authenticated
  USING (has_role('educator') AND user_has_permission('sections:delete') AND EXISTS (
    SELECT 1 FROM tbl_sections WHERE tbl_sections.id = tbl_sections_term.section_id AND tbl_sections.educator_id = get_current_tbl_user_id()));

-- ---- tbl_subjects (educator gated by user_has_permission) ----
CREATE POLICY "Admin full access on tbl_subjects" ON public.tbl_subjects
  AS PERMISSIVE FOR ALL TO authenticated USING (has_role('admin')) WITH CHECK (has_role('admin'));
CREATE POLICY "Educator subject view access" ON public.tbl_subjects
  AS PERMISSIVE FOR SELECT TO authenticated
  USING (has_role('educator') AND (educator_id = get_current_tbl_user_id()) AND user_has_permission('subjects:view'));
CREATE POLICY "Educator subject create access" ON public.tbl_subjects
  AS PERMISSIVE FOR INSERT TO authenticated
  WITH CHECK (has_role('educator') AND (educator_id = get_current_tbl_user_id()) AND user_has_permission('subjects:create'));
CREATE POLICY "Educator subject update access" ON public.tbl_subjects
  AS PERMISSIVE FOR UPDATE TO authenticated
  USING (has_role('educator') AND (educator_id = get_current_tbl_user_id()) AND user_has_permission('subjects:update'))
  WITH CHECK (has_role('educator') AND (educator_id = get_current_tbl_user_id()) AND user_has_permission('subjects:update'));
CREATE POLICY "Educator subject delete access" ON public.tbl_subjects
  AS PERMISSIVE FOR DELETE TO authenticated
  USING (has_role('educator') AND (educator_id = get_current_tbl_user_id()) AND user_has_permission('subjects:delete'));
CREATE POLICY "Student subject view access" ON public.tbl_subjects
  AS PERMISSIVE FOR SELECT TO authenticated
  USING (has_role('student') AND EXISTS (
    SELECT 1 FROM tbl_enrolled enrolled
    WHERE enrolled.student_id = get_current_tbl_user_id()
      AND enrolled.educator_id = tbl_subjects.educator_id
      AND enrolled.subject_id = tbl_subjects.id AND enrolled.is_active = true));

-- ---- tbl_enrolled ----
CREATE POLICY "Admin full access on tbl_enrolled" ON public.tbl_enrolled
  AS PERMISSIVE FOR ALL TO authenticated USING (has_role('admin')) WITH CHECK (has_role('admin'));
CREATE POLICY "Educator enrollment view access" ON public.tbl_enrolled
  AS PERMISSIVE FOR SELECT TO authenticated USING (has_role('educator') AND (educator_id = get_current_tbl_user_id()));
CREATE POLICY "Educator enrollment create access" ON public.tbl_enrolled
  AS PERMISSIVE FOR INSERT TO authenticated WITH CHECK (has_role('educator') AND (educator_id = get_current_tbl_user_id()));
CREATE POLICY "Educator enrollment update access" ON public.tbl_enrolled
  AS PERMISSIVE FOR UPDATE TO authenticated
  USING (has_role('educator') AND (educator_id = get_current_tbl_user_id()))
  WITH CHECK (has_role('educator') AND (educator_id = get_current_tbl_user_id()));
CREATE POLICY "Educator enrollment delete access" ON public.tbl_enrolled
  AS PERMISSIVE FOR DELETE TO authenticated USING (has_role('educator') AND (educator_id = get_current_tbl_user_id()));
CREATE POLICY "Student enrollment view access" ON public.tbl_enrolled
  AS PERMISSIVE FOR SELECT TO authenticated USING (has_role('student') AND (student_id = get_current_tbl_user_id()));

-- ---- tbl_assessments ----
CREATE POLICY "Educator assessment view access" ON public.tbl_assessments
  AS PERMISSIVE FOR SELECT TO authenticated USING (has_role('educator') AND (educator_id = get_current_tbl_user_id()));
CREATE POLICY "Educator assessment create access" ON public.tbl_assessments
  AS PERMISSIVE FOR INSERT TO authenticated WITH CHECK (has_role('educator') AND (educator_id = get_current_tbl_user_id()));
CREATE POLICY "Educator assessment update access" ON public.tbl_assessments
  AS PERMISSIVE FOR UPDATE TO authenticated
  USING (has_role('educator') AND (educator_id = get_current_tbl_user_id()))
  WITH CHECK (has_role('educator') AND (educator_id = get_current_tbl_user_id()));
CREATE POLICY "Educator assessment delete access" ON public.tbl_assessments
  AS PERMISSIVE FOR DELETE TO authenticated USING (has_role('educator') AND (educator_id = get_current_tbl_user_id()));
CREATE POLICY "Student assessment view access" ON public.tbl_assessments
  AS PERMISSIVE FOR SELECT TO authenticated
  USING (has_role('student') AND EXISTS (
    SELECT 1 FROM tbl_enrolled enrolled
    WHERE enrolled.student_id = get_current_tbl_user_id()
      AND enrolled.educator_id = tbl_assessments.educator_id
      AND enrolled.subject_id = tbl_assessments.subject_id AND enrolled.is_active = true));

-- ---- tbl_quizzes (NOTE: no admin policy; correct answers never exposed to students except via grading) ----
CREATE POLICY "Educator quiz view access" ON public.tbl_quizzes
  AS PERMISSIVE FOR SELECT TO authenticated USING (has_role('educator') AND (educator_id = get_current_tbl_user_id()));
CREATE POLICY "Educator quiz create access" ON public.tbl_quizzes
  AS PERMISSIVE FOR INSERT TO authenticated WITH CHECK (has_role('educator') AND (educator_id = get_current_tbl_user_id()));
CREATE POLICY "Educator quiz update access" ON public.tbl_quizzes
  AS PERMISSIVE FOR UPDATE TO authenticated
  USING (has_role('educator') AND (educator_id = get_current_tbl_user_id()))
  WITH CHECK (has_role('educator') AND (educator_id = get_current_tbl_user_id()));
CREATE POLICY "Educator quiz delete access" ON public.tbl_quizzes
  AS PERMISSIVE FOR DELETE TO authenticated USING (has_role('educator') AND (educator_id = get_current_tbl_user_id()));
CREATE POLICY "Student quiz view access" ON public.tbl_quizzes
  AS PERMISSIVE FOR SELECT TO authenticated
  USING (has_role('student') AND EXISTS (
    SELECT 1 FROM tbl_enrolled enrolled
    WHERE enrolled.student_id = get_current_tbl_user_id()
      AND enrolled.educator_id = tbl_quizzes.educator_id
      AND enrolled.subject_id = tbl_quizzes.subject_id AND enrolled.is_active = true));

-- ---- tbl_scores ----
CREATE POLICY "Admin full access on tbl_scores" ON public.tbl_scores
  AS PERMISSIVE FOR ALL TO authenticated USING (has_role('admin')) WITH CHECK (has_role('admin'));
CREATE POLICY "Educator score view access" ON public.tbl_scores
  AS PERMISSIVE FOR SELECT TO authenticated USING (has_role('educator') AND (educator_id = get_current_tbl_user_id()));
CREATE POLICY "Student score view access" ON public.tbl_scores
  AS PERMISSIVE FOR SELECT TO authenticated USING (has_role('student') AND (student_id = get_current_tbl_user_id()));
CREATE POLICY "Student score create access" ON public.tbl_scores
  AS PERMISSIVE FOR INSERT TO authenticated
  WITH CHECK (has_role('student') AND (student_id = get_current_tbl_user_id()) AND EXISTS (
    SELECT 1 FROM tbl_enrolled enrolled
    WHERE enrolled.student_id = tbl_scores.student_id
      AND enrolled.educator_id = tbl_scores.educator_id
      AND enrolled.subject_id = tbl_scores.subject_id AND enrolled.is_active = true));
CREATE POLICY "Student score update access" ON public.tbl_scores
  AS PERMISSIVE FOR UPDATE TO authenticated
  USING (has_role('student') AND (student_id = get_current_tbl_user_id()))
  WITH CHECK (has_role('student') AND (student_id = get_current_tbl_user_id()) AND EXISTS (
    SELECT 1 FROM tbl_enrolled enrolled
    WHERE enrolled.student_id = tbl_scores.student_id
      AND enrolled.educator_id = tbl_scores.educator_id
      AND enrolled.subject_id = tbl_scores.subject_id AND enrolled.is_active = true));

-- ---- tbl_student_assessment_retakes ----
CREATE POLICY "Admin full access on tbl_student_assessment_retakes" ON public.tbl_student_assessment_retakes
  AS PERMISSIVE FOR ALL TO authenticated USING (has_role('admin')) WITH CHECK (has_role('admin'));
CREATE POLICY "Educator student retake view access" ON public.tbl_student_assessment_retakes
  AS PERMISSIVE FOR SELECT TO authenticated USING (has_role('educator') AND (educator_id = get_current_tbl_user_id()));
CREATE POLICY "Educator student retake create access" ON public.tbl_student_assessment_retakes
  AS PERMISSIVE FOR INSERT TO authenticated WITH CHECK (has_role('educator') AND (educator_id = get_current_tbl_user_id()));
CREATE POLICY "Educator student retake update access" ON public.tbl_student_assessment_retakes
  AS PERMISSIVE FOR UPDATE TO authenticated
  USING (has_role('educator') AND (educator_id = get_current_tbl_user_id()))
  WITH CHECK (has_role('educator') AND (educator_id = get_current_tbl_user_id()));
CREATE POLICY "Educator student retake delete access" ON public.tbl_student_assessment_retakes
  AS PERMISSIVE FOR DELETE TO authenticated USING (has_role('educator') AND (educator_id = get_current_tbl_user_id()));
CREATE POLICY "Student retake grant view access" ON public.tbl_student_assessment_retakes
  AS PERMISSIVE FOR SELECT TO authenticated USING (has_role('student') AND (student_id = get_current_tbl_user_id()));

-- ---- tbl_student_presence ----
CREATE POLICY "Admin full access on tbl_student_presence" ON public.tbl_student_presence
  AS PERMISSIVE FOR ALL TO authenticated USING (has_role('admin')) WITH CHECK (has_role('admin'));
CREATE POLICY "Student own presence view access" ON public.tbl_student_presence
  AS PERMISSIVE FOR SELECT TO authenticated USING (has_role('student') AND (student_id = get_current_tbl_user_id()));
CREATE POLICY "Student own presence create access" ON public.tbl_student_presence
  AS PERMISSIVE FOR INSERT TO authenticated WITH CHECK (has_role('student') AND (student_id = get_current_tbl_user_id()));
CREATE POLICY "Student own presence update access" ON public.tbl_student_presence
  AS PERMISSIVE FOR UPDATE TO authenticated
  USING (has_role('student') AND (student_id = get_current_tbl_user_id()))
  WITH CHECK (has_role('student') AND (student_id = get_current_tbl_user_id()));
CREATE POLICY "Student own presence delete access" ON public.tbl_student_presence
  AS PERMISSIVE FOR DELETE TO authenticated USING (has_role('student') AND (student_id = get_current_tbl_user_id()));
CREATE POLICY "Educator student presence view access" ON public.tbl_student_presence
  AS PERMISSIVE FOR SELECT TO authenticated
  USING (has_role('educator') AND EXISTS (
    SELECT 1 FROM tbl_enrolled enrolled
    WHERE enrolled.student_id = tbl_student_presence.student_id
      AND enrolled.educator_id = get_current_tbl_user_id() AND enrolled.is_active = true));

-- ---- tbl_group_chats ----
CREATE POLICY "Admin full access on tbl_group_chats" ON public.tbl_group_chats
  AS PERMISSIVE FOR ALL TO authenticated USING (has_role('admin')) WITH CHECK (has_role('admin'));
CREATE POLICY "Educator group chat view access" ON public.tbl_group_chats
  AS PERMISSIVE FOR SELECT TO authenticated USING (has_role('educator') AND (educator_id = get_current_tbl_user_id()));
CREATE POLICY "Educator group chat create access" ON public.tbl_group_chats
  AS PERMISSIVE FOR INSERT TO authenticated
  WITH CHECK (has_role('educator') AND (educator_id = get_current_tbl_user_id()) AND EXISTS (
    SELECT 1 FROM tbl_subjects subject_row
    WHERE subject_row.id = tbl_group_chats.subject_id
      AND subject_row.educator_id = get_current_tbl_user_id()
      AND subject_row.sections_id = tbl_group_chats.section_id));
CREATE POLICY "Educator group chat delete access" ON public.tbl_group_chats
  AS PERMISSIVE FOR DELETE TO authenticated USING (has_role('educator') AND (educator_id = get_current_tbl_user_id()));
CREATE POLICY "Student group chat view access" ON public.tbl_group_chats
  AS PERMISSIVE FOR SELECT TO authenticated
  USING (has_role('student') AND EXISTS (
    SELECT 1 FROM tbl_enrolled enrolled
    WHERE enrolled.educator_id = tbl_group_chats.educator_id
      AND enrolled.subject_id = tbl_group_chats.subject_id
      AND enrolled.student_id = get_current_tbl_user_id() AND enrolled.is_active = true));

-- ---- tbl_group_chat_messages ----
CREATE POLICY "Admin full access on tbl_group_chat_messages" ON public.tbl_group_chat_messages
  AS PERMISSIVE FOR ALL TO authenticated USING (has_role('admin')) WITH CHECK (has_role('admin'));
CREATE POLICY "Educator group chat message view access" ON public.tbl_group_chat_messages
  AS PERMISSIVE FOR SELECT TO authenticated
  USING (has_role('educator') AND EXISTS (
    SELECT 1 FROM tbl_group_chats chats
    WHERE chats.id = tbl_group_chat_messages.group_chat_id AND chats.educator_id = get_current_tbl_user_id()));
CREATE POLICY "Educator group chat message create access" ON public.tbl_group_chat_messages
  AS PERMISSIVE FOR INSERT TO authenticated
  WITH CHECK (has_role('educator') AND (sender_user_id = get_current_tbl_user_id()) AND EXISTS (
    SELECT 1 FROM tbl_group_chats chats
    WHERE chats.id = tbl_group_chat_messages.group_chat_id AND chats.educator_id = get_current_tbl_user_id()));
CREATE POLICY "Student group chat message view access" ON public.tbl_group_chat_messages
  AS PERMISSIVE FOR SELECT TO authenticated
  USING (has_role('student') AND EXISTS (
    SELECT 1 FROM tbl_group_chats chats
      JOIN tbl_enrolled enrolled ON enrolled.educator_id = chats.educator_id AND enrolled.subject_id = chats.subject_id
    WHERE chats.id = tbl_group_chat_messages.group_chat_id
      AND enrolled.student_id = get_current_tbl_user_id() AND enrolled.is_active = true));
CREATE POLICY "Student group chat message create access" ON public.tbl_group_chat_messages
  AS PERMISSIVE FOR INSERT TO authenticated
  WITH CHECK (has_role('student') AND (sender_user_id = get_current_tbl_user_id()) AND EXISTS (
    SELECT 1 FROM tbl_group_chats chats
      JOIN tbl_enrolled enrolled ON enrolled.educator_id = chats.educator_id AND enrolled.subject_id = chats.subject_id
    WHERE chats.id = tbl_group_chat_messages.group_chat_id
      AND enrolled.student_id = get_current_tbl_user_id() AND enrolled.is_active = true));

-- ---- tbl_group_chat_reads (own marker; educator & student variants) ----
CREATE POLICY "Admin full access on tbl_group_chat_reads" ON public.tbl_group_chat_reads
  AS PERMISSIVE FOR ALL TO authenticated USING (has_role('admin')) WITH CHECK (has_role('admin'));
CREATE POLICY "Educator group chat read view access" ON public.tbl_group_chat_reads
  AS PERMISSIVE FOR SELECT TO authenticated
  USING (has_role('educator') AND EXISTS (
    SELECT 1 FROM tbl_group_chats chats
    WHERE chats.id = tbl_group_chat_reads.group_chat_id AND chats.educator_id = get_current_tbl_user_id()));
CREATE POLICY "Educator own group chat read create access" ON public.tbl_group_chat_reads
  AS PERMISSIVE FOR INSERT TO authenticated
  WITH CHECK (has_role('educator') AND (user_id = get_current_tbl_user_id()) AND EXISTS (
    SELECT 1 FROM tbl_group_chats chats
    WHERE chats.id = tbl_group_chat_reads.group_chat_id AND chats.educator_id = get_current_tbl_user_id()));
CREATE POLICY "Educator own group chat read update access" ON public.tbl_group_chat_reads
  AS PERMISSIVE FOR UPDATE TO authenticated
  USING (has_role('educator') AND (user_id = get_current_tbl_user_id()) AND EXISTS (
    SELECT 1 FROM tbl_group_chats chats
    WHERE chats.id = tbl_group_chat_reads.group_chat_id AND chats.educator_id = get_current_tbl_user_id()))
  WITH CHECK (has_role('educator') AND (user_id = get_current_tbl_user_id()) AND EXISTS (
    SELECT 1 FROM tbl_group_chats chats
    WHERE chats.id = tbl_group_chat_reads.group_chat_id AND chats.educator_id = get_current_tbl_user_id()));
CREATE POLICY "Student group chat read view access" ON public.tbl_group_chat_reads
  AS PERMISSIVE FOR SELECT TO authenticated
  USING (has_role('student') AND EXISTS (
    SELECT 1 FROM tbl_group_chats chats
      JOIN tbl_enrolled enrolled ON enrolled.educator_id = chats.educator_id AND enrolled.subject_id = chats.subject_id
    WHERE chats.id = tbl_group_chat_reads.group_chat_id
      AND enrolled.student_id = get_current_tbl_user_id() AND enrolled.is_active = true));
CREATE POLICY "Student own group chat read create access" ON public.tbl_group_chat_reads
  AS PERMISSIVE FOR INSERT TO authenticated
  WITH CHECK (has_role('student') AND (user_id = get_current_tbl_user_id()) AND EXISTS (
    SELECT 1 FROM tbl_group_chats chats
      JOIN tbl_enrolled enrolled ON enrolled.educator_id = chats.educator_id AND enrolled.subject_id = chats.subject_id
    WHERE chats.id = tbl_group_chat_reads.group_chat_id
      AND enrolled.student_id = get_current_tbl_user_id() AND enrolled.is_active = true));
CREATE POLICY "Student own group chat read update access" ON public.tbl_group_chat_reads
  AS PERMISSIVE FOR UPDATE TO authenticated
  USING (has_role('student') AND (user_id = get_current_tbl_user_id()) AND EXISTS (
    SELECT 1 FROM tbl_group_chats chats
      JOIN tbl_enrolled enrolled ON enrolled.educator_id = chats.educator_id AND enrolled.subject_id = chats.subject_id
    WHERE chats.id = tbl_group_chat_reads.group_chat_id
      AND enrolled.student_id = get_current_tbl_user_id() AND enrolled.is_active = true))
  WITH CHECK (has_role('student') AND (user_id = get_current_tbl_user_id()) AND EXISTS (
    SELECT 1 FROM tbl_group_chats chats
      JOIN tbl_enrolled enrolled ON enrolled.educator_id = chats.educator_id AND enrolled.subject_id = chats.subject_id
    WHERE chats.id = tbl_group_chat_reads.group_chat_id
      AND enrolled.student_id = get_current_tbl_user_id() AND enrolled.is_active = true));

-- ---- tbl_learning_materials (NOTE: no admin policy) ----
CREATE POLICY "Educator learning material view access" ON public.tbl_learning_materials
  AS PERMISSIVE FOR SELECT TO authenticated USING (has_role('educator') AND (educator_id = get_current_tbl_user_id()));
CREATE POLICY "Educator learning material create access" ON public.tbl_learning_materials
  AS PERMISSIVE FOR INSERT TO authenticated WITH CHECK (has_role('educator') AND (educator_id = get_current_tbl_user_id()));
CREATE POLICY "Educator learning material update access" ON public.tbl_learning_materials
  AS PERMISSIVE FOR UPDATE TO authenticated
  USING (has_role('educator') AND (educator_id = get_current_tbl_user_id()))
  WITH CHECK (has_role('educator') AND (educator_id = get_current_tbl_user_id()));
CREATE POLICY "Educator learning material delete access" ON public.tbl_learning_materials
  AS PERMISSIVE FOR DELETE TO authenticated USING (has_role('educator') AND (educator_id = get_current_tbl_user_id()));
CREATE POLICY "Student learning material view access" ON public.tbl_learning_materials
  AS PERMISSIVE FOR SELECT TO authenticated
  USING (has_role('student') AND (is_active = true) AND EXISTS (
    SELECT 1 FROM tbl_enrolled enrolled
    WHERE enrolled.student_id = get_current_tbl_user_id()
      AND enrolled.educator_id = tbl_learning_materials.educator_id
      AND enrolled.subject_id = tbl_learning_materials.subject_id AND enrolled.is_active = true));

-- ---- tbl_notifications ----
CREATE POLICY "Admin full access on tbl_notifications" ON public.tbl_notifications
  AS PERMISSIVE FOR ALL TO authenticated USING (has_role('admin')) WITH CHECK (has_role('admin'));
CREATE POLICY "Recipients can view own notifications" ON public.tbl_notifications
  AS PERMISSIVE FOR SELECT TO authenticated USING (recipient_user_id = get_current_tbl_user_id());
CREATE POLICY "Recipients can update own notifications" ON public.tbl_notifications
  AS PERMISSIVE FOR UPDATE TO authenticated
  USING (recipient_user_id = get_current_tbl_user_id())
  WITH CHECK (recipient_user_id = get_current_tbl_user_id());
-- Educator may emit notifications to students they teach (all event types except quiz_submitted):
CREATE POLICY "Educator notification insert access" ON public.tbl_notifications
  AS PERMISSIVE FOR INSERT TO authenticated
  WITH CHECK (has_role('educator') AND (actor_user_id = get_current_tbl_user_id())
    AND (event_type = ANY (ARRAY['assessment_created','assessment_updated','assessment_deleted',
      'learning_material_uploaded','learning_material_deleted','quiz_created','quiz_uploaded',
      'quiz_updated','quiz_deleted','enrollment_created','enrollment_updated','enrollment_deleted','retake_updated']))
    AND EXISTS (SELECT 1 FROM tbl_users student_user
      WHERE student_user.id = tbl_notifications.recipient_user_id
        AND student_user.user_type = 'student' AND student_user.deleted_at IS NULL)
    AND (((event_type = 'enrollment_deleted') AND EXISTS (SELECT 1 FROM tbl_subjects subject_row
            WHERE subject_row.id = tbl_notifications.subject_id AND subject_row.educator_id = get_current_tbl_user_id()))
      OR ((event_type <> 'enrollment_deleted') AND EXISTS (SELECT 1 FROM tbl_enrolled enrolled
            WHERE enrolled.educator_id = get_current_tbl_user_id()
              AND enrolled.student_id = tbl_notifications.recipient_user_id
              AND enrolled.subject_id = tbl_notifications.subject_id))));
-- Student may emit a quiz_submitted notification to the assessment's educator:
CREATE POLICY "Student submission notification insert access" ON public.tbl_notifications
  AS PERMISSIVE FOR INSERT TO authenticated
  WITH CHECK (has_role('student') AND (actor_user_id = get_current_tbl_user_id()) AND (event_type = 'quiz_submitted')
    AND EXISTS (SELECT 1 FROM tbl_assessments assessment_row
        JOIN tbl_enrolled enrolled ON enrolled.educator_id = assessment_row.educator_id AND enrolled.subject_id = assessment_row.subject_id
      WHERE assessment_row.id = tbl_notifications.assessment_id
        AND assessment_row.educator_id = tbl_notifications.recipient_user_id
        AND enrolled.student_id = get_current_tbl_user_id() AND enrolled.is_active = true));

-- ============================ 6. REALTIME PUBLICATION ========================
-- Tables published to supabase_realtime (17). NOTE: tbl_group_chats is NOT
-- published (chat list is fetched via RPC, not subscribed directly).

ALTER PUBLICATION supabase_realtime ADD TABLE public.tbl_academic_term;
ALTER PUBLICATION supabase_realtime ADD TABLE public.tbl_academic_year;
ALTER PUBLICATION supabase_realtime ADD TABLE public.tbl_assessments;
ALTER PUBLICATION supabase_realtime ADD TABLE public.tbl_enrolled;
ALTER PUBLICATION supabase_realtime ADD TABLE public.tbl_group_chat_messages;
ALTER PUBLICATION supabase_realtime ADD TABLE public.tbl_group_chat_reads;
ALTER PUBLICATION supabase_realtime ADD TABLE public.tbl_learning_materials;
ALTER PUBLICATION supabase_realtime ADD TABLE public.tbl_notifications;
ALTER PUBLICATION supabase_realtime ADD TABLE public.tbl_quizzes;
ALTER PUBLICATION supabase_realtime ADD TABLE public.tbl_scores;
ALTER PUBLICATION supabase_realtime ADD TABLE public.tbl_sections;
ALTER PUBLICATION supabase_realtime ADD TABLE public.tbl_sections_term;
ALTER PUBLICATION supabase_realtime ADD TABLE public.tbl_student_assessment_retakes;
ALTER PUBLICATION supabase_realtime ADD TABLE public.tbl_student_presence;
ALTER PUBLICATION supabase_realtime ADD TABLE public.tbl_subjects;
ALTER PUBLICATION supabase_realtime ADD TABLE public.tbl_user_roles;
ALTER PUBLICATION supabase_realtime ADD TABLE public.tbl_users;

-- =============================================================================
-- End of export. Objects: 21 tables, 9 functions, 1 row trigger (+1 event
-- trigger fn), 35 RLS policies, 17 realtime-published tables.
-- =============================================================================
