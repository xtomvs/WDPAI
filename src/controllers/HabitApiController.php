<?php

require_once 'AppController.php';
require_once __DIR__ . '/../repository/HabitRepository.php';

class HabitApiController extends AppController
{
    private HabitRepository $habitRepository;

    public function __construct()
    {
        parent::__construct();
        $this->habitRepository = new HabitRepository();
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
     * GET /api/habits - Get all habits for current user
     */
    public function getHabits(): void
    {
        $this->requireLogin();
        $userId = $_SESSION['user_id'];

        $habits = $this->habitRepository->getHabitsByUserId($userId);
        $habitsArray = array_map(fn(Habit $habit) => $habit->toArray(), $habits);

        $this->jsonResponse([
            'success' => true,
            'data' => $habitsArray
        ]);
    }

    /**
     * GET /api/habits/{id} - Get single habit
     */
    public function getHabit(): void
    {
        $this->requireLogin();
        $userId = $_SESSION['user_id'];
        $habitId = (int)($_GET['id'] ?? 0);

        if (!$habitId) {
            $this->jsonResponse(['success' => false, 'message' => 'ID nawyku jest wymagane'], 400);
        }

        $habit = $this->habitRepository->getHabitById($habitId);
        
        if (!$habit || $habit->getUserId() !== $userId) {
            $this->jsonResponse(['success' => false, 'message' => 'Nawyk nie znaleziony'], 404);
        }

        $this->jsonResponse([
            'success' => true,
            'data' => $habit->toArray()
        ]);
    }

    /**
     * POST /api/habits - Create new habit
     */
    public function createHabit(): void
    {
        $this->requireLogin();
        $userId = $_SESSION['user_id'];
        $data = $this->getJsonInput();

        // Validation
        if (empty($data['title'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Nazwa nawyku jest wymagana'], 400);
        }

        $habit = new Habit(
            $userId,
            trim($data['title']),
            $data['category'] ?? 'zdrowie',
            $data['frequency'] ?? 'daily',
            $data['accentColor'] ?? 'blue',
            $data['icon'] ?? 'check',
            (int)($data['pointsPerDay'] ?? 10),
            0 // streak starts at 0
        );

        $habitId = $this->habitRepository->createHabit($habit);

        if ($habitId) {
            $newHabit = $this->habitRepository->getHabitById($habitId);
            $this->jsonResponse([
                'success' => true,
                'message' => 'Nawyk utworzony',
                'data' => $newHabit->toArray()
            ], 201);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Błąd tworzenia nawyku'], 500);
        }
    }

    /**
     * PUT /api/habits/{id} - Update habit
     */
    public function updateHabit(): void
    {
        $this->requireLogin();
        $userId = $_SESSION['user_id'];
        $habitId = (int)($_GET['id'] ?? 0);
        $data = $this->getJsonInput();

        if (!$habitId) {
            $this->jsonResponse(['success' => false, 'message' => 'ID nawyku jest wymagane'], 400);
        }

        $habit = $this->habitRepository->getHabitById($habitId);
        
        if (!$habit || $habit->getUserId() !== $userId) {
            $this->jsonResponse(['success' => false, 'message' => 'Nawyk nie znaleziony'], 404);
        }

        // Update habit properties
        if (isset($data['title'])) $habit->setTitle(trim($data['title']));
        if (isset($data['category'])) $habit->setCategory($data['category']);
        if (isset($data['frequency'])) $habit->setFrequency($data['frequency']);
        if (isset($data['accentColor'])) $habit->setAccentColor($data['accentColor']);
        if (isset($data['icon'])) $habit->setIcon($data['icon']);
        if (isset($data['pointsPerDay'])) $habit->setPointsPerDay((int)$data['pointsPerDay']);

        if ($this->habitRepository->updateHabit($habit)) {
            $updatedHabit = $this->habitRepository->getHabitById($habitId);
            $this->jsonResponse([
                'success' => true,
                'message' => 'Nawyk zaktualizowany',
                'data' => $updatedHabit->toArray()
            ]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Błąd aktualizacji nawyku'], 500);
        }
    }

    /**
     * POST /api/habits/{id}/toggle - Toggle habit completion for a date
     */
    public function toggleCompletion(): void
    {
        $this->requireLogin();
        $userId = $_SESSION['user_id'];
        $habitId = (int)($_GET['id'] ?? 0);
        $data = $this->getJsonInput();

        if (!$habitId) {
            $this->jsonResponse(['success' => false, 'message' => 'ID nawyku jest wymagane'], 400);
        }

        // Use provided date or today
        $date = $data['date'] ?? date('Y-m-d');

        if ($this->habitRepository->toggleCompletion($habitId, $userId, $date)) {
            $habit = $this->habitRepository->getHabitById($habitId);
            $this->jsonResponse([
                'success' => true,
                'message' => 'Status ukończenia zmieniony',
                'data' => $habit ? $habit->toArray() : null
            ]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Błąd zmiany statusu'], 500);
        }
    }

    /**
     * DELETE /api/habits/{id} - Delete habit
     */
    public function deleteHabit(): void
    {
        $this->requireLogin();
        $userId = $_SESSION['user_id'];
        $habitId = (int)($_GET['id'] ?? 0);

        if (!$habitId) {
            $this->jsonResponse(['success' => false, 'message' => 'ID nawyku jest wymagane'], 400);
        }

        $habit = $this->habitRepository->getHabitById($habitId);
        
        if (!$habit || $habit->getUserId() !== $userId) {
            $this->jsonResponse(['success' => false, 'message' => 'Nawyk nie znaleziony'], 404);
        }

        if ($this->habitRepository->deleteHabit($habitId, $userId)) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Nawyk usunięty'
            ]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Błąd usuwania nawyku'], 500);
        }
    }

    /**
     * GET /api/habits/stats - Get habit statistics
     */
    public function getStats(): void
    {
        $this->requireLogin();
        $userId = $_SESSION['user_id'];

        $stats = $this->habitRepository->getHabitStats($userId);

        $this->jsonResponse([
            'success' => true,
            'data' => $stats
        ]);
    }
}
