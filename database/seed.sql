-- EduPulse AI - deterministic hackathon demo data
-- Run after schema.sql. All demo accounts use: EduPulse123!

START TRANSACTION;

SET @demo_password_hash = '$2y$10$RYTKd64X.mfLQA3wkvkw4eft0DEgfO4vga2V7dJEHhMxtMmzBwfPu';

INSERT INTO users (id, email, password_hash, first_name, last_name, role) VALUES
  (1, 'admin@edupulse.demo', @demo_password_hash, 'System', 'Administrator', 'admin'),
  (2, 'maria.reyes@edupulse.demo', @demo_password_hash, 'Maria', 'Reyes', 'teacher'),
  (3, 'alex.santos@edupulse.demo', @demo_password_hash, 'Alex', 'Santos', 'student'),
  (4, 'bea.cruz@edupulse.demo', @demo_password_hash, 'Bea', 'Cruz', 'student'),
  (5, 'carlo.mendoza@edupulse.demo', @demo_password_hash, 'Carlo', 'Mendoza', 'student'),
  (6, 'dana.garcia@edupulse.demo', @demo_password_hash, 'Dana', 'Garcia', 'student'),
  (7, 'ethan.ramos@edupulse.demo', @demo_password_hash, 'Ethan', 'Ramos', 'student'),
  (8, 'faith.lim@edupulse.demo', @demo_password_hash, 'Faith', 'Lim', 'student'),
  (9, 'gabriel.tan@edupulse.demo', @demo_password_hash, 'Gabriel', 'Tan', 'student'),
  (10, 'hannah.villanueva@edupulse.demo', @demo_password_hash, 'Hannah', 'Villanueva', 'student'),
  (11, 'ivan.flores@edupulse.demo', @demo_password_hash, 'Ivan', 'Flores', 'student'),
  (12, 'julia.navarro@edupulse.demo', @demo_password_hash, 'Julia', 'Navarro', 'student');

INSERT INTO teachers (id, user_id, employee_number, department) VALUES
  (1, 2, 'T-2026-001', 'Information Technology');

INSERT INTO students (id, user_id, student_number, grade_level, program, guardian_email) VALUES
  (1, 3, '2026-0001', '2nd Year', 'BS Information Technology', 'guardian.alex@example.com'),
  (2, 4, '2026-0002', '2nd Year', 'BS Information Technology', 'guardian.bea@example.com'),
  (3, 5, '2026-0003', '2nd Year', 'BS Information Technology', 'guardian.carlo@example.com'),
  (4, 6, '2026-0004', '2nd Year', 'BS Information Technology', 'guardian.dana@example.com'),
  (5, 7, '2026-0005', '2nd Year', 'BS Information Technology', 'guardian.ethan@example.com'),
  (6, 8, '2026-0006', '2nd Year', 'BS Information Technology', 'guardian.faith@example.com'),
  (7, 9, '2026-0007', '2nd Year', 'BS Information Technology', 'guardian.gabriel@example.com'),
  (8, 10, '2026-0008', '2nd Year', 'BS Information Technology', 'guardian.hannah@example.com'),
  (9, 11, '2026-0009', '2nd Year', 'BS Information Technology', 'guardian.ivan@example.com'),
  (10, 12, '2026-0010', '2nd Year', 'BS Information Technology', 'guardian.julia@example.com');

INSERT INTO subjects (id, teacher_id, code, title, description, school_year, term) VALUES
  (1, 1, 'IT-DB201', 'Database Management Systems', 'Relational modeling, SQL, normalization, indexing, and transactions.', '2026-2027', '1st Semester'),
  (2, 1, 'IT-WEB202', 'Web Application Development', 'Modern web application foundations using JavaScript and REST APIs.', '2026-2027', '1st Semester');

