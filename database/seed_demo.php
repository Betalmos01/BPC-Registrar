<?php

function seed_demo_data(PDO $pdo): array
{
    $counts = [
        'students' => 0,
        'instructors' => 0,
        'classes' => 0,
        'schedules' => 0,
        'enrollments' => 0,
        'grades' => 0,
        'documents' => 0,
        'reports' => 0,
        'academic_reports' => 0,
        'notifications' => 0,
    ];

    $pdo->beginTransaction();
    try {
        $hasStudents = (int)$pdo->query('SELECT COUNT(*) FROM students')->fetchColumn() > 0;
        $hasInstructors = (int)$pdo->query('SELECT COUNT(*) FROM instructors')->fetchColumn() > 0;
        $hasClasses = (int)$pdo->query('SELECT COUNT(*) FROM classes')->fetchColumn() > 0;

        if (!$hasStudents) {
            $students = [];
            $programs = ['BSIT', 'BSCS', 'BSBA', 'BSED', 'BEED', 'BSECE'];
            $statuses = ['Active', 'Active', 'Active', 'On Hold', 'Inactive'];
            for ($i = 1; $i <= 24; $i++) {
                $studentNo = '2026-' . str_pad((string)$i, 4, '0', STR_PAD_LEFT);
                $program = $programs[$i % count($programs)];
                $year = (string)(1 + ($i % 4));
                $status = $statuses[$i % count($statuses)];
                $students[] = [
                    'student_no' => $studentNo,
                    'first_name' => 'Student' . $i,
                    'last_name' => 'Record' . $i,
                    'program' => $program,
                    'year_level' => $year,
                    'status' => $status,
                ];
            }

            $stmt = $pdo->prepare('INSERT INTO students (student_no, first_name, last_name, program, year_level, status, created_at) VALUES (:student_no, :first_name, :last_name, :program, :year_level, :status, NOW())');
            foreach ($students as $row) {
                $stmt->execute($row);
                $counts['students']++;
            }
        }

        if (!$hasInstructors) {
            $instructors = [
                ['EMP-1001', 'Alyssa', 'Santos', 'Computer Studies'],
                ['EMP-1002', 'Marco', 'Reyes', 'Business'],
                ['EMP-1003', 'Janine', 'Dela Cruz', 'Education'],
                ['EMP-1004', 'Paolo', 'Garcia', 'Engineering'],
                ['EMP-1005', 'Bea', 'Lim', 'Computer Studies'],
                ['EMP-1006', 'Carlo', 'Navarro', 'Business'],
            ];

            $stmt = $pdo->prepare('INSERT INTO instructors (employee_no, first_name, last_name, department, created_at) VALUES (:employee_no, :first_name, :last_name, :department, NOW())');
            foreach ($instructors as $row) {
                $stmt->execute([
                    'employee_no' => $row[0],
                    'first_name' => $row[1],
                    'last_name' => $row[2],
                    'department' => $row[3],
                ]);
                $counts['instructors']++;
            }
        }

        if (!$hasClasses) {
            $catalog = [
                ['IT 101', 'Introduction to Computing', 'BSIT', 3],
                ['IT 102', 'Programming Fundamentals', 'BSIT', 3],
                ['IT 201', 'Data Structures', 'BSIT', 3],
                ['IT 202', 'Database Systems', 'BSIT', 3],
                ['CS 101', 'Discrete Mathematics', 'BSCS', 3],
                ['CS 201', 'Algorithms', 'BSCS', 3],
                ['BA 101', 'Principles of Management', 'BSBA', 3],
                ['ED 101', 'Foundations of Education', 'BSED', 3],
            ];
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            $times = [
                '7:00 AM - 9:00 AM',
                '9:00 AM - 11:00 AM',
                '11:00 AM - 1:00 PM',
                '1:00 PM - 3:00 PM',
                '3:00 PM - 5:00 PM',
            ];
            $rooms = ['Room 101', 'Room 102', 'Room 201', 'Room 202', 'Lab 1', 'Lab 2'];

            $classStmt = $pdo->prepare('INSERT INTO classes (class_code, title, course, units, created_at) VALUES (:class_code, :title, :course, :units, NOW())');
            $schedStmt = $pdo->prepare('INSERT INTO schedules (class_id, day, time, room, created_at) VALUES (:class_id, :day, :time, :room, NOW())');

            foreach ($catalog as $index => $row) {
                $classStmt->execute([
                    'class_code' => $row[0],
                    'title' => $row[1],
                    'course' => $row[2],
                    'units' => (int)$row[3],
                ]);
                $classId = (int)$pdo->lastInsertId();
                $counts['classes']++;

                $schedStmt->execute([
                    'class_id' => $classId,
                    'day' => $days[$index % count($days)],
                    'time' => $times[$index % count($times)],
                    'room' => $rooms[$index % count($rooms)],
                ]);
                $counts['schedules']++;
            }
        }

        // Seed enrollments and grades if both students and classes exist and enrollments are empty.
        $hasEnrollments = (int)$pdo->query('SELECT COUNT(*) FROM enrollments')->fetchColumn() > 0;
        if (!$hasEnrollments) {
            $studentIds = $pdo->query('SELECT id FROM students ORDER BY id ASC LIMIT 18')->fetchAll(PDO::FETCH_COLUMN);
            $classIds = $pdo->query('SELECT id FROM classes ORDER BY id ASC')->fetchAll(PDO::FETCH_COLUMN);

            if ($studentIds && $classIds) {
                $enrollStmt = $pdo->prepare('INSERT INTO enrollments (student_id, class_id, status, created_at) VALUES (:student_id, :class_id, :status, NOW())');
                foreach ($studentIds as $i => $studentId) {
                    $classId = (int)$classIds[$i % count($classIds)];
                    $status = ($i % 6 === 0) ? 'Pending' : 'Enrolled';
                    $enrollStmt->execute([
                        'student_id' => (int)$studentId,
                        'class_id' => $classId,
                        'status' => $status,
                    ]);
                    $counts['enrollments']++;
                }
            }
        }

        $hasGrades = (int)$pdo->query('SELECT COUNT(*) FROM grades')->fetchColumn() > 0;
        if (!$hasGrades) {
            $pairs = $pdo->query("SELECT student_id, class_id FROM enrollments WHERE status = 'Enrolled' ORDER BY created_at DESC LIMIT 10")->fetchAll();
            if ($pairs) {
                $gradesStmt = $pdo->prepare('INSERT INTO grades (student_id, class_id, grade, remarks, created_at) VALUES (:student_id, :class_id, :grade, :remarks, NOW())');
                $gradeVals = ['1.00', '1.25', '1.50', '1.75', '2.00', '2.25'];
                foreach ($pairs as $index => $pair) {
                    $gradesStmt->execute([
                        'student_id' => (int)$pair['student_id'],
                        'class_id' => (int)$pair['class_id'],
                        'grade' => $gradeVals[$index % count($gradeVals)],
                        'remarks' => 'Passed',
                    ]);
                    $counts['grades']++;
                }
            }
        }

        $hasDocuments = (int)$pdo->query('SELECT COUNT(*) FROM documents')->fetchColumn() > 0;
        if (!$hasDocuments) {
            $studentIds = $pdo->query('SELECT id FROM students ORDER BY id ASC LIMIT 8')->fetchAll(PDO::FETCH_COLUMN);
            $docTypes = ['Transcript of Records', 'Certificate of Enrollment', 'Good Moral Certificate', 'Certification'];
            $docStmt = $pdo->prepare('INSERT INTO documents (student_id, doc_type, status, requested_at, completed_at) VALUES (:student_id, :doc_type, :status, NOW(), :completed_at)');
            foreach ($studentIds as $i => $studentId) {
                $status = ($i % 4 === 0) ? 'Completed' : (($i % 3 === 0) ? 'Processing' : 'Pending');
                $docStmt->execute([
                    'student_id' => (int)$studentId,
                    'doc_type' => $docTypes[$i % count($docTypes)],
                    'status' => $status,
                    'completed_at' => $status === 'Completed' ? date('Y-m-d H:i:s') : null,
                ]);
                $counts['documents']++;
            }
        }

        $hasReports = (int)$pdo->query('SELECT COUNT(*) FROM reports')->fetchColumn() > 0;
        if (!$hasReports) {
            $rows = [
                ['Monthly Enrollment Summary', 'Registrar Office', 'In Review', date('Y-m-d', strtotime('+7 days'))],
                ['Document Release SLA', 'Registrar Office', 'Pending', date('Y-m-d', strtotime('+14 days'))],
                ['Grade Posting Compliance', 'Academic Affairs', 'Completed', null],
            ];
            $stmt = $pdo->prepare('INSERT INTO reports (title, department, status, due_date, created_at) VALUES (:title, :department, :status, :due_date, NOW())');
            foreach ($rows as $row) {
                $stmt->execute([
                    'title' => $row[0],
                    'department' => $row[1],
                    'status' => $row[2],
                    'due_date' => $row[3],
                ]);
                $counts['reports']++;
            }
        }

        $hasAcademicReports = (int)$pdo->query('SELECT COUNT(*) FROM academic_reports')->fetchColumn() > 0;
        if (!$hasAcademicReports) {
            $rows = [
                ['AY 2025-2026 Midyear Grade Release', 'AY 2025-2026', 'In Review'],
                ['AY 2025-2026 Completion List', 'AY 2025-2026', 'Draft'],
            ];
            $stmt = $pdo->prepare('INSERT INTO academic_reports (title, coverage, status, created_at) VALUES (:title, :coverage, :status, NOW())');
            foreach ($rows as $row) {
                $stmt->execute([
                    'title' => $row[0],
                    'coverage' => $row[1],
                    'status' => $row[2],
                ]);
                $counts['academic_reports']++;
            }
        }

        $hasNotifications = (int)$pdo->query('SELECT COUNT(*) FROM notifications')->fetchColumn() > 0;
        if (!$hasNotifications) {
            $rows = [
                ['Pending Document Requests', 'Review and process pending document requests in the queue.', 'Unread'],
                ['Enrollment Validation', 'Some enrollments are still marked as Pending. Please review.', 'Unread'],
                ['Report Deadline', 'A report is due soon. Check the compliance queue.', 'Unread'],
            ];
            $stmt = $pdo->prepare('INSERT INTO notifications (title, message, status, created_at) VALUES (:title, :message, :status, NOW())');
            foreach ($rows as $row) {
                $stmt->execute([
                    'title' => $row[0],
                    'message' => $row[1],
                    'status' => $row[2],
                ]);
                $counts['notifications']++;
            }
        }

        $pdo->commit();
        return $counts;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

