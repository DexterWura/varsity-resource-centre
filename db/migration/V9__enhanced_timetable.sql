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

-- Insert sample data
INSERT INTO universities (name, code, country, website) VALUES
('University of Zimbabwe', 'UZ', 'Zimbabwe', 'https://www.uz.ac.zw'),
('National University of Science and Technology', 'NUST', 'Zimbabwe', 'https://www.nust.ac.zw'),
('Great Zimbabwe University', 'GZU', 'Zimbabwe', 'https://www.gzu.ac.zw'),
('Midlands State University', 'MSU', 'Zimbabwe', 'https://www.msu.ac.zw'),
('Chinhoyi University of Technology', 'CUT', 'Zimbabwe', 'https://www.cut.ac.zw');

-- Insert sample faculties for UZ
INSERT INTO faculties (university_id, name, code, description) VALUES
(1, 'Faculty of Engineering', 'ENG', 'Engineering and Technology programs'),
(1, 'Faculty of Science', 'SCI', 'Natural and Physical Sciences'),
(1, 'Faculty of Commerce', 'COM', 'Business and Commerce programs'),
(1, 'Faculty of Arts', 'ART', 'Humanities and Arts programs'),
(1, 'Faculty of Medicine', 'MED', 'Medical and Health Sciences');

-- Insert sample semesters for UZ
INSERT INTO semesters (university_id, name, code, start_date, end_date, is_current) VALUES
(1, 'First Semester 2024', 'SEM1-2024', '2024-01-15', '2024-05-15', TRUE),
(1, 'Second Semester 2024', 'SEM2-2024', '2024-06-01', '2024-10-15', FALSE),
(1, 'First Semester 2025', 'SEM1-2025', '2025-01-15', '2025-05-15', FALSE);

-- Insert sample modules for Engineering Faculty
INSERT INTO modules (faculty_id, name, code, credits, description) VALUES
(1, 'Introduction to Programming', 'CS101', 3, 'Basic programming concepts and algorithms'),
(1, 'Data Structures and Algorithms', 'CS201', 4, 'Advanced data structures and algorithm design'),
(1, 'Database Systems', 'CS301', 3, 'Database design and management'),
(1, 'Software Engineering', 'CS401', 4, 'Software development methodologies'),
(1, 'Computer Networks', 'CS302', 3, 'Network protocols and architecture'),
(1, 'Operating Systems', 'CS303', 4, 'OS concepts and implementation'),
(1, 'Web Development', 'CS304', 3, 'Modern web technologies and frameworks'),
(1, 'Mobile Application Development', 'CS305', 3, 'Mobile app development for iOS and Android');

-- Insert sample module schedules
INSERT INTO module_schedules (module_id, semester_id, day_of_week, start_time, end_time, venue, lecturer, schedule_type) VALUES
(1, 1, 'Monday', '08:00:00', '10:00:00', 'Lecture Hall 1', 'Dr. Smith', 'Lecture'),
(1, 1, 'Wednesday', '10:00:00', '12:00:00', 'Computer Lab 1', 'Dr. Smith', 'Practical'),
(2, 1, 'Tuesday', '08:00:00', '10:00:00', 'Lecture Hall 2', 'Prof. Johnson', 'Lecture'),
(2, 1, 'Thursday', '14:00:00', '16:00:00', 'Computer Lab 2', 'Prof. Johnson', 'Tutorial'),
(3, 1, 'Monday', '14:00:00', '16:00:00', 'Lecture Hall 3', 'Dr. Brown', 'Lecture'),
(3, 1, 'Friday', '10:00:00', '12:00:00', 'Database Lab', 'Dr. Brown', 'Practical'),
(4, 1, 'Tuesday', '10:00:00', '12:00:00', 'Lecture Hall 1', 'Dr. Wilson', 'Lecture'),
(4, 1, 'Thursday', '08:00:00', '10:00:00', 'Software Lab', 'Dr. Wilson', 'Seminar'),
(5, 1, 'Wednesday', '14:00:00', '16:00:00', 'Lecture Hall 2', 'Dr. Davis', 'Lecture'),
(5, 1, 'Friday', '14:00:00', '16:00:00', 'Network Lab', 'Dr. Davis', 'Practical'),
(6, 1, 'Monday', '10:00:00', '12:00:00', 'Lecture Hall 3', 'Prof. Miller', 'Lecture'),
(6, 1, 'Wednesday', '08:00:00', '10:00:00', 'OS Lab', 'Prof. Miller', 'Tutorial'),
(7, 1, 'Tuesday', '14:00:00', '16:00:00', 'Lecture Hall 1', 'Dr. Garcia', 'Lecture'),
(7, 1, 'Thursday', '10:00:00', '12:00:00', 'Web Lab', 'Dr. Garcia', 'Practical'),
(8, 1, 'Friday', '08:00:00', '10:00:00', 'Lecture Hall 2', 'Dr. Martinez', 'Lecture'),
(8, 1, 'Monday', '16:00:00', '18:00:00', 'Mobile Lab', 'Dr. Martinez', 'Practical');
