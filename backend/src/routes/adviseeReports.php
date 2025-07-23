<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Advisee reports routes
$app->group('/api/advisee-reports', function ($group) {

    // Get comprehensive advisee reports for an advisor
    $group->get('/comprehensive', function (Request $request, Response $response) {
        // Apply JWT authentication manually
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader)) {
            $response->getBody()->write(json_encode(['error' => 'Authorization token required']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];

            try {
                $jwtSecret = $_ENV['JWT_SECRET'] ?? 'your_jwt_secret_key_here';
                $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($jwtSecret, 'HS256'));

                // Add user info to request attributes
                $user = $decoded;
            } catch (\Firebase\JWT\ExpiredException $e) {
                $response->getBody()->write(json_encode(['error' => 'Token expired']));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            } catch (\Exception $e) {
                $response->getBody()->write(json_encode(['error' => 'Invalid token']));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            }
        } else {
            $response->getBody()->write(json_encode(['error' => 'Invalid authorization header format']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $pdo = $this->get('pdo');

        // Debug: Let's see what user data we have
        error_log('User attribute in advisee reports: ' . print_r($user, true));

        if (!$user) {
            $response->getBody()->write(json_encode(['error' => 'User not authenticated', 'debug' => 'No user attribute found']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        if ($user->role !== 'advisor') {
            $response->getBody()->write(json_encode(['error' => 'Advisor access required', 'current_role' => $user->role ?? 'undefined']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        try {
            $advisorId = $user->id;

            // Debug: Log the advisor ID and user info
            error_log('Debug - Advisor ID: ' . $advisorId);
            error_log('Debug - User object: ' . print_r($user, true));

            // First, let's check if we can find advisees at all
            $stmt = $pdo->prepare('
                SELECT u.id, u.name, u.email, u.advisor_id 
                FROM users u 
                WHERE u.role = "student" AND u.advisor_id = ?
            ');
            $stmt->execute([$advisorId]);
            $basicAdvisees = $stmt->fetchAll();

            error_log('Debug - Basic advisees count: ' . count($basicAdvisees));

            // If we have basic advisees, continue with the complex query
            if (count($basicAdvisees) > 0) {

                // Get all advisees with their comprehensive academic information
                $stmt = $pdo->prepare('
                SELECT 
                    u.id,
                    u.name,
                    u.email,
                    u.matric_number,
                    u.created_at as enrollment_date,
                    COUNT(DISTINCT e.course_id) as total_courses,
                    COUNT(DISTINCT fm.id) as completed_courses,
                    AVG(fm.gpa) as overall_gpa,
                    AVG(fm.assignment_mark) as avg_assignment_mark,
                    AVG(fm.quiz_mark) as avg_quiz_mark,
                    AVG(fm.test_mark) as avg_test_mark,
                    AVG(fm.final_exam_mark) as avg_final_exam_mark,
                    COUNT(CASE WHEN fm.letter_grade IN ("A+", "A", "A-") THEN 1 END) as a_grades,
                    COUNT(CASE WHEN fm.letter_grade IN ("B+", "B", "B-") THEN 1 END) as b_grades,
                    COUNT(CASE WHEN fm.letter_grade IN ("C+", "C", "C-") THEN 1 END) as c_grades,
                    COUNT(CASE WHEN fm.letter_grade IN ("D+", "D") THEN 1 END) as d_grades,
                    COUNT(CASE WHEN fm.letter_grade = "F" THEN 1 END) as f_grades
                FROM users u
                LEFT JOIN enrollments e ON u.id = e.student_id
                LEFT JOIN final_marks_custom fm ON u.id = fm.student_id AND e.course_id = fm.course_id
                WHERE u.advisor_id = ? AND u.role = "student"
                GROUP BY u.id
                ORDER BY u.name
            ');
                $stmt->execute([$advisorId]);
                $advisees = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Calculate performance trends and risk indicators for each advisee
                foreach ($advisees as &$advisee) {
                    // Get course details for trend calculation
                    $stmt = $pdo->prepare('
                    SELECT 
                        fm.final_grade as overall_percentage,
                        fm.created_at
                    FROM final_marks_custom fm
                    WHERE fm.student_id = ? AND fm.final_grade IS NOT NULL
                    ORDER BY fm.created_at
                ');
                    $stmt->execute([$advisee['id']]);
                    $coursePerformances = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // Calculate performance trend
                    $advisee['performance_trend'] = calculatePerformanceTrend($coursePerformances);

                    // Identify at-risk indicators
                    $advisee['risk_indicators'] = identifyRiskIndicators($advisee);

                    // Get improvement suggestions
                    $advisee['suggestions'] = generateSuggestions($advisee);
                }

                // Calculate summary statistics
                $totalAdvisees = count($advisees);
                $avgGpa = $totalAdvisees > 0 ? array_sum(array_column($advisees, 'overall_gpa')) / $totalAdvisees : 0;
                $atRiskCount = count(array_filter($advisees, function ($a) {
                    return ($a['overall_gpa'] ?? 0) < 2.0;
                }));

                $response->getBody()->write(json_encode([
                    'success' => true,
                    'data' => [
                        'advisees' => $advisees,
                        'summary' => [
                            'total_advisees' => $totalAdvisees,
                            'avg_gpa' => round($avgGpa, 2),
                            'at_risk_count' => $atRiskCount,
                            'excellent_performers' => count(array_filter($advisees, function ($a) {
                                return ($a['overall_gpa'] ?? 0) >= 3.5;
                            }))
                        ]
                    ]
                ]));
            } else {
                // No advisees found
                $response->getBody()->write(json_encode([
                    'success' => true,
                    'data' => [
                        'advisees' => [],
                        'summary' => [
                            'total_advisees' => 0,
                            'avg_gpa' => 0,
                            'at_risk_count' => 0,
                            'excellent_performers' => 0
                        ]
                    ]
                ]));
            }

            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            error_log('Advisee reports error: ' . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Failed to generate reports']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Get individual advisee detailed report
    $group->get('/individual/{studentId}', function (Request $request, Response $response, array $args) {
        // Apply JWT authentication manually
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader)) {
            $response->getBody()->write(json_encode(['error' => 'Authorization token required']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];

            try {
                $jwtSecret = $_ENV['JWT_SECRET'] ?? 'your_jwt_secret_key_here';
                $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($jwtSecret, 'HS256'));
                $user = $decoded;
            } catch (\Firebase\JWT\ExpiredException $e) {
                $response->getBody()->write(json_encode(['error' => 'Token expired']));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            } catch (\Exception $e) {
                $response->getBody()->write(json_encode(['error' => 'Invalid token']));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            }
        } else {
            $response->getBody()->write(json_encode(['error' => 'Invalid authorization header format']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $pdo = $this->get('pdo');

        if (!$user || $user->role !== 'advisor') {
            $response->getBody()->write(json_encode(['error' => 'Advisor access required']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        try {
            $studentId = $args['studentId'];
            $advisorId = $user->id;

            // Verify the student is under this advisor
            $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ? AND advisor_id = ?');
            $stmt->execute([$studentId, $advisorId]);
            if (!$stmt->fetch()) {
                $response->getBody()->write(json_encode(['error' => 'Student not under your advisory']));
                return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
            }

            // Get comprehensive student information
            $stmt = $pdo->prepare('
                SELECT 
                    u.id,
                    u.name,
                    u.email,
                    u.matric_number,
                    u.created_at as enrollment_date,
                    AVG(fm.gpa) as overall_gpa,
                    COUNT(DISTINCT e.course_id) as total_courses,
                    COUNT(DISTINCT fm.id) as completed_courses
                FROM users u
                LEFT JOIN enrollments e ON u.id = e.student_id
                LEFT JOIN final_marks_custom fm ON u.id = fm.student_id
                WHERE u.id = ?
                GROUP BY u.id
            ');
            $stmt->execute([$studentId]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$student) {
                $response->getBody()->write(json_encode(['error' => 'Student not found']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // Get detailed course performance
            $stmt = $pdo->prepare('
                SELECT 
                    c.id,
                    c.code,
                    c.name,
                    c.lecturer_id,
                    c.semester,
                    c.academic_year,
                    fm.assignment_mark,
                    fm.assignment_percentage,
                    fm.quiz_mark,
                    fm.quiz_percentage,
                    fm.test_mark,
                    fm.test_percentage,
                    fm.final_exam_mark,
                    fm.final_exam_percentage,
                    fm.component_total,
                    fm.final_grade as overall_percentage,
                    fm.letter_grade,
                    fm.gpa,
                    fm.created_at as completion_date
                FROM enrollments e
                JOIN courses c ON e.course_id = c.id
                LEFT JOIN final_marks_custom fm ON e.student_id = fm.student_id AND e.course_id = fm.course_id
                WHERE e.student_id = ?
                ORDER BY fm.created_at DESC, c.code
            ');
            $stmt->execute([$studentId]);
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get advisor notes if table exists
            $notes = [];
            try {
                $stmt = $pdo->prepare('
                    SELECT content, created_at 
                    FROM advisor_notes 
                    WHERE student_id = ? AND advisor_id = ?
                    ORDER BY created_at DESC
                ');
                $stmt->execute([$studentId, $advisorId]);
                $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                // Table might not exist, continue without notes
                $notes = [];
            }

            // Calculate performance analytics
            $analytics = [
                'grade_distribution' => calculateGradeDistribution($courses),
                'component_strengths' => analyzeComponentStrengths($courses),
                'performance_trend' => calculateDetailedTrend($courses),
                'recommendations' => generateDetailedRecommendations($student, $courses),
                'risk_indicators' => calculateRiskIndicators($student, $courses)
            ];

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => [
                    'student' => $student,
                    'courses' => $courses,
                    'notes' => $notes,
                    'analytics' => $analytics,
                    'risk_indicators' => calculateRiskIndicators($student, $courses)
                ]
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            error_log('Individual advisee report error: ' . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Failed to generate individual report']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Export advisee reports to CSV
    $group->get('/export/csv', function (Request $request, Response $response) {
        // Apply JWT authentication manually
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader)) {
            $response->getBody()->write(json_encode(['error' => 'Authorization token required']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];

            try {
                $jwtSecret = $_ENV['JWT_SECRET'] ?? 'your_jwt_secret_key_here';
                $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($jwtSecret, 'HS256'));
                $user = $decoded;
            } catch (\Firebase\JWT\ExpiredException $e) {
                $response->getBody()->write(json_encode(['error' => 'Token expired']));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            } catch (\Exception $e) {
                $response->getBody()->write(json_encode(['error' => 'Invalid token']));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            }
        } else {
            $response->getBody()->write(json_encode(['error' => 'Invalid authorization header format']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $pdo = $this->get('pdo');

        if (!$user || $user->role !== 'advisor') {
            $response->getBody()->write(json_encode(['error' => 'Advisor access required']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        try {
            $advisorId = $user->id;

            // Get advisees data for CSV export
            $stmt = $pdo->prepare('
                SELECT 
                    u.name as "Student Name",
                    u.matric_number as "Matric Number",
                    u.email as "Email",
                    COUNT(DISTINCT e.course_id) as "Total Courses",
                    COUNT(DISTINCT fm.id) as "Completed Courses",
                    ROUND(AVG(fm.gpa), 2) as "Overall GPA",
                    ROUND(AVG(fm.final_grade), 2) as "Average Percentage",
                    COUNT(CASE WHEN fm.letter_grade IN ("A+", "A", "A-") THEN 1 END) as "A Grades",
                    COUNT(CASE WHEN fm.letter_grade IN ("B+", "B", "B-") THEN 1 END) as "B Grades",
                    COUNT(CASE WHEN fm.letter_grade IN ("C+", "C", "C-") THEN 1 END) as "C Grades",
                    COUNT(CASE WHEN fm.letter_grade IN ("D+", "D") THEN 1 END) as "D Grades",
                    COUNT(CASE WHEN fm.letter_grade = "F" THEN 1 END) as "F Grades",
                    CASE 
                        WHEN AVG(fm.gpa) >= 3.5 THEN "Excellent"
                        WHEN AVG(fm.gpa) >= 3.0 THEN "Good"
                        WHEN AVG(fm.gpa) >= 2.5 THEN "Satisfactory"
                        WHEN AVG(fm.gpa) >= 2.0 THEN "Needs Improvement"
                        ELSE "At Risk"
                    END as "Performance Category"
                FROM users u
                LEFT JOIN enrollments e ON u.id = e.student_id
                LEFT JOIN final_marks_custom fm ON u.id = fm.student_id AND e.course_id = fm.course_id
                WHERE u.advisor_id = ? AND u.role = "student"
                GROUP BY u.id
                ORDER BY u.name
            ');
            $stmt->execute([$advisorId]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Generate CSV content
            $csvContent = '';
            if (!empty($data)) {
                // Add headers
                $csvContent .= implode(',', array_keys($data[0])) . "\n";

                // Add data rows
                foreach ($data as $row) {
                    $csvContent .= implode(',', array_map(function ($value) {
                        return '"' . str_replace('"', '""', $value ?? '') . '"';
                    }, array_values($row))) . "\n";
                }
            }

            $filename = 'advisee_reports_' . date('Y-m-d_H-i-s') . '.csv';

            $response = $response
                ->withHeader('Content-Type', 'text/csv')
                ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

            $response->getBody()->write($csvContent);
            return $response;
        } catch (Exception $e) {
            error_log('CSV export error: ' . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Failed to export CSV']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });
});

// Helper functions
function calculatePerformanceTrend($coursePerformances)
{
    if (count($coursePerformances) < 2) {
        return 'insufficient_data';
    }

    $performances = array_column($coursePerformances, 'overall_percentage');
    $recentHalf = array_slice($performances, -ceil(count($performances) / 2));
    $earlierHalf = array_slice($performances, 0, floor(count($performances) / 2));

    $recentAvg = array_sum($recentHalf) / count($recentHalf);
    $earlierAvg = array_sum($earlierHalf) / count($earlierHalf);

    $difference = $recentAvg - $earlierAvg;

    if ($difference > 5) return 'improving';
    if ($difference < -5) return 'declining';
    return 'stable';
}

function identifyRiskIndicators($advisee)
{
    $indicators = [];

    if (($advisee['overall_gpa'] ?? 0) < 2.0) {
        $indicators[] = 'low_gpa';
    }

    if (($advisee['f_grades'] ?? 0) > 0) {
        $indicators[] = 'failing_grades';
    }

    // Use raw marks with 60% threshold (consistent with individual reports)
    if (($advisee['avg_assignment_mark'] ?? 0) < 60) {
        $indicators[] = 'poor_assignment_performance';
    }

    if (($advisee['avg_quiz_mark'] ?? 0) < 60) {
        $indicators[] = 'poor_quiz_performance';
    }

    // Check for insufficient completed courses (less than 3)
    if (($advisee['completed_courses'] ?? 0) < 3) {
        $indicators[] = 'insufficient_completed_courses';
    }

    return $indicators;
}

function generateSuggestions($advisee)
{
    $suggestions = [];
    $riskIndicators = $advisee['risk_indicators'] ?? [];

    if (in_array('low_gpa', $riskIndicators)) {
        $suggestions[] = 'Schedule regular academic support meetings';
        $suggestions[] = 'Consider course load reduction';
    }

    if (in_array('poor_assignment_performance', $riskIndicators)) {
        $suggestions[] = 'Provide time management guidance';
        $suggestions[] = 'Connect with writing center resources';
    }

    if (in_array('poor_quiz_performance', $riskIndicators)) {
        $suggestions[] = 'Recommend study group participation';
        $suggestions[] = 'Suggest active learning techniques';
    }

    if (empty($suggestions)) {
        $suggestions[] = 'Continue current academic progress';
        $suggestions[] = 'Consider advanced course opportunities';
    }

    return $suggestions;
}

function calculateGradeDistribution($courses)
{
    $distribution = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'F' => 0];

    foreach ($courses as $course) {
        if ($course['letter_grade']) {
            $gradeCategory = substr($course['letter_grade'], 0, 1);
            if (isset($distribution[$gradeCategory])) {
                $distribution[$gradeCategory]++;
            }
        }
    }

    return $distribution;
}

function analyzeComponentStrengths($courses)
{
    $components = ['assignment', 'quiz', 'test', 'final_exam'];
    $strengths = [];

    foreach ($components as $component) {
        $marks = [];
        foreach ($courses as $course) {
            $mark = $course[$component . '_mark'];
            if ($mark !== null && $mark > 0) {
                $marks[] = (float)$mark;
            }
        }

        if (!empty($marks)) {
            $avg = array_sum($marks) / count($marks);
            $strengths[$component] = round($avg, 2);
        }
    }

    return $strengths;
}

function calculateDetailedTrend($courses)
{
    $gradedCourses = array_filter($courses, function ($course) {
        return $course['overall_percentage'] !== null;
    });

    usort($gradedCourses, function ($a, $b) {
        return strtotime($a['completion_date'] ?? 0) - strtotime($b['completion_date'] ?? 0);
    });

    $trend = [];
    foreach ($gradedCourses as $course) {
        $trend[] = [
            'course' => $course['code'],
            'percentage' => $course['overall_percentage'],
            'grade' => $course['letter_grade'],
            'date' => $course['completion_date']
        ];
    }

    return $trend;
}

function generateDetailedRecommendations($student, $courses)
{
    $recommendations = [];

    $gpa = $student['overall_gpa'] ?? 0;
    $completedCourses = count(array_filter($courses, function ($c) {
        return $c['overall_percentage'] !== null;
    }));

    // GPA-based recommendations
    if ($gpa >= 3.5) {
        $recommendations[] = [
            'category' => 'Academic Excellence',
            'text' => 'Encourage advanced coursework and research opportunities',
            'priority' => 'low'
        ];
    } elseif ($gpa >= 3.0) {
        $recommendations[] = [
            'category' => 'Academic Progress',
            'text' => 'Maintain current study habits and consider challenging electives',
            'priority' => 'medium'
        ];
    } elseif ($gpa >= 2.0) {
        $recommendations[] = [
            'category' => 'Academic Support',
            'text' => 'Implement structured study schedule and seek tutoring support',
            'priority' => 'high'
        ];
    } else {
        $recommendations[] = [
            'category' => 'Academic Intervention',
            'text' => 'Immediate academic intervention required - consider course load reduction',
            'priority' => 'urgent'
        ];
    }

    // Component-specific recommendations
    $components = analyzeComponentStrengths($courses);

    foreach ($components as $component => $avg) {
        if ($avg < 60) {
            $recommendations[] = [
                'category' => 'Component Improvement',
                'text' => "Focus on improving {$component} performance (current: {$avg}%)",
                'priority' => 'medium'
            ];
        }
    }

    return $recommendations;
}

function calculateRiskIndicators($student, $courses)
{
    $riskIndicators = [];

    $gpa = floatval($student['overall_gpa'] ?? 0);
    $completedCourses = count(array_filter($courses, function ($c) {
        return $c['overall_percentage'] !== null;
    }));

    // GPA-based risk indicators
    if ($gpa < 2.0) {
        $riskIndicators[] = 'low_gpa';
    }

    // Check for failing grades
    $failingGrades = count(array_filter($courses, function ($c) {
        return $c['letter_grade'] === 'F';
    }));

    if ($failingGrades > 0) {
        $riskIndicators[] = 'failing_grades';
    }

    // Check component performance using raw marks instead of weighted percentages
    if ($completedCourses > 0) {
        $avgAssignment = array_sum(array_column($courses, 'assignment_mark')) / $completedCourses;
        $avgQuiz = array_sum(array_column($courses, 'quiz_mark')) / $completedCourses;

        if ($avgAssignment < 60) {
            $riskIndicators[] = 'poor_assignment_performance';
        }

        if ($avgQuiz < 60) {
            $riskIndicators[] = 'poor_quiz_performance';
        }
    }

    // Check completion rate - only flag if very low number of completed courses
    // This is more realistic than checking against total enrollments
    if ($completedCourses < 3) {
        $riskIndicators[] = 'insufficient_completed_courses';
    }

    return $riskIndicators;
}