INSERT INTO enrollments (id, student_id, subject_id, status, enrolled_at) VALUES
  (1, 1, 1, 'active', '2026-08-10 08:00:00'),
  (2, 2, 1, 'active', '2026-08-10 08:01:00'),
  (3, 3, 1, 'active', '2026-08-10 08:02:00'),
  (4, 4, 1, 'active', '2026-08-10 08:03:00'),
  (5, 5, 1, 'active', '2026-08-10 08:04:00'),
  (6, 6, 1, 'active', '2026-08-10 08:05:00'),
  (7, 7, 1, 'active', '2026-08-10 08:06:00'),
  (8, 8, 1, 'active', '2026-08-10 08:07:00'),
  (9, 9, 1, 'active', '2026-08-10 08:08:00'),
  (10, 10, 1, 'active', '2026-08-10 08:09:00'),
  (11, 1, 2, 'active', '2026-08-10 08:10:00'),
  (12, 2, 2, 'active', '2026-08-10 08:11:00'),
  (13, 3, 2, 'active', '2026-08-10 08:12:00'),
  (14, 4, 2, 'active', '2026-08-10 08:13:00'),
  (15, 5, 2, 'active', '2026-08-10 08:14:00'),
  (16, 6, 2, 'active', '2026-08-10 08:15:00'),
  (17, 7, 2, 'active', '2026-08-10 08:16:00'),
  (18, 8, 2, 'active', '2026-08-10 08:17:00'),
  (19, 9, 2, 'active', '2026-08-10 08:18:00'),
  (20, 10, 2, 'active', '2026-08-10 08:19:00');

-- Twenty-five sessions per enrollment. Alex has exactly 68% attendance (17/25).
CREATE TEMPORARY TABLE seed_session_dates (
  session_number TINYINT UNSIGNED PRIMARY KEY,
  session_date DATE NOT NULL
);

INSERT INTO seed_session_dates (session_number, session_date) VALUES
  (1, '2026-08-17'), (2, '2026-08-19'), (3, '2026-08-21'), (4, '2026-08-24'), (5, '2026-08-26'),
  (6, '2026-08-28'), (7, '2026-08-31'), (8, '2026-09-02'), (9, '2026-09-04'), (10, '2026-09-07'),
  (11, '2026-09-09'), (12, '2026-09-11'), (13, '2026-09-14'), (14, '2026-09-16'), (15, '2026-09-18'),
  (16, '2026-09-21'), (17, '2026-09-23'), (18, '2026-09-25'), (19, '2026-09-28'), (20, '2026-09-30'),
  (21, '2026-10-02'), (22, '2026-10-05'), (23, '2026-10-07'), (24, '2026-10-09'), (25, '2026-10-12');

INSERT INTO attendance (enrollment_id, session_date, status)
SELECT
  e.id,
  d.session_date,
  CASE
    WHEN e.student_id = 1 AND d.session_number IN (3, 6, 9, 12, 15, 18, 21, 24) THEN 'absent'
    WHEN e.student_id = 3 AND d.session_number IN (5, 10, 15, 20, 25) THEN 'absent'
    WHEN e.student_id = 5 AND d.session_number IN (3, 6, 9, 12, 15, 18, 21) THEN 'absent'
    WHEN e.student_id = 6 AND d.session_number IN (6, 12, 18, 24) THEN 'absent'
    WHEN e.student_id = 8 AND d.session_number IN (7, 14, 21) THEN 'absent'
    WHEN e.student_id = 10 AND d.session_number IN (4, 8, 12, 16, 20, 24) THEN 'absent'
    WHEN d.session_number IN (8, 19) AND e.student_id IN (1, 3, 5, 6, 8, 10) THEN 'late'
    WHEN d.session_number = 13 AND e.student_id IN (2, 4, 7, 9) THEN 'absent'
    ELSE 'present'
  END
FROM enrollments e
CROSS JOIN seed_session_dates d;

DROP TEMPORARY TABLE seed_session_dates;

INSERT INTO assignments (id, subject_id, title, topic, instructions, max_score, due_at) VALUES
  (1, 1, 'Entity Relationship Diagram', 'Data Modeling', 'Model a small university enrollment system.', 100, '2026-08-28 23:59:00'),
  (2, 1, 'SQL Query Laboratory', 'SQL Joins', 'Complete the provided join and aggregation problems.', 100, '2026-09-07 23:59:00'),
  (3, 1, 'Normalization Worksheet', 'Database Normalization', 'Normalize the sample relations through 3NF.', 100, '2026-09-18 23:59:00'),
  (4, 1, 'Indexing Case Study', 'Database Indexing', 'Recommend indexes for the sample workload.', 100, '2026-09-29 23:59:00'),
  (5, 2, 'Responsive Profile Page', 'HTML and CSS', 'Build a responsive student profile page.', 100, '2026-08-30 23:59:00'),
  (6, 2, 'JavaScript Data Explorer', 'JavaScript Fundamentals', 'Render and filter a local JSON dataset.', 100, '2026-09-09 23:59:00'),
  (7, 2, 'REST Client Exercise', 'REST APIs', 'Consume a REST endpoint with error states.', 100, '2026-09-20 23:59:00'),
  (8, 2, 'Authentication UI', 'Web Security', 'Create accessible login and registration screens.', 100, '2026-10-01 23:59:00');

