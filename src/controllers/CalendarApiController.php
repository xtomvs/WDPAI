<?php

require_once 'AppController.php';
require_once __DIR__ . '/../repository/CalendarEventRepository.php';

class CalendarApiController extends AppController
{
    private CalendarEventRepository $eventRepository;

    public function __construct()
    {
        parent::__construct();
        $this->eventRepository = new CalendarEventRepository();
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
     * GET /api/events - Get all events for current user
     */
    public function getEvents(): void
    {
        $this->requireLogin();
        $userId = $_SESSION['user_id'];

        // Check for month/year filter
        $year = isset($_GET['year']) ? (int)$_GET['year'] : null;
        $month = isset($_GET['month']) ? (int)$_GET['month'] : null;
        $date = $_GET['date'] ?? null;

        if ($date) {
            $events = $this->eventRepository->getEventsByDate($userId, $date);
        } elseif ($year && $month) {
            $events = $this->eventRepository->getEventsByMonth($userId, $year, $month);
        } else {
            $events = $this->eventRepository->getEventsByUserId($userId);
        }

        $eventsArray = array_map(fn(CalendarEvent $event) => $event->toArray(), $events);

        $this->jsonResponse([
            'success' => true,
            'data' => $eventsArray
        ]);
    }

    /**
     * GET /api/events/month - Get events grouped by date for calendar view
     */
    public function getEventsByMonth(): void
    {
        $this->requireLogin();
        $userId = $_SESSION['user_id'];

        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');

        $grouped = $this->eventRepository->getEventsGroupedByDate($userId, $year, $month);

        $this->jsonResponse([
            'success' => true,
            'data' => $grouped,
            'meta' => [
                'year' => $year,
                'month' => $month
            ]
        ]);
    }

    /**
     * GET /api/events/{id} - Get single event
     */
    public function getEvent(): void
    {
        $this->requireLogin();
        $userId = $_SESSION['user_id'];
        $eventId = (int)($_GET['id'] ?? 0);

        if (!$eventId) {
            $this->jsonResponse(['success' => false, 'message' => 'ID wydarzenia jest wymagane'], 400);
        }

        $event = $this->eventRepository->getEventById($eventId);
        
        if (!$event || $event->getUserId() !== $userId) {
            $this->jsonResponse(['success' => false, 'message' => 'Wydarzenie nie znalezione'], 404);
        }

        $this->jsonResponse([
            'success' => true,
            'data' => $event->toArray()
        ]);
    }

    /**
     * POST /api/events - Create new event
     */
    public function createEvent(): void
    {
        $this->requireLogin();
        $userId = $_SESSION['user_id'];
        $data = $this->getJsonInput();

        // Validation
        if (empty($data['title'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Tytuł jest wymagany'], 400);
        }
        if (empty($data['eventDate'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Data jest wymagana'], 400);
        }

        $event = new CalendarEvent(
            $userId,
            trim($data['title']),
            $data['eventDate'],
            $data['description'] ?? null,
            $data['category'] ?? 'prywatne',
            !empty($data['startTime']) ? $data['startTime'] : null,
            !empty($data['endTime']) ? $data['endTime'] : null,
            (bool)($data['allDay'] ?? false)
        );

        $eventId = $this->eventRepository->createEvent($event);

        if ($eventId) {
            $newEvent = $this->eventRepository->getEventById($eventId);
            $this->jsonResponse([
                'success' => true,
                'message' => 'Wydarzenie utworzone',
                'data' => $newEvent->toArray()
            ], 201);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Błąd tworzenia wydarzenia'], 500);
        }
    }

    /**
     * PUT /api/events/{id} - Update event
     */
    public function updateEvent(): void
    {
        $this->requireLogin();
        $userId = $_SESSION['user_id'];
        $eventId = (int)($_GET['id'] ?? 0);
        $data = $this->getJsonInput();

        if (!$eventId) {
            $this->jsonResponse(['success' => false, 'message' => 'ID wydarzenia jest wymagane'], 400);
        }

        $event = $this->eventRepository->getEventById($eventId);
        
        if (!$event || $event->getUserId() !== $userId) {
            $this->jsonResponse(['success' => false, 'message' => 'Wydarzenie nie znalezione'], 404);
        }

        // Update event properties
        if (isset($data['title'])) $event->setTitle(trim($data['title']));
        if (isset($data['description'])) $event->setDescription($data['description']);
        if (isset($data['category'])) $event->setCategory($data['category']);
        if (isset($data['eventDate'])) $event->setEventDate($data['eventDate']);
        if (array_key_exists('startTime', $data)) $event->setStartTime($data['startTime']);
        if (array_key_exists('endTime', $data)) $event->setEndTime($data['endTime']);
        if (isset($data['allDay'])) $event->setAllDay((bool)$data['allDay']);

        if ($this->eventRepository->updateEvent($event)) {
            $updatedEvent = $this->eventRepository->getEventById($eventId);
            $this->jsonResponse([
                'success' => true,
                'message' => 'Wydarzenie zaktualizowane',
                'data' => $updatedEvent->toArray()
            ]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Błąd aktualizacji wydarzenia'], 500);
        }
    }

    /**
     * DELETE /api/events/{id} - Delete event
     */
    public function deleteEvent(): void
    {
        $this->requireLogin();
        $userId = $_SESSION['user_id'];
        $eventId = (int)($_GET['id'] ?? 0);

        if (!$eventId) {
            $this->jsonResponse(['success' => false, 'message' => 'ID wydarzenia jest wymagane'], 400);
        }

        $event = $this->eventRepository->getEventById($eventId);
        
        if (!$event || $event->getUserId() !== $userId) {
            $this->jsonResponse(['success' => false, 'message' => 'Wydarzenie nie znalezione'], 404);
        }

        if ($this->eventRepository->deleteEvent($eventId, $userId)) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Wydarzenie usunięte'
            ]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Błąd usuwania wydarzenia'], 500);
        }
    }

    /**
     * GET /api/events/today - Get today's events
     */
    public function getTodayEvents(): void
    {
        $this->requireLogin();
        $userId = $_SESSION['user_id'];

        $events = $this->eventRepository->getTodayEvents($userId);
        $eventsArray = array_map(fn(CalendarEvent $event) => $event->toArray(), $events);

        $this->jsonResponse([
            'success' => true,
            'data' => $eventsArray
        ]);
    }

    /**
     * GET /api/events/upcoming - Get upcoming events
     */
    public function getUpcomingEvents(): void
    {
        $this->requireLogin();
        $userId = $_SESSION['user_id'];
        $days = isset($_GET['days']) ? (int)$_GET['days'] : 7;

        $events = $this->eventRepository->getUpcomingEvents($userId, $days);
        $eventsArray = array_map(fn(CalendarEvent $event) => $event->toArray(), $events);

        $this->jsonResponse([
            'success' => true,
            'data' => $eventsArray
        ]);
    }
}
