-- EduPulse AI - MySQL 8 schema
-- WARNING: this script recreates the development database.

DROP DATABASE IF EXISTS edupulse_ai;
CREATE DATABASE edupulse_ai
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE edupulse_ai;

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(191) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  first_name VARCHAR(80) NOT NULL,
  last_name VARCHAR(80) NOT NULL,
  role ENUM('student', 'teacher', 'admin') NOT NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  last_login_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uq_users_email UNIQUE (email),
  INDEX idx_users_role_active (role, is_active)
) ENGINE=InnoDB;

CREATE TABLE teachers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  employee_number VARCHAR(40) NOT NULL,
  department VARCHAR(120) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uq_teachers_user UNIQUE (user_id),
  CONSTRAINT uq_teachers_employee_number UNIQUE (employee_number),
  CONSTRAINT fk_teachers_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE students (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  student_number VARCHAR(40) NOT NULL,
  grade_level VARCHAR(40) NOT NULL,
  program VARCHAR(120) NULL,
  guardian_email VARCHAR(191) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uq_students_user UNIQUE (user_id),
  CONSTRAINT uq_students_student_number UNIQUE (student_number),
  CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_students_grade_program (grade_level, program)
) ENGINE=InnoDB;

CREATE TABLE subjects (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  teacher_id BIGINT UNSIGNED NOT NULL,
  code VARCHAR(30) NOT NULL,
  title VARCHAR(160) NOT NULL,
  description TEXT NULL,
  school_year VARCHAR(20) NOT NULL,
  term VARCHAR(30) NOT NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uq_subjects_offering UNIQUE (code, school_year, term),
  CONSTRAINT fk_subjects_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE RESTRICT,
  INDEX idx_subjects_teacher_active (teacher_id, is_active)
) ENGINE=InnoDB;

CREATE TABLE enrollments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id BIGINT UNSIGNED NOT NULL,
  subject_id BIGINT UNSIGNED NOT NULL,
  status ENUM('active', 'completed', 'dropped') NOT NULL DEFAULT 'active',
  enrolled_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uq_enrollments_student_subject UNIQUE (student_id, subject_id),
  CONSTRAINT fk_enrollments_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT fk_enrollments_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
  INDEX idx_enrollments_subject_status (subject_id, status)
) ENGINE=InnoDB;

CREATE TABLE attendance (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  enrollment_id BIGINT UNSIGNED NOT NULL,
  session_date DATE NOT NULL,
  status ENUM('present', 'late', 'absent', 'excused') NOT NULL,
  notes VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uq_attendance_session UNIQUE (enrollment_id, session_date),
  CONSTRAINT fk_attendance_enrollment FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
  INDEX idx_attendance_date_status (session_date, status)
) ENGINE=InnoDB;

CREATE TABLE assignments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  subject_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(180) NOT NULL,
  topic VARCHAR(160) NOT NULL,
  instructions TEXT NULL,
  max_score DECIMAL(7,2) NOT NULL DEFAULT 100.00,
  due_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT chk_assignments_max_score CHECK (max_score > 0),
  CONSTRAINT fk_assignments_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
  INDEX idx_assignments_subject_due (subject_id, due_at)
) ENGINE=InnoDB;

CREATE TABLE submissions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  assignment_id BIGINT UNSIGNED NOT NULL,
  student_id BIGINT UNSIGNED NOT NULL,
  submitted_at DATETIME NULL,
  score DECIMAL(7,2) NULL,
  status ENUM('submitted', 'late', 'missing', 'graded') NOT NULL,
  feedback TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uq_submissions_assignment_student UNIQUE (assignment_id, student_id),
  CONSTRAINT chk_submissions_score CHECK (score IS NULL OR score >= 0),
  CONSTRAINT fk_submissions_assignment FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
  CONSTRAINT fk_submissions_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  INDEX idx_submissions_student_status (student_id, status)
) ENGINE=InnoDB;

CREATE TABLE quiz_results (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id BIGINT UNSIGNED NOT NULL,
  subject_id BIGINT UNSIGNED NOT NULL,
  topic VARCHAR(160) NOT NULL,
  score DECIMAL(7,2) NOT NULL,
  max_score DECIMAL(7,2) NOT NULL,
  source ENUM('teacher', 'ai_generated') NOT NULL DEFAULT 'teacher',
  taken_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT chk_quiz_results_scores CHECK (max_score > 0 AND score >= 0 AND score <= max_score),
  CONSTRAINT fk_quiz_results_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT fk_quiz_results_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
  INDEX idx_quiz_results_student_taken (student_id, taken_at),
  INDEX idx_quiz_results_subject_topic (subject_id, topic)
) ENGINE=InnoDB;