INSERT INTO submissions (assignment_id, student_id, submitted_at, score, status, feedback)
SELECT
  a.id,
  s.id,
  CASE
    WHEN s.id = 1 AND a.id IN (3, 7) THEN NULL
    WHEN s.id = 1 AND a.id IN (1, 2, 5, 6) THEN DATE_ADD(a.due_at, INTERVAL 18 HOUR)
    WHEN s.id = 5 AND a.id IN (3, 4, 7) THEN NULL
    WHEN s.id = 10 AND a.id IN (3, 7) THEN NULL
    WHEN MOD(s.id + a.id, 7) = 0 THEN DATE_ADD(a.due_at, INTERVAL 6 HOUR)
    ELSE DATE_SUB(a.due_at, INTERVAL (12 + s.id) HOUR)
  END,
  CASE
    WHEN s.id = 1 AND a.id IN (3, 7) THEN NULL
    WHEN s.id = 5 AND a.id IN (3, 4, 7) THEN NULL
    WHEN s.id = 10 AND a.id IN (3, 7) THEN NULL
    WHEN s.id = 1 THEN CASE a.id WHEN 1 THEN 74 WHEN 2 THEN 70 WHEN 4 THEN 69 WHEN 5 THEN 76 WHEN 6 THEN 72 WHEN 8 THEN 65 ELSE 71 END
    WHEN s.id = 2 THEN 91 + MOD(a.id, 7)
    WHEN s.id = 3 THEN 76 + MOD(a.id * 3, 10)
    WHEN s.id = 4 THEN 88 + MOD(a.id, 9)
    WHEN s.id = 5 THEN 58 + MOD(a.id * 2, 12)
    WHEN s.id = 6 THEN 73 + MOD(a.id * 4, 13)
    WHEN s.id = 7 THEN 90 + MOD(a.id, 8)
    WHEN s.id = 8 THEN 78 + MOD(a.id * 2, 11)
    WHEN s.id = 9 THEN 86 + MOD(a.id, 10)
    ELSE 64 + MOD(a.id * 3, 13)
  END,
  CASE
    WHEN s.id = 1 AND a.id IN (3, 7) THEN 'missing'
    WHEN s.id = 5 AND a.id IN (3, 4, 7) THEN 'missing'
    WHEN s.id = 10 AND a.id IN (3, 7) THEN 'missing'
    WHEN s.id = 1 AND a.id IN (1, 2, 5, 6) THEN 'late'
    WHEN MOD(s.id + a.id, 7) = 0 THEN 'late'
    ELSE 'graded'
  END,
  CASE
    WHEN s.id IN (1, 5, 10) THEN 'Review the topic and use the practice material before the follow-up activity.'
    ELSE 'Good progress. Continue applying the concepts in the next activity.'
  END
FROM assignments a
CROSS JOIN students s;

