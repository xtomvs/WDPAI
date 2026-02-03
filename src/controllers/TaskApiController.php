<?php

require_once 'AppController.php';
require_once __DIR__ . '/../repository/TaskRepository.php';

class TaskApiController extends AppController
{
    private TaskRepository $taskRepository;

    public function __construct()
    {
        parent::__construct();
        $this->taskRepository = new TaskRepository();
    }

    /**
     * Send JSON response
     */
    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Get JSON input from request body
     */
    private function getJsonInput(): array
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }

    /**
     * GET /api/tasks - Get all tasks for current user
     */
    public function getTasks(): void
    {
        $this->requireLogin();
        $userId = $_SESSION['user_id'];

        $tasks = $this->taskRepository->getTasksByUserId($userId);
        $tasksArray = array_map(fn(Task $task) => $task->toArray(), $tasks);

        $this->jsonResponse([
            'success' => true,
            'data' => $tasksArray
        ]);
    }

    /**
     * GET /api/tasks/{id} - Get single task
     */
    public function getTask(): void
    {
        $this->requireLogin();
        $userId = $_SESSION['user_id'];
        $taskId = (int)($_GET['id'] ?? 0);

        if (!$taskId) {
            $this->jsonResponse(['success' => false, 'message' => 'ID zadania jest wymagane'], 400);
        }

        $task = $this->taskRepository->getTaskById($taskId);
        
        if (!$task || $task->getUserId() !== $userId) {
            $this->jsonResponse(['success' => false, 'message' => 'Zadanie nie znalezione'], 404);
        }

        $this->jsonResponse([
            'success' => true,
            'data' => $task->toArray()
        ]);
    }

    /**
     * POST /api/tasks - Create new task
     */
    public function createTask(): void
    {
        $this->requireLogin();
        $userId = $_SESSION['user_id'];
        $data = $this->getJsonInput();

        // Validation
        if (empty($data['title'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Tytuł jest wymagany'], 400);
        }

        $task = new Task(
            $userId,
            trim($data['title']),
            $data['description'] ?? null,
            $data['category'] ?? 'osobiste',
            $data['priority'] ?? 'sredni',
            'todo',
            !empty($data['dueDate']) ? $data['dueDate'] : null
        );

        $taskId = $this->taskRepository->createTask($task);

        if ($taskId) {
            $newTask = $this->taskRepository->getTaskById($taskId);
            $this->jsonResponse([
                'success' => true,
                'message' => 'Zadanie utworzone',
                'data' => $newTask->toArray()
            ], 201);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Błąd tworzenia zadania'], 500);
        }
    }

    /**
     * PUT /api/tasks/{id} - Update task
     */
    public function updateTask(): void
    {
        $this->requireLogin();
        $userId = $_SESSION['user_id'];
        $taskId = (int)($_GET['id'] ?? 0);
        $data = $this->getJsonInput();

        if (!$taskId) {
            $this->jsonResponse(['success' => false, 'message' => 'ID zadania jest wymagane'], 400);
        }

        $task = $this->taskRepository->getTaskById($taskId);
        
        if (!$task || $task->getUserId() !== $userId) {
            $this->jsonResponse(['success' => false, 'message' => 'Zadanie nie znalezione'], 404);
        }

        // Update task properties
        if (isset($data['title'])) $task->setTitle(trim($data['title']));
        if (isset($data['description'])) $task->setDescription($data['description']);
        if (isset($data['category'])) $task->setCategory($data['category']);
        if (isset($data['priority'])) $task->setPriority($data['priority']);
        if (isset($data['status'])) $task->setStatus($data['status']);
        if (array_key_exists('dueDate', $data)) $task->setDueDate($data['dueDate']);

        if ($this->taskRepository->updateTask($task)) {
            $updatedTask = $this->taskRepository->getTaskById($taskId);
            $this->jsonResponse([
                'success' => true,
                'message' => 'Zadanie zaktualizowane',
                'data' => $updatedTask->toArray()
            ]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Błąd aktualizacji zadania'], 500);
        }
    }

    /**
     * PATCH /api/tasks/{id}/status - Toggle task status
     */
    public function toggleStatus(): void
    {
        $this->requireLogin();
        $userId = $_SESSION['user_id'];
        $taskId = (int)($_GET['id'] ?? 0);

        if (!$taskId) {
            $this->jsonResponse(['success' => false, 'message' => 'ID zadania jest wymagane'], 400);
        }

        $task = $this->taskRepository->getTaskById($taskId);
        
        if (!$task || $task->getUserId() !== $userId) {
            $this->jsonResponse(['success' => false, 'message' => 'Zadanie nie znalezione'], 404);
        }

        $newStatus = $task->getStatus() === 'done' ? 'todo' : 'done';
        
        if ($this->taskRepository->updateTaskStatus($taskId, $userId, $newStatus)) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Status zmieniony',
                'data' => ['id' => $taskId, 'status' => $newStatus]
            ]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Błąd zmiany statusu'], 500);
        }
    }

    /**
     * DELETE /api/tasks/{id} - Delete task
     */
    public function deleteTask(): void
    {
        $this->requireLogin();
        $userId = $_SESSION['user_id'];
        $taskId = (int)($_GET['id'] ?? 0);

        if (!$taskId) {
            $this->jsonResponse(['success' => false, 'message' => 'ID zadania jest wymagane'], 400);
        }

        $task = $this->taskRepository->getTaskById($taskId);
        
        if (!$task || $task->getUserId() !== $userId) {
            $this->jsonResponse(['success' => false, 'message' => 'Zadanie nie znalezione'], 404);
        }

        if ($this->taskRepository->deleteTask($taskId, $userId)) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Zadanie usunięte'
            ]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Błąd usuwania zadania'], 500);
        }
    }

    /**
     * GET /api/tasks/stats - Get task statistics
     */
    public function getStats(): void
    {
        $this->requireLogin();
        $userId = $_SESSION['user_id'];

        $counts = $this->taskRepository->getTaskCounts($userId);
        $todayTasks = $this->taskRepository->getTodayTasks($userId);
        $overdueTasks = $this->taskRepository->getOverdueTasks($userId);

        $this->jsonResponse([
            'success' => true,
            'data' => [
                'total' => $counts['todo'] + $counts['done'],
                'todo' => $counts['todo'],
                'done' => $counts['done'],
                'today' => count($todayTasks),
                'overdue' => count($overdueTasks)
            ]
        ]);
    }
}