CREATE TABLE modules (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  subject_id BIGINT UNSIGNED NOT NULL,
  teacher_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(180) NOT NULL,
  original_filename VARCHAR(255) NOT NULL,
  stored_filename VARCHAR(255) NOT NULL,
  file_path VARCHAR(500) NOT NULL,
  mime_type VARCHAR(100) NOT NULL DEFAULT 'application/pdf',
  file_size_bytes BIGINT UNSIGNED NOT NULL,
  processing_status ENUM('pending', 'processing', 'ready', 'failed') NOT NULL DEFAULT 'pending',
  processing_error TEXT NULL,
  uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uq_modules_stored_filename UNIQUE (stored_filename),
  CONSTRAINT chk_modules_pdf CHECK (mime_type = 'application/pdf'),
  CONSTRAINT fk_modules_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
  CONSTRAINT fk_modules_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE RESTRICT,
  INDEX idx_modules_subject_status (subject_id, processing_status)
) ENGINE=InnoDB;

CREATE TABLE module_chunks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  module_id BIGINT UNSIGNED NOT NULL,
  chunk_index INT UNSIGNED NOT NULL,
  page_number INT UNSIGNED NULL,
  content MEDIUMTEXT NOT NULL,
  token_count INT UNSIGNED NULL,
  embedding JSON NULL COMMENT 'Replace with a vector store/ChromaDB reference when the RAG service is upgraded.',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uq_module_chunks_position UNIQUE (module_id, chunk_index),
  CONSTRAINT fk_module_chunks_module FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
  FULLTEXT INDEX ftx_module_chunks_content (content)
) ENGINE=InnoDB;

CREATE TABLE student_performance (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id BIGINT UNSIGNED NOT NULL,
  subject_id BIGINT UNSIGNED NOT NULL,
  snapshot_date DATE NOT NULL,
  attendance_rate DECIMAL(5,2) NOT NULL,
  quiz_average DECIMAL(5,2) NOT NULL,
  assignment_average DECIMAL(5,2) NOT NULL,
  late_submissions INT UNSIGNED NOT NULL DEFAULT 0,
  missing_submissions INT UNSIGNED NOT NULL DEFAULT 0,
  activity_score DECIMAL(5,2) NOT NULL,
  performance_trend DECIMAL(6,2) NOT NULL DEFAULT 0 COMMENT 'Percentage-point change from the prior comparison period.',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uq_student_performance_snapshot UNIQUE (student_id, subject_id, snapshot_date),
  CONSTRAINT chk_student_performance_rates CHECK (
    attendance_rate BETWEEN 0 AND 100 AND
    quiz_average BETWEEN 0 AND 100 AND
    assignment_average BETWEEN 0 AND 100 AND
    activity_score BETWEEN 0 AND 100
  ),
  CONSTRAINT fk_student_performance_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT fk_student_performance_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
  INDEX idx_student_performance_subject_date (subject_id, snapshot_date),
  INDEX idx_student_performance_student_date (student_id, snapshot_date)
) ENGINE=InnoDB;

CREATE TABLE student_support_analysis (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  performance_id BIGINT UNSIGNED NOT NULL,
  support_level ENUM('LOW', 'MODERATE', 'HIGH') NOT NULL,
  confidence DECIMAL(5,4) NULL,
  weak_topics JSON NOT NULL,
  ai_summary TEXT NULL,
  model_version VARCHAR(80) NULL,
  analysis_source ENUM('ml_ai', 'ml_only', 'rules_fallback') NOT NULL DEFAULT 'ml_ai',
  analyzed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uq_support_analysis_performance UNIQUE (performance_id),
  CONSTRAINT chk_support_analysis_confidence CHECK (confidence IS NULL OR confidence BETWEEN 0 AND 1),
  CONSTRAINT fk_support_analysis_performance FOREIGN KEY (performance_id) REFERENCES student_performance(id) ON DELETE CASCADE,
  INDEX idx_support_analysis_level_date (support_level, analyzed_at)
) ENGINE=InnoDB;

CREATE TABLE recommendations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  support_analysis_id BIGINT UNSIGNED NOT NULL,
  recommendation_text VARCHAR(500) NOT NULL,
  priority ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
  status ENUM('pending', 'in_progress', 'completed', 'dismissed') NOT NULL DEFAULT 'pending',
  created_by ENUM('ai', 'teacher') NOT NULL DEFAULT 'ai',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_recommendations_analysis FOREIGN KEY (support_analysis_id) REFERENCES student_support_analysis(id) ON DELETE CASCADE,
  INDEX idx_recommendations_analysis_status (support_analysis_id, status)
) ENGINE=InnoDB;

CREATE TABLE study_plans (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id BIGINT UNSIGNED NOT NULL,
  subject_id BIGINT UNSIGNED NOT NULL,
  support_analysis_id BIGINT UNSIGNED NULL,
  title VARCHAR(180) NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  status ENUM('draft', 'active', 'completed', 'archived') NOT NULL DEFAULT 'draft',
  generated_by ENUM('ai', 'teacher') NOT NULL DEFAULT 'ai',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT chk_study_plans_dates CHECK (end_date >= start_date),
  CONSTRAINT fk_study_plans_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT fk_study_plans_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
  CONSTRAINT fk_study_plans_analysis FOREIGN KEY (support_analysis_id) REFERENCES student_support_analysis(id) ON DELETE SET NULL,
  INDEX idx_study_plans_student_status (student_id, status)
) ENGINE=InnoDB;