INSERT INTO quiz_results (student_id, subject_id, topic, score, max_score, source, taken_at) VALUES
  (1, 1, 'Data Modeling', 67, 100, 'teacher', '2026-08-25 10:00:00'),
  (1, 1, 'SQL Joins', 62, 100, 'teacher', '2026-09-08 10:00:00'),
  (1, 1, 'Database Normalization', 48, 100, 'teacher', '2026-09-24 10:00:00'),
  (2, 1, 'Data Modeling', 94, 100, 'teacher', '2026-08-25 10:04:00'),
  (2, 1, 'SQL Joins', 96, 100, 'teacher', '2026-09-08 10:04:00'),
  (2, 1, 'Database Normalization', 95, 100, 'teacher', '2026-09-24 10:04:00'),
  (3, 1, 'Data Modeling', 82, 100, 'teacher', '2026-08-25 10:08:00'),
  (3, 1, 'SQL Joins', 78, 100, 'teacher', '2026-09-08 10:08:00'),
  (3, 1, 'Database Normalization', 72, 100, 'teacher', '2026-09-24 10:08:00'),
  (4, 1, 'Data Modeling', 91, 100, 'teacher', '2026-08-25 10:12:00'),
  (4, 1, 'SQL Joins', 90, 100, 'teacher', '2026-09-08 10:12:00'),
  (4, 1, 'Database Normalization', 93, 100, 'teacher', '2026-09-24 10:12:00'),
  (5, 1, 'Data Modeling', 71, 100, 'teacher', '2026-08-25 10:16:00'),
  (5, 1, 'SQL Joins', 63, 100, 'teacher', '2026-09-08 10:16:00'),
  (5, 1, 'Database Normalization', 55, 100, 'teacher', '2026-09-24 10:16:00'),
  (6, 1, 'Data Modeling', 80, 100, 'teacher', '2026-08-25 10:20:00'),
  (6, 1, 'SQL Joins', 76, 100, 'teacher', '2026-09-08 10:20:00'),
  (6, 1, 'Database Normalization', 73, 100, 'teacher', '2026-09-24 10:20:00'),
  (7, 1, 'Data Modeling', 92, 100, 'teacher', '2026-08-25 10:24:00'),
  (7, 1, 'SQL Joins', 94, 100, 'teacher', '2026-09-08 10:24:00'),
  (7, 1, 'Database Normalization', 91, 100, 'teacher', '2026-09-24 10:24:00'),
  (8, 1, 'Data Modeling', 84, 100, 'teacher', '2026-08-25 10:28:00'),
  (8, 1, 'SQL Joins', 79, 100, 'teacher', '2026-09-08 10:28:00'),
  (8, 1, 'Database Normalization', 75, 100, 'teacher', '2026-09-24 10:28:00'),
  (9, 1, 'Data Modeling', 90, 100, 'teacher', '2026-08-25 10:32:00'),
  (9, 1, 'SQL Joins', 88, 100, 'teacher', '2026-09-08 10:32:00'),
  (9, 1, 'Database Normalization', 90, 100, 'teacher', '2026-09-24 10:32:00'),
  (10, 1, 'Data Modeling', 73, 100, 'teacher', '2026-08-25 10:36:00'),
  (10, 1, 'SQL Joins', 66, 100, 'teacher', '2026-09-08 10:36:00'),
  (10, 1, 'Database Normalization', 60, 100, 'teacher', '2026-09-24 10:36:00'),
  (1, 2, 'JavaScript Fundamentals', 64, 100, 'teacher', '2026-09-10 13:00:00'),
  (1, 2, 'REST APIs', 58, 100, 'teacher', '2026-09-26 13:00:00'),
  (2, 2, 'JavaScript Fundamentals', 96, 100, 'teacher', '2026-09-10 13:04:00'),
  (2, 2, 'REST APIs', 94, 100, 'teacher', '2026-09-26 13:04:00'),
  (3, 2, 'JavaScript Fundamentals', 81, 100, 'teacher', '2026-09-10 13:08:00'),
  (3, 2, 'REST APIs', 77, 100, 'teacher', '2026-09-26 13:08:00'),
  (4, 2, 'JavaScript Fundamentals', 92, 100, 'teacher', '2026-09-10 13:12:00'),
  (4, 2, 'REST APIs', 91, 100, 'teacher', '2026-09-26 13:12:00'),
  (5, 2, 'JavaScript Fundamentals', 68, 100, 'teacher', '2026-09-10 13:16:00'),
  (5, 2, 'REST APIs', 59, 100, 'teacher', '2026-09-26 13:16:00'),
  (6, 2, 'JavaScript Fundamentals', 79, 100, 'teacher', '2026-09-10 13:20:00'),
  (6, 2, 'REST APIs', 76, 100, 'teacher', '2026-09-26 13:20:00'),
  (7, 2, 'JavaScript Fundamentals', 94, 100, 'teacher', '2026-09-10 13:24:00'),
  (7, 2, 'REST APIs', 92, 100, 'teacher', '2026-09-26 13:24:00'),
  (8, 2, 'JavaScript Fundamentals', 83, 100, 'teacher', '2026-09-10 13:28:00'),
  (8, 2, 'REST APIs', 80, 100, 'teacher', '2026-09-26 13:28:00'),
  (9, 2, 'JavaScript Fundamentals', 89, 100, 'teacher', '2026-09-10 13:32:00'),
  (9, 2, 'REST APIs', 91, 100, 'teacher', '2026-09-26 13:32:00'),
  (10, 2, 'JavaScript Fundamentals', 70, 100, 'teacher', '2026-09-10 13:36:00'),
  (10, 2, 'REST APIs', 62, 100, 'teacher', '2026-09-26 13:36:00');

