-- Kidzenia Student Management Enhanced Database Schema
-- This file extends the existing schema with comprehensive student management features

USE kidzenia_db;

-- ============================================
-- ACADEMIC PROGRESS TRACKING TABLES
-- ============================================

-- Subjects/Skills for Kindergarten
CREATE TABLE subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) UNIQUE NOT NULL,
    description TEXT,
    category ENUM('academic', 'creative', 'physical', 'social', 'language') DEFAULT 'academic',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default kindergarten subjects
INSERT INTO subjects (name, code, description, category) VALUES 
('Language Development', 'LANG', 'Speaking, listening, and early literacy skills', 'language'),
('Mathematics', 'MATH', 'Numbers, shapes, patterns, and basic math concepts', 'academic'),
('Science & Discovery', 'SCI', 'Exploration, observation, and basic scientific concepts', 'academic'),
('Art & Creativity', 'ART', 'Drawing, painting, crafts, and creative expression', 'creative'),
('Music & Movement', 'MUSIC', 'Singing, dancing, and rhythmic activities', 'creative'),
('Physical Education', 'PE', 'Gross motor skills, coordination, and physical fitness', 'physical'),
('Social Skills', 'SOCIAL', 'Interaction, sharing, cooperation, and emotional development', 'social'),
('Fine Motor Skills', 'MOTOR', 'Hand-eye coordination, writing preparation, and manipulation skills', 'physical');

-- Assessment Criteria
CREATE TABLE assessment_criteria (
    id INT PRIMARY KEY AUTO_INCREMENT,
    subject_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    max_score DECIMAL(5,2) DEFAULT 100.00,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

-- Student Progress Tracking
CREATE TABLE student_progress (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    assessment_criteria_id INT NOT NULL,
    term ENUM('term1', 'term2', 'term3') NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    score DECIMAL(5,2),
    remarks TEXT,
    teacher_id INT,
    assessment_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (assessment_criteria_id) REFERENCES assessment_criteria(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_progress (student_id, subject_id, assessment_criteria_id, term, academic_year)
);

-- Student Portfolio
CREATE TABLE student_portfolio (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    file_path VARCHAR(255),
    file_type ENUM('image', 'document', 'video', 'audio') DEFAULT 'image',
    category ENUM('artwork', 'project', 'achievement', 'certificate', 'other') DEFAULT 'artwork',
    teacher_id INT,
    portfolio_date DATE NOT NULL,
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================
-- BEHAVIOR AND CONDUCT TRACKING TABLES
-- ============================================

-- Behavior Categories
CREATE TABLE behavior_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    type ENUM('positive', 'negative', 'neutral') DEFAULT 'neutral',
    point_value INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default behavior categories
INSERT INTO behavior_categories (name, description, type, point_value) VALUES 
('Excellent Behavior', 'Outstanding conduct and cooperation', 'positive', 5),
('Good Behavior', 'Positive attitude and good conduct', 'positive', 3),
('Helpful', 'Assisting others and showing kindness', 'positive', 2),
('Participation', 'Active participation in activities', 'positive', 1),
('Disruptive Behavior', 'Disturbing class activities', 'negative', -2),
('Not Following Instructions', 'Ignoring teacher directions', 'negative', -3),
('Aggressive Behavior', 'Physical or verbal aggression', 'negative', -5);

-- Behavior Records
CREATE TABLE behavior_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    behavior_category_id INT NOT NULL,
    description TEXT,
    incident_date DATE NOT NULL,
    incident_time TIME,
    location VARCHAR(100),
    reported_by INT NOT NULL,
    action_taken TEXT,
    parent_notified BOOLEAN DEFAULT FALSE,
    points_earned INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (behavior_category_id) REFERENCES behavior_categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE RESTRICT
);

-- ============================================
-- HEALTH AND MEDICAL MANAGEMENT TABLES
-- ============================================

-- Medical Visits
CREATE TABLE medical_visits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    visit_date DATE NOT NULL,
    visit_time TIME NOT NULL,
    reason VARCHAR(200) NOT NULL,
    symptoms TEXT,
    diagnosis TEXT,
    treatment_given TEXT,
    medication_administered TEXT,
    follow_up_required BOOLEAN DEFAULT FALSE,
    follow_up_notes TEXT,
    staff_id INT NOT NULL,
    parent_notified BOOLEAN DEFAULT FALSE,
    notification_time TIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE RESTRICT
);

-- Vaccination Records
CREATE TABLE vaccination_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    vaccine_name VARCHAR(100) NOT NULL,
    vaccine_type VARCHAR(100),
    dose_number INT,
    administration_date DATE NOT NULL,
    administered_by VARCHAR(100),
    next_due_date DATE,
    batch_number VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Allergy Management
CREATE TABLE allergy_management (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    allergen VARCHAR(100) NOT NULL,
    allergy_type ENUM('food', 'medication', 'environmental', 'other') DEFAULT 'food',
    severity ENUM('mild', 'moderate', 'severe', 'life_threatening') DEFAULT 'moderate',
    symptoms TEXT,
    emergency_action TEXT,
    medication_required VARCHAR(100),
    last_updated DATE,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================
-- PARENT COMMUNICATION TABLES
-- ============================================

-- Communication Logs
CREATE TABLE communication_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    communication_type ENUM('phone', 'email', 'meeting', 'note', 'portal_message') DEFAULT 'portal_message',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    status ENUM('sent', 'delivered', 'read', 'replied') DEFAULT 'sent',
    parent_response TEXT,
    follow_up_required BOOLEAN DEFAULT FALSE,
    follow_up_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE RESTRICT
);

-- Meeting Schedules
CREATE TABLE parent_meetings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    teacher_id INT NOT NULL,
    parent_id INT NOT NULL,
    meeting_date DATE NOT NULL,
    meeting_time TIME NOT NULL,
    duration_minutes INT DEFAULT 30,
    meeting_type ENUM('regular', 'concern', 'progress', 'emergency') DEFAULT 'regular',
    agenda TEXT,
    location VARCHAR(100),
    status ENUM('scheduled', 'completed', 'cancelled', 'rescheduled') DEFAULT 'scheduled',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE RESTRICT
);

