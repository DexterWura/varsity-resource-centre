-- Enhanced Timetable System
-- Migration V9: Enhanced Timetable with University, Faculty, and Module Management

-- Universities table
CREATE TABLE IF NOT EXISTS universities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(10) UNIQUE NOT NULL,
    country VARCHAR(100) DEFAULT 'Zimbabwe',
    website VARCHAR(255),
    logo_url VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Faculties table
CREATE TABLE IF NOT EXISTS faculties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    university_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(20) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE CASCADE,
    UNIQUE KEY unique_faculty_per_university (university_id, code)
);

-- Semesters table
CREATE TABLE IF NOT EXISTS semesters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    university_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_current BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE CASCADE,
    UNIQUE KEY unique_semester_per_university (university_id, code)
);

-- Modules table
CREATE TABLE IF NOT EXISTS modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    faculty_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(20) NOT NULL,
    credits INT DEFAULT 3,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (faculty_id) REFERENCES faculties(id) ON DELETE CASCADE,
    UNIQUE KEY unique_module_per_faculty (faculty_id, code)
);

-- Module schedules table (lecture times, venues, etc.)
CREATE TABLE IF NOT EXISTS module_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_id INT NOT NULL,
    semester_id INT NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    venue VARCHAR(255),
    lecturer VARCHAR(255),
    schedule_type ENUM('Lecture', 'Tutorial', 'Practical', 'Seminar') DEFAULT 'Lecture',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE CASCADE
);

-- Student timetables table
CREATE TABLE IF NOT EXISTS student_timetables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    university_id INT NOT NULL,
    semester_id INT NOT NULL,
    faculty_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE CASCADE,
    FOREIGN KEY (faculty_id) REFERENCES faculties(id) ON DELETE CASCADE
);

-- Student timetable modules (many-to-many relationship)
CREATE TABLE IF NOT EXISTS student_timetable_modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    timetable_id INT NOT NULL,
    module_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (timetable_id) REFERENCES student_timetables(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    UNIQUE KEY unique_timetable_module (timetable_id, module_id)
);

-- Insert sample data (only if not already exists)
INSERT IGNORE INTO universities (name, code, country, website) VALUES
('University of Zimbabwe', 'UZ', 'Zimbabwe', 'https://www.uz.ac.zw'),
('National University of Science and Technology', 'NUST', 'Zimbabwe', 'https://www.nust.ac.zw'),
('Great Zimbabwe University', 'GZU', 'Zimbabwe', 'https://www.gzu.ac.zw'),
('Midlands State University', 'MSU', 'Zimbabwe', 'https://www.msu.ac.zw'),
('Chinhoyi University of Technology', 'CUT', 'Zimbabwe', 'https://www.cut.ac.zw');

-- Insert sample faculties for UZ (only if not already exists)
INSERT IGNORE INTO faculties (university_id, name, code, description) 
SELECT id, 'Faculty of Engineering', 'ENG', 'Engineering and Technology programs' FROM universities WHERE code = 'UZ'
UNION ALL
SELECT id, 'Faculty of Science', 'SCI', 'Natural and Physical Sciences' FROM universities WHERE code = 'UZ'
UNION ALL
SELECT id, 'Faculty of Commerce', 'COM', 'Business and Commerce programs' FROM universities WHERE code = 'UZ'
UNION ALL
SELECT id, 'Faculty of Arts', 'ART', 'Humanities and Arts programs' FROM universities WHERE code = 'UZ'
UNION ALL
SELECT id, 'Faculty of Medicine', 'MED', 'Medical and Health Sciences' FROM universities WHERE code = 'UZ';

-- Insert sample semesters for UZ (only if not already exists)
INSERT IGNORE INTO semesters (university_id, name, code, start_date, end_date, is_current) 
SELECT id, 'First Semester 2024', 'SEM1-2024', '2024-01-15', '2024-05-15', TRUE FROM universities WHERE code = 'UZ'
UNION ALL
SELECT id, 'Second Semester 2024', 'SEM2-2024', '2024-06-01', '2024-10-15', FALSE FROM universities WHERE code = 'UZ'
UNION ALL
SELECT id, 'First Semester 2025', 'SEM1-2025', '2025-01-15', '2025-05-15', FALSE FROM universities WHERE code = 'UZ';

