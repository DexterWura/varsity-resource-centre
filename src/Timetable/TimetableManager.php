<?php

namespace Timetable;

use Database\DB;

class TimetableManager
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = DB::pdo();
    }

    /**
     * Get all universities
     */
    public function getUniversities(): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM universities WHERE is_active = 1 ORDER BY name');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get faculties by university
     */
    public function getFacultiesByUniversity(int $universityId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM faculties WHERE university_id = ? AND is_active = 1 ORDER BY name');
        $stmt->execute([$universityId]);
        return $stmt->fetchAll();
    }

    /**
     * Get semesters by university
     */
    public function getSemestersByUniversity(int $universityId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM semesters WHERE university_id = ? AND is_active = 1 ORDER BY start_date DESC');
        $stmt->execute([$universityId]);
        return $stmt->fetchAll();
    }

    /**
     * Get modules by faculty
     */
    public function getModulesByFaculty(int $facultyId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM modules WHERE faculty_id = ? AND is_active = 1 ORDER BY code');
        $stmt->execute([$facultyId]);
        return $stmt->fetchAll();
    }

    /**
     * Get module schedules for selected modules and semester
     */
    public function getModuleSchedules(array $moduleIds, int $semesterId): array
    {
        if (empty($moduleIds)) {
            return [];
        }

        $placeholders = str_repeat('?,', count($moduleIds) - 1) . '?';
        $stmt = $this->pdo->prepare("
            SELECT ms.*, m.name as module_name, m.code as module_code, m.credits
            FROM module_schedules ms
            JOIN modules m ON ms.module_id = m.id
            WHERE ms.module_id IN ($placeholders) 
            AND ms.semester_id = ? 
            AND ms.is_active = 1
            ORDER BY ms.day_of_week, ms.start_time
        ");
        
        $params = array_merge($moduleIds, [$semesterId]);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Create a student timetable
     */
    public function createStudentTimetable(int $userId, int $universityId, int $semesterId, int $facultyId, string $name, array $moduleIds): array
    {
        try {
            $this->pdo->beginTransaction();

            // Create timetable
            $stmt = $this->pdo->prepare('
                INSERT INTO student_timetables (user_id, university_id, semester_id, faculty_id, name)
                VALUES (?, ?, ?, ?, ?)
            ');
            $stmt->execute([$userId, $universityId, $semesterId, $facultyId, $name]);
            $timetableId = $this->pdo->lastInsertId();

            // Add modules to timetable
            if (!empty($moduleIds)) {
                $stmt = $this->pdo->prepare('
                    INSERT INTO student_timetable_modules (timetable_id, module_id)
                    VALUES (?, ?)
                ');
                foreach ($moduleIds as $moduleId) {
                    $stmt->execute([$timetableId, $moduleId]);
                }
            }

            $this->pdo->commit();
            return ['success' => true, 'timetable_id' => $timetableId];
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get student timetables
     */
    public function getStudentTimetables(int $userId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT st.*, u.name as university_name, s.name as semester_name, f.name as faculty_name
            FROM student_timetables st
            JOIN universities u ON st.university_id = u.id
            JOIN semesters s ON st.semester_id = s.id
            JOIN faculties f ON st.faculty_id = f.id
            WHERE st.user_id = ? AND st.is_active = 1
            ORDER BY st.created_at DESC
        ');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Get timetable details with modules and schedules
     */
    public function getTimetableDetails(int $timetableId): array
    {
        // Get timetable info
        $stmt = $this->pdo->prepare('
            SELECT st.*, u.name as university_name, s.name as semester_name, f.name as faculty_name
            FROM student_timetables st
            JOIN universities u ON st.university_id = u.id
            JOIN semesters s ON st.semester_id = s.id
            JOIN faculties f ON st.faculty_id = f.id
            WHERE st.id = ?
        ');
        $stmt->execute([$timetableId]);
        $timetable = $stmt->fetch();

        if (!$timetable) {
            return null;
        }

        // Get modules
        $stmt = $this->pdo->prepare('
            SELECT m.*
            FROM modules m
            JOIN student_timetable_modules stm ON m.id = stm.module_id
            WHERE stm.timetable_id = ?
            ORDER BY m.code
        ');
        $stmt->execute([$timetableId]);
        $modules = $stmt->fetchAll();

        // Get schedules
        $moduleIds = array_column($modules, 'id');
        $schedules = $this->getModuleSchedules($moduleIds, $timetable['semester_id']);

        return [
            'timetable' => $timetable,
            'modules' => $modules,
            'schedules' => $schedules
        ];
    }

    /**
     * Generate timetable grid data
     */
    public function generateTimetableGrid(array $schedules): array
    {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $timeSlots = $this->generateTimeSlots();
        
        $grid = [];
        
        // Initialize grid
        foreach ($days as $day) {
            $grid[$day] = [];
            foreach ($timeSlots as $time) {
                $grid[$day][$time] = null;
            }
        }

        // Fill grid with schedules
        foreach ($schedules as $schedule) {
            $day = $schedule['day_of_week'];
            $startTime = $schedule['start_time'];
            $endTime = $schedule['end_time'];
            
            // Find time slots that overlap with this schedule
            foreach ($timeSlots as $time) {
                if ($this->timeInRange($time, $startTime, $endTime)) {
                    $grid[$day][$time] = $schedule;
                }
            }
        }

        return $grid;
    }

    /**
     * Generate time slots (30-minute intervals from 7 AM to 7 PM)
     */
    private function generateTimeSlots(): array
    {
        $slots = [];
        for ($hour = 7; $hour <= 19; $hour++) {
            for ($minute = 0; $minute < 60; $minute += 30) {
                $time = sprintf('%02d:%02d', $hour, $minute);
                $slots[] = $time;
            }
        }
        return $slots;
    }

    /**
     * Check if a time falls within a range
     */
    private function timeInRange(string $time, string $startTime, string $endTime): bool
    {
        return $time >= $startTime && $time < $endTime;
    }

    /**
     * Delete a student timetable
     */
    public function deleteStudentTimetable(int $timetableId, int $userId): bool
    {
        try {
            $stmt = $this->pdo->prepare('
                UPDATE student_timetables 
                SET is_active = 0 
                WHERE id = ? AND user_id = ?
            ');
            $stmt->execute([$timetableId, $userId]);
            return $stmt->rowCount() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check for schedule conflicts
     */
    public function checkScheduleConflicts(array $moduleIds, int $semesterId): array
    {
        $schedules = $this->getModuleSchedules($moduleIds, $semesterId);
        $conflicts = [];
        
        // Group schedules by day and time
        $scheduleMap = [];
        foreach ($schedules as $schedule) {
            $key = $schedule['day_of_week'] . '_' . $schedule['start_time'] . '_' . $schedule['end_time'];
            if (!isset($scheduleMap[$key])) {
                $scheduleMap[$key] = [];
            }
            $scheduleMap[$key][] = $schedule;
        }
        
        // Find conflicts
        foreach ($scheduleMap as $key => $schedules) {
            if (count($schedules) > 1) {
                $conflicts[] = [
                    'day' => $schedules[0]['day_of_week'],
                    'time' => $schedules[0]['start_time'] . ' - ' . $schedules[0]['end_time'],
                    'modules' => array_map(function($s) {
                        return $s['module_name'] . ' (' . $s['module_code'] . ')';
                    }, $schedules)
                ];
            }
        }
        
        return $conflicts;
    }
}