-- ============================================
-- STUDENT DOCUMENTS AND REPORTS TABLES
-- ============================================

-- Student Documents
CREATE TABLE student_documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    document_type ENUM('birth_certificate', 'medical_form', 'immunization_record', 'permission_slip', 'report_card', 'other') DEFAULT 'other',
    title VARCHAR(200) NOT NULL,
    description TEXT,
    file_path VARCHAR(255) NOT NULL,
    file_size INT,
    file_type VARCHAR(50),
    upload_date DATE NOT NULL,
    expiry_date DATE,
    is_required BOOLEAN DEFAULT FALSE,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Report Cards
CREATE TABLE report_cards (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    term ENUM('term1', 'term2', 'term3') NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    overall_grade VARCHAR(10),
    class_rank INT,
    total_students INT,
    teacher_comments TEXT,
    principal_comments TEXT,
    attendance_percentage DECIMAL(5,2),
    conduct_grade VARCHAR(10),
    generated_by INT,
    generated_date DATE NOT NULL,
    parent_viewed BOOLEAN DEFAULT FALSE,
    parent_viewed_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_report_card (student_id, term, academic_year)
);

-- ============================================
-- FEE MANAGEMENT TABLES
-- ============================================

-- Fee Types
CREATE TABLE fee_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    amount DECIMAL(10,2) NOT NULL,
    fee_category ENUM('tuition', 'transport', 'uniform', 'books', 'activities', 'other') DEFAULT 'tuition',
    billing_cycle ENUM('monthly', 'quarterly', 'semester', 'yearly', 'one_time') DEFAULT 'monthly',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Student Fee Assignments
CREATE TABLE student_fee_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    fee_type_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    due_date DATE,
    academic_year VARCHAR(20),
    status ENUM('pending', 'partial', 'paid', 'waived', 'overdue') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (fee_type_id) REFERENCES fee_types(id) ON DELETE RESTRICT
);

-- Fee Payments
CREATE TABLE fee_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_fee_assignment_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method ENUM('cash', 'check', 'bank_transfer', 'online', 'card') DEFAULT 'cash',
    transaction_id VARCHAR(100),
    receipt_number VARCHAR(100),
    received_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_fee_assignment_id) REFERENCES student_fee_assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (received_by) REFERENCES users(id) ON DELETE RESTRICT
);

-- ============================================
-- TRANSPORTATION MANAGEMENT TABLES
-- ============================================

-- Bus Routes
CREATE TABLE bus_routes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    route_name VARCHAR(100) NOT NULL,
    route_number VARCHAR(20) UNIQUE NOT NULL,
    description TEXT,
    start_location VARCHAR(200) NOT NULL,
    end_location VARCHAR(200) NOT NULL,
    distance_km DECIMAL(6,2),
    estimated_duration_minutes INT,
    morning_departure_time TIME,
    afternoon_departure_time TIME,
    capacity INT DEFAULT 40,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bus Stops
CREATE TABLE bus_stops (
    id INT PRIMARY KEY AUTO_INCREMENT,
    route_id INT NOT NULL,
    stop_name VARCHAR(100) NOT NULL,
    stop_location VARCHAR(200) NOT NULL,
    stop_order INT NOT NULL,
    estimated_arrival_time TIME,
    estimated_departure_time TIME,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (route_id) REFERENCES bus_routes(id) ON DELETE CASCADE
);

