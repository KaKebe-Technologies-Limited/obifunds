-- ============================================================
-- CHAMAFUNDS - Complete Database Setup with Dummy Data
-- ============================================================
-- Run this script in your SQL environment (MySQL / PostgreSQL)
-- It creates the database, all tables, and seeds them with
-- realistic sample data for testing.
-- ============================================================

-- ============================================================
-- 1. CREATE DATABASE
-- ============================================================

DROP DATABASE IF EXISTS chamafunds;
CREATE DATABASE chamafunds;
USE chamafunds;

-- ============================================================
-- 2. USERS TABLE
-- ============================================================

CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','campaigner','donor') NOT NULL DEFAULT 'donor',
    country VARCHAR(50),
    avatar_url VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    is_verified BOOLEAN DEFAULT FALSE,
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_email (email),
    INDEX idx_users_phone (phone),
    INDEX idx_users_role (role)
);

-- ============================================================
-- 3. COUNTRIES TABLE
-- ============================================================

CREATE TABLE countries (
    country_id INT PRIMARY KEY AUTO_INCREMENT,
    country_name VARCHAR(100) NOT NULL UNIQUE,
    country_code VARCHAR(2) NOT NULL UNIQUE,
    currency_code VARCHAR(3) NOT NULL,
    currency_symbol VARCHAR(5) NOT NULL,
    payment_partner VARCHAR(50),
    api_config JSON,
    is_active BOOLEAN DEFAULT TRUE,
    campaign_count INT DEFAULT 0,
    user_count INT DEFAULT 0,
    fee_percentage DECIMAL(5,2) DEFAULT 7.50,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- 4. CAMPAIGNS TABLE
-- ============================================================

CREATE TABLE campaigns (
    campaign_id INT PRIMARY KEY AUTO_INCREMENT,
    campaigner_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    category VARCHAR(50) NOT NULL,
    goal_amount DECIMAL(15,2) NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'UGX',
    raised_amount DECIMAL(15,2) DEFAULT 0,
    contributor_count INT DEFAULT 0,
    image_url VARCHAR(255),
    mobile_money_number VARCHAR(20) NOT NULL,
    mobile_money_network VARCHAR(50) NOT NULL,
    status ENUM('draft','active','paused','suspended','completed','flagged') DEFAULT 'draft',
    start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_date TIMESTAMP NULL,
    country VARCHAR(50) NOT NULL,
    is_featured BOOLEAN DEFAULT FALSE,
    view_count INT DEFAULT 0,
    share_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (campaigner_id) REFERENCES users(user_id),
    INDEX idx_campaigns_slug (slug),
    INDEX idx_campaigns_status (status),
    INDEX idx_campaigns_category (category),
    INDEX idx_campaigns_country (country),
    INDEX idx_campaigns_end_date (end_date)
);

-- ============================================================
-- 5. DONATIONS TABLE
-- ============================================================

CREATE TABLE donations (
    donation_id INT PRIMARY KEY AUTO_INCREMENT,
    campaign_id INT NOT NULL,
    donor_id INT NULL,
    donor_name VARCHAR(100),
    donor_email VARCHAR(100),
    donor_phone VARCHAR(20) NOT NULL,
    is_anonymous BOOLEAN DEFAULT FALSE,
    amount DECIMAL(15,2) NOT NULL,
    fee_percentage DECIMAL(5,2) NOT NULL DEFAULT 7.50,
    fee_amount DECIMAL(15,2) GENERATED ALWAYS AS (amount * (fee_percentage/100)) STORED,
    net_amount DECIMAL(15,2) GENERATED ALWAYS AS (amount - (amount * (fee_percentage/100))) STORED,
    tip_amount DECIMAL(15,2) DEFAULT 0,
    status ENUM('pending','completed','failed','refunded') DEFAULT 'pending',
    transaction_reference VARCHAR(100),
    mobile_money_network VARCHAR(50) NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(campaign_id),
    FOREIGN KEY (donor_id) REFERENCES users(user_id),
    INDEX idx_donations_campaign (campaign_id),
    INDEX idx_donations_donor (donor_id),
    INDEX idx_donations_status (status),
    INDEX idx_donations_created (created_at)
);

-- ============================================================
-- 6. WITHDRAWALS TABLE
-- ============================================================

CREATE TABLE withdrawals (
    withdrawal_id INT PRIMARY KEY AUTO_INCREMENT,
    campaign_id INT NOT NULL,
    campaigner_id INT NOT NULL,
    gross_amount DECIMAL(15,2) NOT NULL,
    fee_percentage DECIMAL(5,2) NOT NULL DEFAULT 7.50,
    fee_amount DECIMAL(15,2) GENERATED ALWAYS AS (gross_amount * (fee_percentage/100)) STORED,
    net_amount DECIMAL(15,2) GENERATED ALWAYS AS (gross_amount - (gross_amount * (fee_percentage/100))) STORED,
    mobile_money_number VARCHAR(20) NOT NULL,
    mobile_money_network VARCHAR(50) NOT NULL,
    status ENUM('pending','approved','completed','rejected') DEFAULT 'pending',
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT,
    admin_notes TEXT,
    transaction_reference VARCHAR(100),
    completed_at TIMESTAMP NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(campaign_id),
    FOREIGN KEY (campaigner_id) REFERENCES users(user_id),
    FOREIGN KEY (approved_by) REFERENCES users(user_id),
    INDEX idx_withdrawals_campaign (campaign_id),
    INDEX idx_withdrawals_status (status)
);

-- ============================================================
-- 7. PLEDGES TABLE
-- ============================================================

CREATE TABLE pledges (
    pledge_id INT PRIMARY KEY AUTO_INCREMENT,
    campaign_id INT NOT NULL,
    pledger_name VARCHAR(100) NOT NULL,
    pledger_phone VARCHAR(20),
    pledger_email VARCHAR(100),
    amount DECIMAL(15,2) NOT NULL,
    status ENUM('pending','paid','reminded','cancelled') DEFAULT 'pending',
    reminder_sent_count INT DEFAULT 0,
    last_reminder_sent TIMESTAMP NULL,
    paid_donation_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(campaign_id),
    FOREIGN KEY (paid_donation_id) REFERENCES donations(donation_id),
    INDEX idx_pledges_campaign (campaign_id),
    INDEX idx_pledges_status (status)
);

-- ============================================================
-- 8. ADMIN LOGS TABLE
-- ============================================================

CREATE TABLE admin_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(50),
    target_id INT,
    target_name VARCHAR(255),
    changes JSON,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(user_id),
    INDEX idx_logs_admin (admin_id),
    INDEX idx_logs_target (target_type, target_id),
    INDEX idx_logs_created (created_at)
);

