-- V16__business_lookup_tables.sql
-- Create lookup tables for business management

-- Cities table
CREATE TABLE IF NOT EXISTS cities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    country VARCHAR(100) DEFAULT 'South Africa',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Universities table
CREATE TABLE IF NOT EXISTS universities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL UNIQUE,
    abbreviation VARCHAR(20),
    city_id INT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE SET NULL,
    INDEX idx_name (name),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Campuses table
CREATE TABLE IF NOT EXISTS campuses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    university_id INT NOT NULL,
    address TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE CASCADE,
    UNIQUE KEY unique_campus_university (name, university_id),
    INDEX idx_university (university_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Business categories table
CREATE TABLE IF NOT EXISTS business_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Hours of operation table
CREATE TABLE IF NOT EXISTS business_hours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    business_id INT NOT NULL,
    day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NOT NULL,
    open_time TIME,
    close_time TIME,
    is_closed TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_business_day (business_id, day_of_week),
    INDEX idx_business (business_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add foreign key constraints to businesses table
ALTER TABLE businesses 
ADD COLUMN IF NOT EXISTS city_id INT,
ADD COLUMN IF NOT EXISTS university_id INT,
ADD COLUMN IF NOT EXISTS campus_id INT,
ADD COLUMN IF NOT EXISTS category_id INT,
ADD COLUMN IF NOT EXISTS contact_name VARCHAR(255),
ADD COLUMN IF NOT EXISTS hours_of_operation JSON;

-- Add foreign key constraints
ALTER TABLE businesses 
ADD CONSTRAINT fk_businesses_city FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE SET NULL,
ADD CONSTRAINT fk_businesses_university FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE SET NULL,
ADD CONSTRAINT fk_businesses_campus FOREIGN KEY (campus_id) REFERENCES campuses(id) ON DELETE SET NULL,
ADD CONSTRAINT fk_businesses_category FOREIGN KEY (category_id) REFERENCES business_categories(id) ON DELETE SET NULL;

-- Insert default data
INSERT IGNORE INTO cities (name) VALUES
('Johannesburg'),
('Cape Town'),
('Durban'),
('Pretoria'),
('Port Elizabeth'),
('Bloemfontein'),
('East London'),
('Pietermaritzburg'),
('Nelspruit'),
('Polokwane');

INSERT IGNORE INTO universities (name, abbreviation) VALUES
('University of Cape Town', 'UCT'),
('University of the Witwatersrand', 'Wits'),
('University of KwaZulu-Natal', 'UKZN'),
('University of Pretoria', 'UP'),
('Stellenbosch University', 'SU'),
('University of Johannesburg', 'UJ'),
('North-West University', 'NWU'),
('University of the Free State', 'UFS'),
('Rhodes University', 'RU'),
('University of the Western Cape', 'UWC');

INSERT IGNORE INTO business_categories (name, description) VALUES
('Food & Dining', 'Restaurants, cafes, food delivery'),
('Retail & Shopping', 'Stores, markets, shopping centers'),
('Health & Wellness', 'Medical, fitness, beauty services'),
('Education & Training', 'Schools, tutoring, courses'),
('Technology & IT', 'Software, hardware, tech services'),
('Transportation', 'Taxis, buses, delivery services'),
('Entertainment', 'Movies, games, events'),
('Professional Services', 'Legal, accounting, consulting'),
('Home & Garden', 'Furniture, decor, maintenance'),
('Automotive', 'Car sales, repairs, parts');

-- Insert some default campuses
INSERT IGNORE INTO campuses (name, university_id) VALUES
('Main Campus', 1),
('Upper Campus', 1),
('Lower Campus', 1),
('Main Campus', 2),
('East Campus', 2),
('West Campus', 2),
('Howard College', 3),
('Pietermaritzburg Campus', 3),
('Westville Campus', 3),
('Main Campus', 4);