INSERT INTO modules (id, subject_id, teacher_id, title, original_filename, stored_filename, file_path, file_size_bytes, processing_status) VALUES
  (1, 1, 1, 'Module 3: Database Normalization', 'module-3-normalization.pdf', 'demo-module-3-normalization.pdf', 'uploads/demo-module-3-normalization.pdf', 248000, 'ready'),
  (2, 2, 1, 'Module 4: Building REST APIs', 'module-4-rest-apis.pdf', 'demo-module-4-rest-apis.pdf', 'uploads/demo-module-4-rest-apis.pdf', 195000, 'ready');

INSERT INTO module_chunks (id, module_id, chunk_index, page_number, content, token_count) VALUES
  (1, 1, 0, 2, 'Database normalization organizes relational data to reduce duplication and prevent update anomalies. First normal form requires atomic values and removes repeating groups.', 29),
  (2, 1, 1, 3, 'Second normal form builds on first normal form. Every non-key attribute must depend on the whole candidate key, which removes partial dependencies from tables with composite keys.', 31),
  (3, 1, 2, 4, 'Third normal form removes transitive dependencies. Non-key attributes should depend on the key, the whole key, and nothing but the key.', 25),
  (4, 2, 0, 2, 'A REST API models resources with predictable URLs and standard HTTP methods. GET reads, POST creates, PUT or PATCH updates, and DELETE removes a resource.', 28),
  (5, 2, 1, 3, 'API responses should use meaningful HTTP status codes and a consistent JSON error structure. Validate input before applying business logic.', 22);

-- Current performance snapshots for the database subject drive the teacher demo.
INSERT INTO student_performance
  (id, student_id, subject_id, snapshot_date, attendance_rate, quiz_average, assignment_average, late_submissions, missing_submissions, activity_score, performance_trend)
VALUES
  (1, 1, 1, '2026-09-30', 68, 59, 71, 4, 2, 42, -12),
  (2, 2, 1, '2026-09-30', 96, 95, 94, 0, 0, 93, 4),
  (3, 3, 1, '2026-09-30', 80, 77, 81, 1, 0, 72, -4),
  (4, 4, 1, '2026-09-30', 96, 91, 92, 0, 0, 89, 2),
  (5, 5, 1, '2026-09-30', 72, 63, 62, 2, 3, 48, -10),
  (6, 6, 1, '2026-09-30', 84, 76, 79, 1, 0, 69, -3),
  (7, 7, 1, '2026-09-30', 96, 92, 93, 0, 0, 91, 3),
  (8, 8, 1, '2026-09-30', 88, 79, 83, 1, 0, 75, -2),
  (9, 9, 1, '2026-09-30', 96, 89, 90, 0, 0, 87, 1),
  (10, 10, 1, '2026-09-30', 76, 66, 68, 2, 2, 53, -8);

INSERT INTO student_support_analysis
  (id, performance_id, support_level, confidence, weak_topics, ai_summary, model_version, analysis_source, analyzed_at)
