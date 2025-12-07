<?php
/**
 * Attendance System API
 * 
 * RESTful API for managing sections, students, and attendance records
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Include database configuration
$conn = include 'db_config.php';

// Get action from request
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Route to appropriate handler
switch ($action) {
    case 'get_data':
        getData($conn);
        break;
    
    case 'create_section':
        createSection($conn);
        break;
    
    case 'update_section':
        updateSection($conn);
        break;
    
    case 'delete_section':
        deleteSection($conn);
        break;
    
    case 'add_student':
        addStudent($conn);
        break;
    
    case 'remove_student':
        removeStudent($conn);
        break;
    
    case 'update_student_status':
        updateStudentStatus($conn);
        break;
    
    case 'save_session':
        saveSession($conn);
        break;
    
    case 'update_history':
        updateHistory($conn);
        break;
    
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}

$conn->close();

// ================================================
// API Functions
// ================================================

/**
 * Get all data (sections, students, history)
 */
function getData($conn) {
    $data = [
        'success' => true,
        'sections' => []
    ];
    
    // Get all sections
    $sectionsQuery = "SELECT * FROM sections ORDER BY created_at DESC";
    $sectionsResult = $conn->query($sectionsQuery);
    
    if ($sectionsResult) {
        while ($section = $sectionsResult->fetch_assoc()) {
            $sectionId = $section['id'];
            
            // Get students for this section
            $studentsQuery = "SELECT * FROM students WHERE section_id = ? ORDER BY name ASC";
            $stmt = $conn->prepare($studentsQuery);
            $stmt->bind_param('s', $sectionId);
            $stmt->execute();
            $studentsResult = $stmt->get_result();
            $students = [];
            
            while ($student = $studentsResult->fetch_assoc()) {
                $students[] = [
                    'id' => $student['id'],
                    'name' => $student['name'],
                    'status' => $student['status']
                ];
            }
            $stmt->close();
            
            // Get attendance history for this section
            $historyQuery = "SELECT * FROM attendance_sessions WHERE section_id = ? ORDER BY timestamp DESC";
            $stmt = $conn->prepare($historyQuery);
            $stmt->bind_param('s', $sectionId);
            $stmt->execute();
            $historyResult = $stmt->get_result();
            $history = [];
            
            while ($session = $historyResult->fetch_assoc()) {
                $sessionId = $session['id'];
                $sessionName = $session['session_name'];
                
                // Get attendance records for this session
                $recordsQuery = "SELECT student_id, status FROM attendance_records WHERE session_id = ?";
                $stmt2 = $conn->prepare($recordsQuery);
                $stmt2->bind_param('i', $sessionId);
                $stmt2->execute();
                $recordsResult = $stmt2->get_result();
                $records = [];
                
                while ($record = $recordsResult->fetch_assoc()) {
                    $records[$record['student_id']] = $record['status'];
                }
                $stmt2->close();
                
                $history[$sessionName] = [
                    'data' => $records,
                    'modCount' => (int)$session['mod_count'],
                    'timestamp' => (int)$session['timestamp']
                ];
            }
            $stmt->close();
            
            // Add section to data
            $data['sections'][] = [
                'id' => $section['id'],
                'name' => $section['name'],
                'subject' => $section['subject'],
                'students' => $students,
                'history' => $history,
                'isLocked' => (bool)$section['is_locked'],
                'lastSavedDate' => $section['last_saved_date'],
                'createdAt' => (int)$section['created_at']
            ];
        }
    }
    
    echo json_encode($data);
}

/**
 * Create a new section
 */
function createSection($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = $input['id'];
    $name = $input['name'];
    $subject = $input['subject'] ?? '';
    $createdAt = $input['createdAt'];
    
    $stmt = $conn->prepare("INSERT INTO sections (id, name, subject, is_locked, created_at) VALUES (?, ?, ?, 0, ?)");
    $stmt->bind_param('sssi', $id, $name, $subject, $createdAt);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    
    $stmt->close();
}

/**
 * Update section properties
 */