-- Transportation Assignments
CREATE TABLE transportation_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    route_id INT NOT NULL,
    pickup_stop_id INT,
    dropoff_stop_id INT,
    service_type ENUM('morning', 'afternoon', 'both') DEFAULT 'both',
    start_date DATE NOT NULL,
    end_date DATE,
    monthly_fee DECIMAL(8,2),
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (route_id) REFERENCES bus_routes(id) ON DELETE CASCADE,
    FOREIGN KEY (pickup_stop_id) REFERENCES bus_stops(id) ON DELETE SET NULL,
    FOREIGN KEY (dropoff_stop_id) REFERENCES bus_stops(id) ON DELETE SET NULL
);

-- ============================================
-- INDEXES FOR PERFORMANCE OPTIMIZATION
-- ============================================

-- Academic Progress Indexes
CREATE INDEX idx_student_progress_student ON student_progress(student_id);
CREATE INDEX idx_student_progress_subject ON student_progress(subject_id);
CREATE INDEX idx_student_progress_term_year ON student_progress(term, academic_year);

-- Behavior Records Indexes
CREATE INDEX idx_behavior_student_date ON behavior_records(student_id, incident_date);
CREATE INDEX idx_behavior_category ON behavior_records(behavior_category_id);

-- Medical Visits Indexes
CREATE INDEX idx_medical_student_date ON medical_visits(student_id, visit_date);

-- Communication Indexes
CREATE INDEX idx_communication_student ON communication_logs(student_id);
CREATE INDEX idx_communication_date ON communication_logs(created_at);
CREATE INDEX idx_meeting_student_date ON parent_meetings(student_id, meeting_date);

-- Documents Indexes
CREATE INDEX idx_documents_student ON student_documents(student_id);
CREATE INDEX idx_documents_type ON student_documents(document_type);

-- Fee Management Indexes
CREATE INDEX idx_fee_assignment_student ON student_fee_assignments(student_id);
CREATE INDEX idx_fee_payment_assignment ON fee_payments(student_fee_assignment_id);
CREATE INDEX idx_fee_payment_date ON fee_payments(payment_date);

-- Transportation Indexes
CREATE INDEX idx_transport_student ON transportation_assignments(student_id);
CREATE INDEX idx_transport_route ON transportation_assignments(route_id);

-- ============================================
-- TRIGGERS FOR AUTOMATIC UPDATES
-- ============================================

-- Trigger to update fee assignment status when payments are made
DELIMITER //
CREATE TRIGGER update_fee_status_after_payment
AFTER INSERT ON fee_payments
FOR EACH ROW
BEGIN
    DECLARE total_paid DECIMAL(10,2);
    DECLARE total_amount DECIMAL(10,2);
    
    SELECT COALESCE(SUM(amount), 0) INTO total_paid
    FROM fee_payments
    WHERE student_fee_assignment_id = NEW.student_fee_assignment_id;
    
    SELECT amount INTO total_amount
    FROM student_fee_assignments
    WHERE id = NEW.student_fee_assignment_id;
    
    IF total_paid >= total_amount THEN
        UPDATE student_fee_assignments
        SET status = 'paid'
        WHERE id = NEW.student_fee_assignment_id;
    ELSEIF total_paid > 0 THEN
        UPDATE student_fee_assignments
        SET status = 'partial'
        WHERE id = NEW.student_fee_assignment_id;
    END IF;
END//
DELIMITER ;

-- ============================================
-- VIEWS FOR COMMON QUERIES
-- ============================================

-- Student Complete Profile View
CREATE VIEW student_complete_profile AS
SELECT 
    s.*,
    c.name as class_name,
    u.full_name as parent_name,
    u.email as parent_email,
    u.phone as parent_phone,
    TIMESTAMPDIFF(YEAR, s.date_of_birth, CURDATE()) as age
FROM students s
LEFT JOIN classes c ON s.class_id = c.id
LEFT JOIN users u ON s.parent_id = u.id
WHERE s.status = 'active';

-- Student Academic Summary View
CREATE VIEW student_academic_summary AS
SELECT 
    sp.student_id,
    sub.name as subject_name,
    sp.term,
    sp.academic_year,
    AVG(sp.score) as average_score,
    COUNT(sp.id) as assessment_count
FROM student_progress sp
JOIN subjects sub ON sp.subject_id = sub.id
GROUP BY sp.student_id, sub.name, sp.term, sp.academic_year;

-- Student Fee Status View
CREATE VIEW student_fee_status AS
SELECT 
    sfa.student_id,
    ft.name as fee_name,
    ft.fee_category,
    sfa.amount as total_amount,
    COALESCE(SUM(fp.amount), 0) as paid_amount,
    (sfa.amount - COALESCE(SUM(fp.amount), 0)) as balance,
    sfa.status,
    sfa.due_date
FROM student_fee_assignments sfa
JOIN fee_types ft ON sfa.fee_type_id = ft.id
LEFT JOIN fee_payments fp ON sfa.id = fp.student_fee_assignment_id
GROUP BY sfa.id, ft.name, ft.fee_category, sfa.amount, sfa.status, sfa.due_date;