VALUES
  (1, 1, 'HIGH', 0.8700, JSON_ARRAY('Database Normalization', 'SQL Joins'), 'Quiz scores and attendance have declined. Database Normalization is the strongest current weakness, so Alex would benefit from a short, structured review and follow-up assessment.', 'random-forest-demo-v1', 'ml_ai', '2026-09-30 15:00:00'),
  (2, 2, 'LOW', 0.9600, JSON_ARRAY(), 'Bea is performing consistently across attendance, assessments, and course activity.', 'random-forest-demo-v1', 'ml_ai', '2026-09-30 15:01:00'),
  (3, 3, 'MODERATE', 0.7300, JSON_ARRAY('Database Normalization'), 'Carlo is generally progressing but recent assessment scores suggest that normalization concepts need reinforcement.', 'random-forest-demo-v1', 'ml_ai', '2026-09-30 15:02:00'),
  (4, 4, 'LOW', 0.9400, JSON_ARRAY(), 'Dana demonstrates strong and stable academic performance.', 'random-forest-demo-v1', 'ml_ai', '2026-09-30 15:03:00'),
  (5, 5, 'HIGH', 0.9100, JSON_ARRAY('Database Normalization', 'SQL Joins'), 'Ethan has multiple missing submissions and a declining score trend. Prioritize assignment recovery and guided practice.', 'random-forest-demo-v1', 'ml_ai', '2026-09-30 15:04:00'),
  (6, 6, 'MODERATE', 0.7100, JSON_ARRAY('Database Normalization'), 'Faith is close to expectations but would benefit from targeted practice on normalization.', 'random-forest-demo-v1', 'ml_ai', '2026-09-30 15:05:00'),
  (7, 7, 'LOW', 0.9500, JSON_ARRAY(), 'Gabriel is performing strongly with consistent participation.', 'random-forest-demo-v1', 'ml_ai', '2026-09-30 15:06:00'),
  (8, 8, 'MODERATE', 0.6800, JSON_ARRAY('Database Normalization'), 'Hannah is progressing steadily; a brief normalization review can prevent a wider gap.', 'random-forest-demo-v1', 'ml_ai', '2026-09-30 15:07:00'),
  (9, 9, 'LOW', 0.9300, JSON_ARRAY(), 'Ivan is meeting course expectations across all current indicators.', 'random-forest-demo-v1', 'ml_ai', '2026-09-30 15:08:00'),
  (10, 10, 'HIGH', 0.8200, JSON_ARRAY('Database Normalization', 'SQL Joins'), 'Julia has declining scores and two missing tasks. A focused recovery plan is recommended.', 'random-forest-demo-v1', 'ml_ai', '2026-09-30 15:09:00');

INSERT INTO recommendations (support_analysis_id, recommendation_text, priority, status, created_by) VALUES
  (1, 'Review first, second, and third normal forms using Module 3.', 'high', 'in_progress', 'ai'),
  (1, 'Complete five normalization practice exercises and review each mistake.', 'high', 'pending', 'ai'),
  (1, 'Meet the instructor for a short consultation before the follow-up quiz.', 'medium', 'pending', 'teacher'),
  (3, 'Complete the normalization dependency-mapping exercise.', 'medium', 'pending', 'ai'),
  (5, 'Submit the missing normalization and indexing activities.', 'high', 'pending', 'teacher'),
  (5, 'Attend a guided SQL joins practice session.', 'high', 'pending', 'ai'),
  (6, 'Review 2NF and 3NF examples, then answer three practice questions.', 'medium', 'pending', 'ai'),
  (8, 'Revisit transitive dependencies before the next assessment.', 'medium', 'pending', 'ai'),
  (10, 'Complete missing work and take a short diagnostic quiz.', 'high', 'pending', 'ai');

INSERT INTO study_plans (id, student_id, subject_id, support_analysis_id, title, start_date, end_date, status, generated_by) VALUES
  (1, 1, 1, 1, '7-Day Database Normalization Recovery Plan', '2026-10-01', '2026-10-07', 'active', 'ai'),
  (2, 5, 1, 5, '7-Day SQL and Submission Recovery Plan', '2026-10-01', '2026-10-07', 'active', 'ai'),
  (3, 10, 1, 10, '7-Day Database Foundations Review', '2026-10-01', '2026-10-07', 'active', 'ai');

