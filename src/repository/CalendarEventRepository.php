<?php

require_once 'Repository.php';
require_once __DIR__ . '/../models/CalendarEvent.php';

class CalendarEventRepository extends Repository
{
    /**
     * Get all events for a user
     */
    public function getEventsByUserId(int $userId): array
    {
        $stmt = $this->database->connect()->prepare(
            'SELECT * FROM calendar_events WHERE user_id = :user_id 
             ORDER BY event_date ASC, start_time ASC'
        );
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $events = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $events[] = CalendarEvent::fromArray($row);
        }
        return $events;
    }

    /**
     * Get events for a specific date
     */
    public function getEventsByDate(int $userId, string $date): array
    {
        $stmt = $this->database->connect()->prepare(
            'SELECT * FROM calendar_events WHERE user_id = :user_id AND event_date = :date
             ORDER BY all_day DESC, start_time ASC'
        );
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':date', $date, PDO::PARAM_STR);
        $stmt->execute();

        $events = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $events[] = CalendarEvent::fromArray($row);
        }
        return $events;
    }

    /**
     * Get events for a specific month
     */
    public function getEventsByMonth(int $userId, int $year, int $month): array
    {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $stmt = $this->database->connect()->prepare(
            'SELECT * FROM calendar_events 
             WHERE user_id = :user_id AND event_date >= :start_date AND event_date <= :end_date
             ORDER BY event_date ASC, start_time ASC'
        );
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':start_date', $startDate, PDO::PARAM_STR);
        $stmt->bindParam(':end_date', $endDate, PDO::PARAM_STR);
        $stmt->execute();

        $events = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $events[] = CalendarEvent::fromArray($row);
        }
        return $events;
    }

    /**
     * Get single event by ID
     */
    public function getEventById(int $eventId): ?CalendarEvent
    {
        $stmt = $this->database->connect()->prepare(
            'SELECT * FROM calendar_events WHERE id = :id'
        );
        $stmt->bindParam(':id', $eventId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? CalendarEvent::fromArray($row) : null;
    }

    /**
     * Create a new event
     */
    public function createEvent(CalendarEvent $event): ?int
    {
        $stmt = $this->database->connect()->prepare(
            'INSERT INTO calendar_events (user_id, title, description, category, event_date, start_time, end_time, all_day) 
             VALUES (:user_id, :title, :description, :category, :event_date, :start_time, :end_time, :all_day)
             RETURNING id'
        );

        $userId = $event->getUserId();
        $title = $event->getTitle();
        $description = $event->getDescription();
        $category = $event->getCategory();
        $eventDate = $event->getEventDate();
        $startTime = $event->getStartTime();
        $endTime = $event->getEndTime();
        $allDay = $event->isAllDay();

        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':category', $category, PDO::PARAM_STR);
        $stmt->bindParam(':event_date', $eventDate, PDO::PARAM_STR);
        $stmt->bindParam(':start_time', $startTime, PDO::PARAM_STR);
        $stmt->bindParam(':end_time', $endTime, PDO::PARAM_STR);
        $stmt->bindParam(':all_day', $allDay, PDO::PARAM_BOOL);

        if ($stmt->execute()) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (int)$result['id'] : null;
        }
        return null;
    }

    /**
     * Update an existing event
     */
    public function updateEvent(CalendarEvent $event): bool
    {
        $stmt = $this->database->connect()->prepare(
            'UPDATE calendar_events SET 
                title = :title, 
                description = :description, 
                category = :category, 
                event_date = :event_date, 
                start_time = :start_time, 
                end_time = :end_time, 
                all_day = :all_day,
                updated_at = CURRENT_TIMESTAMP
             WHERE id = :id AND user_id = :user_id'
        );

        $id = $event->getId();
        $userId = $event->getUserId();
        $title = $event->getTitle();
        $description = $event->getDescription();
        $category = $event->getCategory();
        $eventDate = $event->getEventDate();
        $startTime = $event->getStartTime();
        $endTime = $event->getEndTime();
        $allDay = $event->isAllDay();

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':category', $category, PDO::PARAM_STR);
        $stmt->bindParam(':event_date', $eventDate, PDO::PARAM_STR);
        $stmt->bindParam(':start_time', $startTime, PDO::PARAM_STR);
        $stmt->bindParam(':end_time', $endTime, PDO::PARAM_STR);
        $stmt->bindParam(':all_day', $allDay, PDO::PARAM_BOOL);

        return $stmt->execute();
    }

    /**
     * Delete an event
     */
    public function deleteEvent(int $eventId, int $userId): bool
    {
        $stmt = $this->database->connect()->prepare(
            'DELETE FROM calendar_events WHERE id = :id AND user_id = :user_id'
        );
        $stmt->bindParam(':id', $eventId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Get today's events
     */
    public function getTodayEvents(int $userId): array
    {
        $today = date('Y-m-d');
        return $this->getEventsByDate($userId, $today);
    }

    /**
     * Get upcoming events (next 7 days)
     */
    public function getUpcomingEvents(int $userId, int $days = 7): array
    {
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime("+$days days"));

        $stmt = $this->database->connect()->prepare(
            'SELECT * FROM calendar_events 
             WHERE user_id = :user_id AND event_date >= :start_date AND event_date <= :end_date
             ORDER BY event_date ASC, start_time ASC'
        );
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':start_date', $startDate, PDO::PARAM_STR);
        $stmt->bindParam(':end_date', $endDate, PDO::PARAM_STR);
        $stmt->execute();

        $events = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $events[] = CalendarEvent::fromArray($row);
        }
        return $events;
    }

    /**
     * Get events grouped by date (for calendar view)
     */
    public function getEventsGroupedByDate(int $userId, int $year, int $month): array
    {
        $events = $this->getEventsByMonth($userId, $year, $month);
        $grouped = [];

        foreach ($events as $event) {
            $date = $event->getEventDate();
            if (!isset($grouped[$date])) {
                $grouped[$date] = [];
            }
            $grouped[$date][] = $event->toArray();
        }

        return $grouped;
    }
}