-- Insert sample modules for Engineering Faculty (only if not already exists)
INSERT IGNORE INTO modules (faculty_id, name, code, credits, description) 
SELECT f.id, 'Introduction to Programming', 'CS101', 3, 'Basic programming concepts and algorithms' 
FROM faculties f JOIN universities u ON f.university_id = u.id WHERE f.code = 'ENG' AND u.code = 'UZ'
UNION ALL
SELECT f.id, 'Data Structures and Algorithms', 'CS201', 4, 'Advanced data structures and algorithm design' 
FROM faculties f JOIN universities u ON f.university_id = u.id WHERE f.code = 'ENG' AND u.code = 'UZ'
UNION ALL
SELECT f.id, 'Database Systems', 'CS301', 3, 'Database design and management' 
FROM faculties f JOIN universities u ON f.university_id = u.id WHERE f.code = 'ENG' AND u.code = 'UZ'
UNION ALL
SELECT f.id, 'Software Engineering', 'CS401', 4, 'Software development methodologies' 
FROM faculties f JOIN universities u ON f.university_id = u.id WHERE f.code = 'ENG' AND u.code = 'UZ'
UNION ALL
SELECT f.id, 'Computer Networks', 'CS302', 3, 'Network protocols and architecture' 
FROM faculties f JOIN universities u ON f.university_id = u.id WHERE f.code = 'ENG' AND u.code = 'UZ'
UNION ALL
SELECT f.id, 'Operating Systems', 'CS303', 4, 'OS concepts and implementation' 
FROM faculties f JOIN universities u ON f.university_id = u.id WHERE f.code = 'ENG' AND u.code = 'UZ'
UNION ALL
SELECT f.id, 'Web Development', 'CS304', 3, 'Modern web technologies and frameworks' 
FROM faculties f JOIN universities u ON f.university_id = u.id WHERE f.code = 'ENG' AND u.code = 'UZ'
UNION ALL
SELECT f.id, 'Mobile Application Development', 'CS305', 3, 'Mobile app development for iOS and Android' 
FROM faculties f JOIN universities u ON f.university_id = u.id WHERE f.code = 'ENG' AND u.code = 'UZ';

-- Insert sample module schedules (only if not already exists)
INSERT IGNORE INTO module_schedules (module_id, semester_id, day_of_week, start_time, end_time, venue, lecturer, schedule_type) 
SELECT m.id, s.id, 'Monday', '08:00:00', '10:00:00', 'Lecture Hall 1', 'Dr. Smith', 'Lecture'
FROM modules m 
JOIN faculties f ON m.faculty_id = f.id 
JOIN universities u ON f.university_id = u.id
JOIN semesters s ON s.university_id = u.id
WHERE m.code = 'CS101' AND u.code = 'UZ' AND s.code = 'SEM1-2024'
UNION ALL
SELECT m.id, s.id, 'Wednesday', '10:00:00', '12:00:00', 'Computer Lab 1', 'Dr. Smith', 'Practical'
FROM modules m 
JOIN faculties f ON m.faculty_id = f.id 
JOIN universities u ON f.university_id = u.id
JOIN semesters s ON s.university_id = u.id
WHERE m.code = 'CS101' AND u.code = 'UZ' AND s.code = 'SEM1-2024'
UNION ALL
SELECT m.id, s.id, 'Tuesday', '08:00:00', '10:00:00', 'Lecture Hall 2', 'Prof. Johnson', 'Lecture'
FROM modules m 
JOIN faculties f ON m.faculty_id = f.id 
JOIN universities u ON f.university_id = u.id
JOIN semesters s ON s.university_id = u.id
WHERE m.code = 'CS201' AND u.code = 'UZ' AND s.code = 'SEM1-2024'
UNION ALL
SELECT m.id, s.id, 'Thursday', '14:00:00', '16:00:00', 'Computer Lab 2', 'Prof. Johnson', 'Tutorial'
FROM modules m 
JOIN faculties f ON m.faculty_id = f.id 
JOIN universities u ON f.university_id = u.id
JOIN semesters s ON s.university_id = u.id
WHERE m.code = 'CS201' AND u.code = 'UZ' AND s.code = 'SEM1-2024';
