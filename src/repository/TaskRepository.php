<?php

require_once 'Repository.php';
require_once __DIR__ . '/../models/Task.php';

class TaskRepository extends Repository
{
    /**
     * Get all tasks for a user
     */
    public function getTasksByUserId(int $userId): array
    {
        $stmt = $this->database->connect()->prepare(
            'SELECT * FROM tasks WHERE user_id = :user_id ORDER BY 
                CASE WHEN due_date IS NULL THEN 1 ELSE 0 END,
                due_date ASC,
                CASE priority 
                    WHEN \'wysoki\' THEN 1 
                    WHEN \'sredni\' THEN 2 
                    WHEN \'niski\' THEN 3 
                END,
                created_at DESC'
        );
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $tasks = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tasks[] = Task::fromArray($row);
        }
        return $tasks;
    }

    /**
     * Get tasks by status (todo or done)
     */
    public function getTasksByStatus(int $userId, string $status): array
    {
        $stmt = $this->database->connect()->prepare(
            'SELECT * FROM tasks WHERE user_id = :user_id AND status = :status 
             ORDER BY due_date ASC, created_at DESC'
        );
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->execute();

        $tasks = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tasks[] = Task::fromArray($row);
        }
        return $tasks;
    }

    /**
     * Get tasks by category
     */
    public function getTasksByCategory(int $userId, string $category): array
    {
        $stmt = $this->database->connect()->prepare(
            'SELECT * FROM tasks WHERE user_id = :user_id AND category = :category 
             ORDER BY status ASC, due_date ASC'
        );
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':category', $category, PDO::PARAM_STR);
        $stmt->execute();

        $tasks = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tasks[] = Task::fromArray($row);
        }
        return $tasks;
    }

    /**
     * Get single task by ID
     */
    public function getTaskById(int $taskId): ?Task
    {
        $stmt = $this->database->connect()->prepare(
            'SELECT * FROM tasks WHERE id = :id'
        );
        $stmt->bindParam(':id', $taskId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? Task::fromArray($row) : null;
    }

    /**
     * Create a new task
     */
    public function createTask(Task $task): ?int
    {
        $stmt = $this->database->connect()->prepare(
            'INSERT INTO tasks (user_id, title, description, category, priority, status, due_date) 
             VALUES (:user_id, :title, :description, :category, :priority, :status, :due_date)
             RETURNING id'
        );

        $userId = $task->getUserId();
        $title = $task->getTitle();
        $description = $task->getDescription();
        $category = $task->getCategory();
        $priority = $task->getPriority();
        $status = $task->getStatus();
        $dueDate = $task->getDueDate();

        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':category', $category, PDO::PARAM_STR);
        $stmt->bindParam(':priority', $priority, PDO::PARAM_STR);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':due_date', $dueDate, PDO::PARAM_STR);

        if ($stmt->execute()) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (int)$result['id'] : null;
        }
        return null;
    }

    /**
     * Update an existing task
     */
    public function updateTask(Task $task): bool
    {
        $stmt = $this->database->connect()->prepare(
            'UPDATE tasks SET 
                title = :title, 
                description = :description, 
                category = :category, 
                priority = :priority, 
                status = :status, 
                due_date = :due_date,
                updated_at = CURRENT_TIMESTAMP
             WHERE id = :id AND user_id = :user_id'
        );

        $id = $task->getId();
        $userId = $task->getUserId();
        $title = $task->getTitle();
        $description = $task->getDescription();
        $category = $task->getCategory();
        $priority = $task->getPriority();
        $status = $task->getStatus();
        $dueDate = $task->getDueDate();

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':category', $category, PDO::PARAM_STR);
        $stmt->bindParam(':priority', $priority, PDO::PARAM_STR);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':due_date', $dueDate, PDO::PARAM_STR);

        return $stmt->execute();
    }

    /**
     * Update task status only
     */
    public function updateTaskStatus(int $taskId, int $userId, string $status): bool
    {
        $stmt = $this->database->connect()->prepare(
            'UPDATE tasks SET status = :status, updated_at = CURRENT_TIMESTAMP 
             WHERE id = :id AND user_id = :user_id'
        );
        $stmt->bindParam(':id', $taskId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);

        return $stmt->execute();
    }

    /**
     * Delete a task
     */
    public function deleteTask(int $taskId, int $userId): bool
    {
        $stmt = $this->database->connect()->prepare(
            'DELETE FROM tasks WHERE id = :id AND user_id = :user_id'
        );
        $stmt->bindParam(':id', $taskId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Get task counts by status
     */
    public function getTaskCounts(int $userId): array
    {
        $stmt = $this->database->connect()->prepare(
            'SELECT status, COUNT(*) as count FROM tasks WHERE user_id = :user_id GROUP BY status'
        );
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $counts = ['todo' => 0, 'done' => 0];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $counts[$row['status']] = (int)$row['count'];
        }
        return $counts;
    }

    /**
     * Get today's tasks
     */
    public function getTodayTasks(int $userId): array
    {
        $stmt = $this->database->connect()->prepare(
            'SELECT * FROM tasks WHERE user_id = :user_id AND due_date = CURRENT_DATE 
             ORDER BY status ASC, priority DESC'
        );
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $tasks = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tasks[] = Task::fromArray($row);
        }
        return $tasks;
    }

    /**
     * Get overdue tasks
     */
    public function getOverdueTasks(int $userId): array
    {
        $stmt = $this->database->connect()->prepare(
            'SELECT * FROM tasks WHERE user_id = :user_id AND due_date < CURRENT_DATE AND status = \'todo\'
             ORDER BY due_date ASC'
        );
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $tasks = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tasks[] = Task::fromArray($row);
        }
        return $tasks;
    }
}
