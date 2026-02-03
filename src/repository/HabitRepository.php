<?php

require_once 'Repository.php';
require_once __DIR__ . '/../models/Habit.php';

class HabitRepository extends Repository
{
    /**
     * Get all habits for a user with week completions
     */
    public function getHabitsByUserId(int $userId): array
    {
        $stmt = $this->database->connect()->prepare(
            'SELECT * FROM habits WHERE user_id = :user_id ORDER BY created_at DESC'
        );
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $habits = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $habit = Habit::fromArray($row);
            $habit->setWeekCompletions($this->getWeekCompletions((int)$row['id']));
            $habits[] = $habit;
        }
        return $habits;
    }

    /**
     * Get single habit by ID
     */
    public function getHabitById(int $habitId): ?Habit
    {
        $stmt = $this->database->connect()->prepare(
            'SELECT * FROM habits WHERE id = :id'
        );
        $stmt->bindParam(':id', $habitId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        $habit = Habit::fromArray($row);
        $habit->setWeekCompletions($this->getWeekCompletions($habitId));
        return $habit;
    }

    /**
     * Create a new habit
     */
    public function createHabit(Habit $habit): ?int
    {
        $stmt = $this->database->connect()->prepare(
            'INSERT INTO habits (user_id, title, category, frequency, accent_color, icon, points_per_day, streak_days) 
             VALUES (:user_id, :title, :category, :frequency, :accent_color, :icon, :points_per_day, :streak_days)
             RETURNING id'
        );

        $userId = $habit->getUserId();
        $title = $habit->getTitle();
        $category = $habit->getCategory();
        $frequency = $habit->getFrequency();
        $accentColor = $habit->getAccentColor();
        $icon = $habit->getIcon();
        $pointsPerDay = $habit->getPointsPerDay();
        $streakDays = $habit->getStreakDays();

        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':category', $category, PDO::PARAM_STR);
        $stmt->bindParam(':frequency', $frequency, PDO::PARAM_STR);
        $stmt->bindParam(':accent_color', $accentColor, PDO::PARAM_STR);
        $stmt->bindParam(':icon', $icon, PDO::PARAM_STR);
        $stmt->bindParam(':points_per_day', $pointsPerDay, PDO::PARAM_INT);
        $stmt->bindParam(':streak_days', $streakDays, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (int)$result['id'] : null;
        }
        return null;
    }

    /**
     * Update an existing habit
     */
    public function updateHabit(Habit $habit): bool
    {
        $stmt = $this->database->connect()->prepare(
            'UPDATE habits SET 
                title = :title, 
                category = :category, 
                frequency = :frequency, 
                accent_color = :accent_color, 
                icon = :icon, 
                points_per_day = :points_per_day,
                streak_days = :streak_days,
                updated_at = CURRENT_TIMESTAMP
             WHERE id = :id AND user_id = :user_id'
        );

        $id = $habit->getId();
        $userId = $habit->getUserId();
        $title = $habit->getTitle();
        $category = $habit->getCategory();
        $frequency = $habit->getFrequency();
        $accentColor = $habit->getAccentColor();
        $icon = $habit->getIcon();
        $pointsPerDay = $habit->getPointsPerDay();
        $streakDays = $habit->getStreakDays();

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':category', $category, PDO::PARAM_STR);
        $stmt->bindParam(':frequency', $frequency, PDO::PARAM_STR);
        $stmt->bindParam(':accent_color', $accentColor, PDO::PARAM_STR);
        $stmt->bindParam(':icon', $icon, PDO::PARAM_STR);
        $stmt->bindParam(':points_per_day', $pointsPerDay, PDO::PARAM_INT);
        $stmt->bindParam(':streak_days', $streakDays, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Delete a habit and its completions
     */
    public function deleteHabit(int $habitId, int $userId): bool
    {
        $conn = $this->database->connect();
        
        // Delete completions first
        $stmt = $conn->prepare(
            'DELETE FROM habit_completions WHERE habit_id = :habit_id'
        );
        $stmt->bindParam(':habit_id', $habitId, PDO::PARAM_INT);
        $stmt->execute();

        // Delete habit
        $stmt = $conn->prepare(
            'DELETE FROM habits WHERE id = :id AND user_id = :user_id'
        );
        $stmt->bindParam(':id', $habitId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Toggle habit completion for a specific date
     */
    public function toggleCompletion(int $habitId, int $userId, string $date): bool
    {
        // First verify the habit belongs to the user
        $stmt = $this->database->connect()->prepare(
            'SELECT id FROM habits WHERE id = :id AND user_id = :user_id'
        );
        $stmt->bindParam(':id', $habitId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        if (!$stmt->fetch()) {
            return false;
        }

        // Check if completion exists
        $stmt = $this->database->connect()->prepare(
            'SELECT id FROM habit_completions WHERE habit_id = :habit_id AND completion_date = :date'
        );
        $stmt->bindParam(':habit_id', $habitId, PDO::PARAM_INT);
        $stmt->bindParam(':date', $date, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->fetch()) {
            // Remove completion
            $stmt = $this->database->connect()->prepare(
                'DELETE FROM habit_completions WHERE habit_id = :habit_id AND completion_date = :date'
            );
        } else {
            // Add completion
            $stmt = $this->database->connect()->prepare(
                'INSERT INTO habit_completions (habit_id, completion_date) VALUES (:habit_id, :date)'
            );
        }
        $stmt->bindParam(':habit_id', $habitId, PDO::PARAM_INT);
        $stmt->bindParam(':date', $date, PDO::PARAM_STR);

        $result = $stmt->execute();
        
        // Update streak if successful
        if ($result) {
            $this->updateStreak($habitId);
        }

        return $result;
    }

    /**
     * Get week completions for a habit (last 7 days)
     */
    public function getWeekCompletions(int $habitId): array
    {
        $weekDays = ['pon', 'wt', 'śr', 'czw', 'pt', 'sob', 'nd'];
        $completions = [];

        // Get completions from the last 7 days
        $stmt = $this->database->connect()->prepare(
            'SELECT completion_date FROM habit_completions 
             WHERE habit_id = :habit_id 
             AND completion_date >= CURRENT_DATE - INTERVAL \'6 days\'
             ORDER BY completion_date'
        );
        $stmt->bindParam(':habit_id', $habitId, PDO::PARAM_INT);
        $stmt->execute();

        $completedDates = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $completedDates[] = $row['completion_date'];
        }

        // Build week array starting from Monday of current week
        $today = new DateTime();
        $dayOfWeek = (int)$today->format('N'); // 1 (Mon) to 7 (Sun)
        $monday = (clone $today)->modify('-' . ($dayOfWeek - 1) . ' days');

        for ($i = 0; $i < 7; $i++) {
            $date = (clone $monday)->modify("+$i days");
            $dateStr = $date->format('Y-m-d');
            $completions[] = [
                'day' => $weekDays[$i],
                'date' => $dateStr,
                'done' => in_array($dateStr, $completedDates)
            ];
        }

        return $completions;
    }

    /**
     * Update streak count for a habit
     */
    private function updateStreak(int $habitId): void
    {
        // Calculate streak (consecutive days completed ending today or yesterday)
        $stmt = $this->database->connect()->prepare(
            'SELECT completion_date FROM habit_completions 
             WHERE habit_id = :habit_id 
             ORDER BY completion_date DESC'
        );
        $stmt->bindParam(':habit_id', $habitId, PDO::PARAM_INT);
        $stmt->execute();

        $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $streak = 0;

        if (!empty($dates)) {
            $today = new DateTime();
            $yesterday = (clone $today)->modify('-1 day');
            
            $lastCompletion = new DateTime($dates[0]);
            
            // Streak counts if last completion was today or yesterday
            if ($lastCompletion->format('Y-m-d') == $today->format('Y-m-d') || 
                $lastCompletion->format('Y-m-d') == $yesterday->format('Y-m-d')) {
                
                $streak = 1;
                for ($i = 1; $i < count($dates); $i++) {
                    $currentDate = new DateTime($dates[$i]);
                    $prevDate = new DateTime($dates[$i - 1]);
                    $diff = $prevDate->diff($currentDate)->days;
                    
                    if ($diff == 1) {
                        $streak++;
                    } else {
                        break;
                    }
                }
            }
        }

        // Update streak in database
        $stmt = $this->database->connect()->prepare(
            'UPDATE habits SET streak_days = :streak WHERE id = :id'
        );
        $stmt->bindParam(':streak', $streak, PDO::PARAM_INT);
        $stmt->bindParam(':id', $habitId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Get total points earned by user
     */
    public function getTotalPoints(int $userId): int
    {
        $stmt = $this->database->connect()->prepare(
            'SELECT SUM(h.points_per_day) as total_points
             FROM habit_completions hc
             JOIN habits h ON hc.habit_id = h.id
             WHERE h.user_id = :user_id'
        );
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total_points'] ?? 0);
    }

    /**
     * Get habit statistics for dashboard
     */
    public function getHabitStats(int $userId): array
    {
        $habits = $this->getHabitsByUserId($userId);
        
        $totalHabits = count($habits);
        $todayCompleted = 0;
        $maxStreak = 0;
        $totalPoints = $this->getTotalPoints($userId);

        $today = (new DateTime())->format('Y-m-d');

        foreach ($habits as $habit) {
            $week = $habit->getWeekCompletions();
            foreach ($week as $day) {
                if ($day['date'] === $today && $day['done']) {
                    $todayCompleted++;
                    break;
                }
            }
            if ($habit->getStreakDays() > $maxStreak) {
                $maxStreak = $habit->getStreakDays();
            }
        }

        return [
            'totalHabits' => $totalHabits,
            'todayCompleted' => $todayCompleted,
            'maxStreak' => $maxStreak,
            'totalPoints' => $totalPoints
        ];
    }
}