CREATE TABLE study_plan_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  study_plan_id BIGINT UNSIGNED NOT NULL,
  day_number TINYINT UNSIGNED NOT NULL,
  scheduled_date DATE NOT NULL,
  topic VARCHAR(160) NOT NULL,
  task TEXT NOT NULL,
  is_completed BOOLEAN NOT NULL DEFAULT FALSE,
  completed_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uq_study_plan_items_day UNIQUE (study_plan_id, day_number),
  CONSTRAINT chk_study_plan_items_day CHECK (day_number BETWEEN 1 AND 31),
  CONSTRAINT fk_study_plan_items_plan FOREIGN KEY (study_plan_id) REFERENCES study_plans(id) ON DELETE CASCADE,
  INDEX idx_study_plan_items_schedule (scheduled_date, is_completed)
) ENGINE=InnoDB;

CREATE TABLE chat_conversations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id BIGINT UNSIGNED NOT NULL,
  subject_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(180) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_chat_conversations_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT fk_chat_conversations_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
  INDEX idx_chat_conversations_student_updated (student_id, updated_at)
) ENGINE=InnoDB;

CREATE TABLE chat_messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  conversation_id BIGINT UNSIGNED NOT NULL,
  role ENUM('user', 'assistant', 'system') NOT NULL,
  content TEXT NOT NULL,
  retrieved_context JSON NULL COMMENT 'Module/chunk references actually used for grounded answers.',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_chat_messages_conversation FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE,
  INDEX idx_chat_messages_conversation_created (conversation_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE generated_quizzes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  subject_id BIGINT UNSIGNED NOT NULL,
  module_id BIGINT UNSIGNED NULL,
  created_by_user_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(180) NOT NULL,
  topic VARCHAR(160) NOT NULL,
  difficulty ENUM('easy', 'medium', 'hard') NOT NULL DEFAULT 'medium',
  question_count TINYINT UNSIGNED NOT NULL,
  is_published BOOLEAN NOT NULL DEFAULT FALSE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT chk_generated_quizzes_count CHECK (question_count BETWEEN 1 AND 50),
  CONSTRAINT fk_generated_quizzes_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
  CONSTRAINT fk_generated_quizzes_module FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE SET NULL,
  CONSTRAINT fk_generated_quizzes_creator FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE RESTRICT,
  INDEX idx_generated_quizzes_subject_published (subject_id, is_published)
) ENGINE=InnoDB;

CREATE TABLE generated_quiz_questions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  quiz_id BIGINT UNSIGNED NOT NULL,
  position TINYINT UNSIGNED NOT NULL,
  question_type ENUM('multiple_choice', 'true_false', 'short_answer') NOT NULL,
  question_text TEXT NOT NULL,
  choices JSON NULL,
  correct_answer TEXT NOT NULL,
  explanation TEXT NOT NULL,
  source_chunk_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uq_quiz_questions_position UNIQUE (quiz_id, position),
  CONSTRAINT fk_quiz_questions_quiz FOREIGN KEY (quiz_id) REFERENCES generated_quizzes(id) ON DELETE CASCADE,
  CONSTRAINT fk_quiz_questions_chunk FOREIGN KEY (source_chunk_id) REFERENCES module_chunks(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE quiz_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  quiz_id BIGINT UNSIGNED NOT NULL,
  student_id BIGINT UNSIGNED NOT NULL,
  score DECIMAL(7,2) NULL,
  max_score DECIMAL(7,2) NULL,
  strong_topics JSON NULL,
  weak_topics JSON NULL,
  recommended_review TEXT NULL,
  started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  completed_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT chk_quiz_attempts_score CHECK (
    (score IS NULL AND max_score IS NULL) OR
    (score >= 0 AND max_score > 0 AND score <= max_score)
  ),
  CONSTRAINT fk_quiz_attempts_quiz FOREIGN KEY (quiz_id) REFERENCES generated_quizzes(id) ON DELETE CASCADE,
  CONSTRAINT fk_quiz_attempts_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  INDEX idx_quiz_attempts_student_completed (student_id, completed_at)
) ENGINE=InnoDB;

CREATE TABLE quiz_attempt_answers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  attempt_id BIGINT UNSIGNED NOT NULL,
  question_id BIGINT UNSIGNED NOT NULL,
  answer_text TEXT NULL,
  is_correct BOOLEAN NULL,
  feedback TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uq_quiz_attempt_answers_question UNIQUE (attempt_id, question_id),
  CONSTRAINT fk_quiz_attempt_answers_attempt FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(id) ON DELETE CASCADE,
  CONSTRAINT fk_quiz_attempt_answers_question FOREIGN KEY (question_id) REFERENCES generated_quiz_questions(id) ON DELETE CASCADE
) ENGINE=InnoDB;