function updateSection($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = $input['id'];
    $isLocked = $input['isLocked'] ?? null;
    $lastSavedDate = $input['lastSavedDate'] ?? null;
    
    if ($isLocked !== null) {
        $stmt = $conn->prepare("UPDATE sections SET is_locked = ? WHERE id = ?");
        $stmt->bind_param('is', $isLocked, $id);
        $stmt->execute();
        $stmt->close();
    }
    
    if ($lastSavedDate !== null) {
        $stmt = $conn->prepare("UPDATE sections SET last_saved_date = ? WHERE id = ?");
        $stmt->bind_param('ss', $lastSavedDate, $id);
        $stmt->execute();
        $stmt->close();
    }
    
    echo json_encode(['success' => true]);
}

/**
 * Delete a section and all related data
 */
function deleteSection($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'];
    
    $stmt = $conn->prepare("DELETE FROM sections WHERE id = ?");
    $stmt->bind_param('s', $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    
    $stmt->close();
}

/**
 * Add a student to a section
 */
function addStudent($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = $input['id'];
    $sectionId = $input['sectionId'];
    $name = $input['name'];
    $createdAt = time() * 1000;
    
    $stmt = $conn->prepare("INSERT INTO students (id, section_id, name, status, created_at) VALUES (?, ?, ?, NULL, ?)");
    $stmt->bind_param('sssi', $id, $sectionId, $name, $createdAt);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    
    $stmt->close();
}

/**
 * Remove a student from a section
 */
function removeStudent($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'];
    
    $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    $stmt->bind_param('s', $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    
    $stmt->close();
}

/**
 * Update student attendance status
 */
function updateStudentStatus($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = $input['id'];
    $status = $input['status'];
    
    $stmt = $conn->prepare("UPDATE students SET status = ? WHERE id = ?");
    $stmt->bind_param('ss', $status, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    
    $stmt->close();
}

/**
 * Save an attendance session to history
 */
function saveSession($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $sectionId = $input['sectionId'];
    $sessionName = $input['sessionName'];
    $timestamp = $input['timestamp'];
    $records = $input['records'];
    
    // Insert session
    $stmt = $conn->prepare("INSERT INTO attendance_sessions (section_id, session_name, timestamp, mod_count) VALUES (?, ?, ?, 0)");
    $stmt->bind_param('ssi', $sectionId, $sessionName, $timestamp);
    
    if ($stmt->execute()) {
        $sessionId = $stmt->insert_id;
        $stmt->close();
        
        // Insert attendance records
        if (!empty($records)) {
            $stmt = $conn->prepare("INSERT INTO attendance_records (session_id, student_id, status) VALUES (?, ?, ?)");
            
            foreach ($records as $studentId => $status) {
                $stmt->bind_param('iss', $sessionId, $studentId, $status);
                $stmt->execute();
            }
            
            $stmt->close();
        }
        
        // Reset student statuses to NULL
        $stmt = $conn->prepare("UPDATE students SET status = NULL WHERE section_id = ?");
        $stmt->bind_param('s', $sectionId);
        $stmt->execute();
        $stmt->close();
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
        $stmt->close();
    }
}

/**
 * Update a historical attendance record
 */
function updateHistory($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $sectionId = $input['sectionId'];
    $sessionName = $input['sessionName'];
    $records = $input['records'];
    $modCount = $input['modCount'];
    
    // Get session ID
    $stmt = $conn->prepare("SELECT id FROM attendance_sessions WHERE section_id = ? AND session_name = ?");
    $stmt->bind_param('ss', $sectionId, $sessionName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $sessionId = $row['id'];
        $stmt->close();
        
        // Delete existing records
        $stmt = $conn->prepare("DELETE FROM attendance_records WHERE session_id = ?");
        $stmt->bind_param('i', $sessionId);
        $stmt->execute();
        $stmt->close();
        
        // Insert updated records
        if (!empty($records)) {
            $stmt = $conn->prepare("INSERT INTO attendance_records (session_id, student_id, status) VALUES (?, ?, ?)");
            
            foreach ($records as $studentId => $status) {
                if ($status !== null) {
                    $stmt->bind_param('iss', $sessionId, $studentId, $status);
                    $stmt->execute();
                }
            }
            
            $stmt->close();
        }
        
        // Update mod count
        $stmt = $conn->prepare("UPDATE attendance_sessions SET mod_count = ? WHERE id = ?");
        $stmt->bind_param('ii', $modCount, $sessionId);
        $stmt->execute();
        $stmt->close();
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Session not found']);
        $stmt->close();
    }
}