-- ============================================================
-- 9. NOTIFICATIONS TABLE
-- ============================================================

CREATE TABLE notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    sent_via_email BOOLEAN DEFAULT FALSE,
    sent_via_sms BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    INDEX idx_notifications_user (user_id),
    INDEX idx_notifications_read (is_read)
);

-- ============================================================
-- 10. PLATFORM SETTINGS TABLE
-- ============================================================

CREATE TABLE platform_settings (
    setting_id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    setting_group VARCHAR(50) DEFAULT 'general',
    is_encrypted BOOLEAN DEFAULT FALSE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- 11. SEED DATA - USERS (4 Users)
-- ============================================================

INSERT INTO users (full_name, email, phone, password_hash, role, country, avatar_url, is_active, is_verified) VALUES
('Admin Kakebe', 'admin@chamafunds.com', '256700000001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Uganda', 'https://i.pravatar.cc/150?img=1', TRUE, TRUE),
('Sarah Nakato', 'sarah.nakato@gmail.com', '256712345678', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'campaigner', 'Uganda', 'https://i.pravatar.cc/150?img=2', TRUE, TRUE),
('John Mwangi', 'john.mwangi@gmail.com', '254712345678', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'campaigner', 'Kenya', 'https://i.pravatar.cc/150?img=3', TRUE, TRUE),
('Grace Achieng', 'grace.achieng@gmail.com', '256789012345', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'donor', 'Uganda', 'https://i.pravatar.cc/150?img=4', TRUE, TRUE);

-- ============================================================
-- 12. SEED DATA - COUNTRIES (4 Countries)
-- ============================================================

INSERT INTO countries (country_name, country_code, currency_code, currency_symbol, payment_partner, is_active, fee_percentage) VALUES
('Uganda', 'UG', 'UGX', 'UGX', 'PawaPay', TRUE, 7.50),
('Kenya', 'KE', 'KES', 'KSh', 'PawaPay', TRUE, 7.50),
('Rwanda', 'RW', 'RWF', 'RWF', 'PawaPay', TRUE, 7.50),
('Nigeria', 'NG', 'NGN', '₦', 'PawaPay', TRUE, 7.50);

-- ============================================================
-- 13. SEED DATA - CAMPAIGNS (4 Campaigns)
-- ============================================================

INSERT INTO campaigns (
    campaigner_id, title, slug, description, category, goal_amount, 
    currency, raised_amount, contributor_count, image_url, 
    mobile_money_number, mobile_money_network, status, 
    country, is_featured, view_count, share_count
) VALUES
(
    2, 'Family Medical Fund for Baby Grace', 'family-medical-fund-baby-grace',
    'Our daughter Grace was born with a condition that requires immediate surgery. The total cost is UGX 5,000,000. We have raised some funds from family but we need help from our wider community. Every contribution brings us closer to saving our baby girl.',
    'Medical', 5000000, 'UGX', 3750000, 45,
    'https://images.unsplash.com/photo-1582750433449-648ed127bb54?w=800',
    '256712345678', 'MTN Mobile Money', 'active',
    'Uganda', TRUE, 1240, 89
),
(
    3, 'Clean Water Borehole for Kibera Community', 'clean-water-borehole-kibera',
    'Access to clean water is a daily challenge for the Kibera community. We are raising funds to drill a borehole that will serve over 500 families. This project will transform lives and provide sustainable access to safe drinking water.',
    'Community', 8000000, 'KES', 6200000, 78,
    'https://images.unsplash.com/photo-1548839140-29a749e1cf4d?w=800',
    '254712345678', 'Airtel Money', 'active',
    'Kenya', TRUE, 2300, 156
),
(
    2, 'Education Scholarship for 10 Bright Students', 'education-scholarship-10-students',
    'We are raising funds to provide full scholarships for 10 academically gifted students from low-income families. Each scholarship covers tuition, books, and supplies for one academic year. Help us invest in the future leaders of Uganda.',
    'Education', 3000000, 'UGX', 2850000, 62,
    'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=800',
    '256789012345', 'MTN Mobile Money', 'active',
    'Uganda', FALSE, 876, 43
),
(
    3, 'Emergency Flood Relief for Kisumu Families', 'emergency-flood-relief-kisumu',
    'Heavy rains have caused devastating floods in Kisumu, displacing over 200 families. We are raising emergency funds to provide food, shelter, and essential supplies to those affected. Every donation makes a difference in helping these families rebuild.',
    'Emergency', 10000000, 'KES', 8300000, 112,
    'https://images.unsplash.com/photo-1534274988757-a28bf1a57c17?w=800',
    '254789012345', 'Safaricom M-PESA', 'active',
    'Kenya', FALSE, 3456, 201
);

-- ============================================================
-- 14. SEED DATA - DONATIONS (4 Donations per campaign = 16 total)
-- ============================================================

INSERT INTO donations (campaign_id, donor_id, donor_name, donor_email, donor_phone, is_anonymous, amount, fee_percentage, tip_amount, status, transaction_reference, mobile_money_network, payment_date) VALUES
-- Campaign 1: Family Medical Fund (3 donations)
(1, 4, 'Grace Achieng', 'grace.achieng@gmail.com', '256789012345', FALSE, 250000, 7.50, 0, 'completed', 'MMT-UG-2026-001', 'MTN Mobile Money', '2026-01-15 10:30:00'),
(1, NULL, 'Peter Okello', 'peter.okello@gmail.com', '256701234567', FALSE, 100000, 7.50, 5000, 'completed', 'MMT-UG-2026-002', 'MTN Mobile Money', '2026-01-16 14:20:00'),
(1, NULL, 'Anonymous Donor', NULL, '256702345678', TRUE, 50000, 7.50, 0, 'completed', 'MMT-UG-2026-003', 'Airtel Money', '2026-01-17 09:45:00'),
(1, NULL, 'James Ssemakula', 'james.ssemakula@gmail.com', '256703456789', FALSE, 75000, 7.50, 2000, 'pending', 'MMT-UG-2026-004', 'MTN Mobile Money', '2026-01-18 16:10:00'),
-- Campaign 2: Clean Water Borehole (3 donations)
(2, NULL, 'David Mwangi', 'david.mwangi@gmail.com', '254723456789', FALSE, 500000, 7.50, 0, 'completed', 'MMT-KE-2026-005', 'Safaricom M-PESA', '2026-01-14 11:00:00'),
(2, NULL, 'Alice Wanjiku', 'alice.wanjiku@gmail.com', '254734567890', FALSE, 300000, 7.50, 10000, 'completed', 'MMT-KE-2026-006', 'Airtel Money', '2026-01-15 08:30:00'),
(2, NULL, 'Anonymous Donor', NULL, '254745678901', TRUE, 200000, 7.50, 0, 'completed', 'MMT-KE-2026-007', 'Safaricom M-PESA', '2026-01-16 13:15:00'),
(2, NULL, 'Samuel Kiprop', 'samuel.kiprop@gmail.com', '254756789012', FALSE, 150000, 7.50, 0, 'pending', 'MMT-KE-2026-008', 'Airtel Money', '2026-01-17 10:45:00'),
-- Campaign 3: Education Scholarship (3 donations)
(3, 4, 'Grace Achieng', 'grace.achieng@gmail.com', '256789012345', FALSE, 200000, 7.50, 5000, 'completed', 'MMT-UG-2026-009', 'MTN Mobile Money', '2026-01-12 09:00:00'),
(3, NULL, 'Martha Nambooze', 'martha.nambooze@gmail.com', '256707890123', FALSE, 150000, 7.50, 0, 'completed', 'MMT-UG-2026-010', 'Airtel Money', '2026-01-13 11:30:00'),
(3, NULL, 'Anonymous Donor', NULL, '256708901234', TRUE, 100000, 7.50, 0, 'completed', 'MMT-UG-2026-011', 'MTN Mobile Money', '2026-01-14 15:20:00'),
(3, NULL, 'Robert Kato', 'robert.kato@gmail.com', '256709012345', FALSE, 80000, 7.50, 3000, 'pending', 'MMT-UG-2026-012', 'MTN Mobile Money', '2026-01-15 12:00:00'),
-- Campaign 4: Emergency Flood Relief (3 donations)
(4, NULL, 'Faith Akinyi', 'faith.akinyi@gmail.com', '254767890123', FALSE, 1000000, 7.50, 20000, 'completed', 'MMT-KE-2026-013', 'Safaricom M-PESA', '2026-01-10 07:00:00'),
(4, NULL, 'Anonymous Donor', NULL, '254778901234', TRUE, 500000, 7.50, 0, 'completed', 'MMT-KE-2026-014', 'Airtel Money', '2026-01-11 09:30:00'),
(4, NULL, 'Joseph Odhiambo', 'joseph.odhiambo@gmail.com', '254789012345', FALSE, 250000, 7.50, 0, 'completed', 'MMT-KE-2026-015', 'Safaricom M-PESA', '2026-01-12 14:45:00'),
(4, NULL, 'Dorothy Atieno', 'dorothy.atieno@gmail.com', '254790123456', FALSE, 300000, 7.50, 5000, 'pending', 'MMT-KE-2026-016', 'Airtel Money', '2026-01-13 08:15:00');

-- ============================================================
-- 15. SEED DATA - PLEDGES (4 Pledges)
-- ============================================================

INSERT INTO pledges (campaign_id, pledger_name, pledger_phone, pledger_email, amount, status, reminder_sent_count, last_reminder_sent, created_at) VALUES
(1, 'Mary Adong', '256798012345', 'mary.adong@gmail.com', 50000, 'pending', 1, '2026-01-18 08:00:00', '2026-01-10 12:00:00'),
(1, 'John Opio', '256799123456', 'john.opio@gmail.com', 75000, 'reminded', 2, '2026-01-19 09:00:00', '2026-01-09 14:30:00'),
(2, 'Esther Kamau', '254780123456', 'esther.kamau@gmail.com', 100000, 'pending', 0, NULL, '2026-01-12 10:00:00'),
(3, 'David Balidawa', '256701234567', 'david.balidawa@gmail.com', 50000, 'paid', 1, '2026-01-14 08:00:00', '2026-01-05 16:00:00');

-- ============================================================
-- 16. SEED DATA - WITHDRAWALS (4 Withdrawals)
-- ============================================================

INSERT INTO withdrawals (campaign_id, campaigner_id, gross_amount, fee_percentage, mobile_money_number, mobile_money_network, status, approved_by, approved_at, rejection_reason, admin_notes, transaction_reference, completed_at, requested_at) VALUES
(1, 2, 500000, 7.50, '256712345678', 'MTN Mobile Money', 'completed', 1, '2026-01-20 10:00:00', NULL, 'Approved - first withdrawal', 'MMT-WD-UG-2026-001', '2026-01-20 12:00:00', '2026-01-19 08:00:00'),
(1, 2, 250000, 7.50, '256712345678', 'MTN Mobile Money', 'approved', 1, '2026-01-22 09:00:00', NULL, 'Approved - second withdrawal', NULL, NULL, '2026-01-21 11:00:00'),
(2, 3, 800000, 7.50, '254712345678', 'Safaricom M-PESA', 'pending', NULL, NULL, NULL, 'Awaiting approval', NULL, NULL, '2026-01-22 14:30:00'),
(3, 2, 300000, 7.50, '256789012345', 'MTN Mobile Money', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-23 09:00:00');

-- ============================================================
-- 17. SEED DATA - ADMIN LOGS (4 Logs)
-- ============================================================

INSERT INTO admin_logs (admin_id, action, target_type, target_id, target_name, changes, ip_address, user_agent) VALUES
(1, 'Approved Withdrawal', 'withdrawal', 1, 'Family Medical Fund - UGX 500,000', '{"status": "pending", "new_status": "approved"}', '192.168.1.100', 'Chrome/120.0.0.0'),
(1, 'Approved Withdrawal', 'withdrawal', 2, 'Family Medical Fund - UGX 250,000', '{"status": "pending", "new_status": "approved"}', '192.168.1.100', 'Chrome/120.0.0.0'),
(1, 'Logged In', 'user', 1, 'Admin Kakebe', '{"ip": "192.168.1.100"}', '192.168.1.100', 'Chrome/120.0.0.0'),
(1, 'Featured Campaign', 'campaign', 1, 'Family Medical Fund for Baby Grace', '{"is_featured": "false", "new_is_featured": "true"}', '192.168.1.100', 'Chrome/120.0.0.0');

-- ============================================================
-- 18. SEED DATA - NOTIFICATIONS (4 Notifications)
-- ============================================================

INSERT INTO notifications (user_id, type, title, message, link, is_read, sent_via_email, sent_via_sms, created_at) VALUES
(2, 'donation', 'New Donation Received!', 'Grace Achieng just donated UGX 250,000 to your campaign "Family Medical Fund for Baby Grace"', '/campaign/1', FALSE, TRUE, FALSE, '2026-01-15 10:31:00'),
(2, 'withdrawal', 'Withdrawal Approved', 'Your withdrawal of UGX 500,000 for "Family Medical Fund for Baby Grace" has been approved and is being processed.', '/withdrawals', FALSE, TRUE, TRUE, '2026-01-20 10:05:00'),
(3, 'donation', 'New Donation Received!', 'Anonymous Donor just donated KES 200,000 to your campaign "Clean Water Borehole for Kibera Community"', '/campaign/2', FALSE, TRUE, FALSE, '2026-01-16 13:16:00'),
(4, 'donation', 'Donation Confirmed', 'Your donation of UGX 250,000 to "Family Medical Fund for Baby Grace" was successful. Thank you!', '/campaign/1', TRUE, TRUE, TRUE, '2026-01-15 10:32:00');

-- ============================================================
-- 19. SEED DATA - PLATFORM SETTINGS
-- ============================================================

INSERT INTO platform_settings (setting_key, setting_value, setting_group) VALUES
('platform_name', 'ChamaFunds', 'general'),
('platform_tagline', 'Pool Money Together for What Matters Most', 'general'),
('platform_email', 'support@chamafunds.com', 'general'),
('platform_phone', '+256700000001', 'general'),
('platform_fee', '7.5', 'fees'),
('fee_applied_at', 'withdrawal', 'fees'),
('maintenance_mode', 'false', 'security'),
('max_donation_amount', '1000000', 'payments'),
('min_donation_amount', '1000', 'payments'),
('session_timeout', '60', 'security'),
('default_country', 'Uganda', 'general'),
('two_factor_enabled', 'false', 'security'),
('email_notifications_enabled', 'true', 'notifications'),
('sms_notifications_enabled', 'true', 'notifications'),
('default_currency', 'UGX', 'general');

-- ============================================================
-- 20. UPDATE COUNTERS
-- ============================================================

-- Update campaign counts
UPDATE campaigns SET 
    contributor_count = (
        SELECT COUNT(*) FROM donations WHERE donations.campaign_id = campaigns.campaign_id AND donations.status = 'completed'
    ),
    raised_amount = (
        SELECT COALESCE(SUM(amount), 0) FROM donations WHERE donations.campaign_id = campaigns.campaign_id AND donations.status = 'completed'
    ),
    updated_at = CURRENT_TIMESTAMP
WHERE campaign_id IN (1, 2, 3, 4);

-- Update country counts
UPDATE countries SET 
    campaign_count = (SELECT COUNT(*) FROM campaigns WHERE campaigns.country = countries.country_name AND campaigns.status IN ('active', 'completed')),
    user_count = (SELECT COUNT(*) FROM users WHERE users.country = countries.country_name)
WHERE country_id IN (1, 2, 3, 4);

-- ============================================================
-- 21. VERIFICATION QUERIES (Run these to test your data)
-- ============================================================

-- SELECT 'Users Count:' AS '', COUNT(*) FROM users;
-- SELECT 'Countries Count:' AS '', COUNT(*) FROM countries;
-- SELECT 'Campaigns Count:' AS '', COUNT(*) FROM campaigns;
-- SELECT 'Donations Count:' AS '', COUNT(*) FROM donations;
-- SELECT 'Pledges Count:' AS '', COUNT(*) FROM pledges;
-- SELECT 'Withdrawals Count:' AS '', COUNT(*) FROM withdrawals;
-- SELECT 'Admin Logs Count:' AS '', COUNT(*) FROM admin_logs;
-- SELECT 'Notifications Count:' AS '', COUNT(*) FROM notifications;
-- SELECT 'Platform Settings Count:' AS '', COUNT(*) FROM platform_settings;

-- ============================================================
-- END OF SCRIPT
-- ============================================================