INSERT INTO study_plan_items (study_plan_id, day_number, scheduled_date, topic, task) VALUES
  (1, 1, '2026-10-01', '1NF and 2NF', 'Read the relevant Module 3 sections and write one example of each normal form.'),
  (1, 2, '2026-10-02', '3NF', 'Study transitive dependencies and normalize two sample relations to 3NF.'),
  (1, 3, '2026-10-03', 'Practice', 'Complete five normalization exercises.'),
  (1, 4, '2026-10-04', 'Error Review', 'Review mistakes from the exercises and ask the AI Tutor about unclear steps.'),
  (1, 5, '2026-10-05', 'Practice Quiz', 'Take the generated normalization quiz.'),
  (1, 6, '2026-10-06', 'Targeted Review', 'Revisit the lowest-scoring quiz topic and create a one-page summary.'),
  (1, 7, '2026-10-07', 'Assessment and Reflection', 'Complete the follow-up assessment and record what improved.'),
  (2, 1, '2026-10-01', 'Missing Work', 'List and prioritize all missing database activities.'),
  (2, 2, '2026-10-02', 'SQL Joins', 'Review INNER and LEFT JOIN examples.'),
  (2, 3, '2026-10-03', 'Practice', 'Complete five guided SQL queries.'),
  (2, 4, '2026-10-04', 'Normalization', 'Review functional dependencies and 2NF.'),
  (2, 5, '2026-10-05', 'Assignment Recovery', 'Finish the normalization worksheet.'),
  (2, 6, '2026-10-06', 'Practice Quiz', 'Take a short SQL and normalization quiz.'),
  (2, 7, '2026-10-07', 'Reflection', 'Review results with the instructor.'),
  (3, 1, '2026-10-01', 'Database Foundations', 'Review keys and entity relationships.'),
  (3, 2, '2026-10-02', 'SQL Joins', 'Practice join selection with three examples.'),
  (3, 3, '2026-10-03', '1NF', 'Identify repeating groups in sample tables.'),
  (3, 4, '2026-10-04', '2NF', 'Remove two partial dependencies.'),
  (3, 5, '2026-10-05', '3NF', 'Remove two transitive dependencies.'),
  (3, 6, '2026-10-06', 'Practice Quiz', 'Take the database foundations quiz.'),
  (3, 7, '2026-10-07', 'Follow-up', 'Review incorrect answers and meet the instructor if needed.');

INSERT INTO chat_conversations (id, student_id, subject_id, title, created_at, updated_at) VALUES
  (1, 1, 1, 'Understanding database normalization', '2026-09-30 17:00:00', '2026-09-30 17:02:00');

INSERT INTO chat_messages (conversation_id, role, content, retrieved_context, created_at) VALUES
  (1, 'user', 'What is database normalization?', NULL, '2026-09-30 17:00:00'),
  (1, 'assistant', 'Based on Module 3, database normalization is a way to organize relational data so that repeated information is reduced and updates remain consistent. A useful first step is to ensure every field contains one atomic value, which is the core requirement of first normal form.', JSON_ARRAY(JSON_OBJECT('module_id', 1, 'chunk_id', 1, 'page', 2)), '2026-09-30 17:00:03');

INSERT INTO generated_quizzes (id, subject_id, module_id, created_by_user_id, title, topic, difficulty, question_count, is_published) VALUES
  (1, 1, 1, 2, 'Normalization Checkpoint', 'Database Normalization', 'medium', 3, TRUE);

INSERT INTO generated_quiz_questions
  (id, quiz_id, position, question_type, question_text, choices, correct_answer, explanation, source_chunk_id)
VALUES
  (1, 1, 1, 'multiple_choice', 'What is the main purpose of database normalization?', JSON_ARRAY('Increase data duplication', 'Reduce redundancy and update anomalies', 'Remove all keys', 'Combine every table'), 'Reduce redundancy and update anomalies', 'Normalization organizes data to reduce duplication and preserve consistency.', 1),
  (2, 1, 2, 'true_false', 'A table in first normal form may contain repeating groups.', JSON_ARRAY('True', 'False'), 'False', 'First normal form requires atomic values and removes repeating groups.', 1),
  (3, 1, 3, 'short_answer', 'What kind of dependency does third normal form remove?', NULL, 'Transitive dependency', 'Third normal form removes dependencies between non-key attributes.', 3);

INSERT INTO quiz_attempts
  (id, quiz_id, student_id, score, max_score, strong_topics, weak_topics, recommended_review, started_at, completed_at)
VALUES
  (1, 1, 1, 1, 3, JSON_ARRAY('First Normal Form'), JSON_ARRAY('Third Normal Form', 'Functional Dependencies'), 'Review the 3NF section of Module 3 and retry the dependency exercise.', '2026-09-30 18:00:00', '2026-09-30 18:08:00');

INSERT INTO quiz_attempt_answers (attempt_id, question_id, answer_text, is_correct, feedback) VALUES
  (1, 1, 'Reduce redundancy and update anomalies', TRUE, 'Correct. This is the central goal of normalization.'),
  (1, 2, 'True', FALSE, 'First normal form removes repeating groups and requires atomic values.'),
  (1, 3, 'Partial dependency', FALSE, 'Third normal form targets transitive dependencies; second normal form targets partial dependencies.');

COMMIT;
