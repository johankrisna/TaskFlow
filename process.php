<?php
header('Content-Type: application/json');
require_once 'config.php';

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
case 'get_tasks':
    $filter_type = $_POST['filter_type'] ?? 'all';
    $filter_value = $_POST['filter_value'] ?? '';
    
    $query = "SELECT * FROM tasks WHERE 1=1";
    $params = [];
    
    if ($filter_type === 'category' && !empty($filter_value)) {
        $query .= " AND category = :category";
        $params[':category'] = $filter_value;
    } elseif ($filter_type === 'status' && !empty($filter_value)) {
        $query .= " AND status = :status";
        $params[':status'] = $filter_value;
    } elseif ($filter_type === 'priority' && !empty($filter_value)) {
        $query .= " AND priority = :priority";
        $params[':priority'] = $filter_value;
    }
    
    $query .= " ORDER BY created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'tasks' => $tasks]);
    break;
            
        case 'add_task':
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            $due_date = $_POST['due_date'] ?? '';
            $category = $_POST['category'] ?? '';
            $priority = $_POST['priority'] ?? 'medium';
            
            if (empty($title) || empty($due_date)) {
                echo json_encode(['success' => false, 'message' => 'Judul dan tanggal wajib diisi']);
                exit;
            }
            
            $stmt = $pdo->prepare("INSERT INTO tasks (title, description, due_date, category, priority) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $due_date, $category, $priority]);
            
            echo json_encode(['success' => true, 'message' => 'Tugas berhasil ditambahkan']);
            break;
            
        case 'update_task_status':
            $task_id = $_POST['task_id'] ?? 0;
            $status = $_POST['status'] ?? 'pending';
            
            $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
            $stmt->execute([$status, $task_id]);
            
            echo json_encode(['success' => true, 'message' => 'Status tugas diperbarui']);
            break;
            
        case 'delete_task':
            $task_id = $_POST['task_id'] ?? 0;
            
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
            $stmt->execute([$task_id]);
            
            echo json_encode(['success' => true, 'message' => 'Tugas dihapus']);
            break;
            
        case 'get_stats':
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM tasks");
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $stmt = $pdo->query("SELECT COUNT(*) as completed FROM tasks WHERE status = 'completed'");
            $completed = $stmt->fetch(PDO::FETCH_ASSOC)['completed'];
            
            $stmt = $pdo->query("SELECT COUNT(*) as pending FROM tasks WHERE status = 'pending'");
            $pending = $stmt->fetch(PDO::FETCH_ASSOC)['pending'];
            
            $stmt = $pdo->query("SELECT COUNT(*) as high FROM tasks WHERE priority = 'high'");
            $high = $stmt->fetch(PDO::FETCH_ASSOC)['high'];
            
            echo json_encode([
                'success' => true, 
                'stats' => [
                    'total' => $total,
                    'completed' => $completed,
                    'pending' => $pending,
                    'high' => $high
                ]
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Aksi tidak valid']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>