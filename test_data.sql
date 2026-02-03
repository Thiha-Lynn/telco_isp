-- ============================================
-- T-LINK ISP TEST DATA
-- Created: December 23, 2025
-- ============================================

-- 1. PACKAGES (Internet Plans)
INSERT INTO packages (id, language_id, status, name, speed, time, feature, price, discount_price, created_at, updated_at) VALUES
(1, '1', '1', 'Basic Home', '10 Mbps', '1 Month', 'Up to 10 Mbps Download\nUp to 5 Mbps Upload\n100 GB Data Cap\nBasic Support\nFree Installation', '15000', '12000', NOW(), NOW()),
(2, '1', '1', 'Standard Home', '30 Mbps', '1 Month', 'Up to 30 Mbps Download\nUp to 15 Mbps Upload\n300 GB Data Cap\nPriority Support\nFree Installation\nFree Router', '25000', '22000', NOW(), NOW()),
(3, '1', '1', 'Premium Home', '50 Mbps', '1 Month', 'Up to 50 Mbps Download\nUp to 25 Mbps Upload\nUnlimited Data\n24/7 Premium Support\nFree Installation\nFree Router\nIPTV Access', '40000', '35000', NOW(), NOW()),
(4, '1', '1', 'Ultra Home', '100 Mbps', '1 Month', 'Up to 100 Mbps Download\nUp to 50 Mbps Upload\nUnlimited Data\n24/7 VIP Support\nFree Installation\nPremium Router\nIPTV + VOD Access\nStatic IP', '60000', '55000', NOW(), NOW()),
(5, '1', '1', 'Business Basic', '50 Mbps', '1 Month', 'Up to 50 Mbps Symmetric\nUnlimited Data\nBusiness Support\n4 Hour Response SLA\nStatic IP\nFree Installation', '80000', '75000', NOW(), NOW()),
(6, '1', '1', 'Business Pro', '100 Mbps', '1 Month', 'Up to 100 Mbps Symmetric\nUnlimited Data\n24/7 Business Support\n2 Hour Response SLA\n5 Static IPs\nFree Installation\nDedicated Account Manager', '150000', '140000', NOW(), NOW()),
(7, '1', '1', 'Enterprise', '200 Mbps', '1 Month', 'Up to 200 Mbps Dedicated\nUnlimited Data\n24/7 Enterprise Support\n1 Hour Response SLA\n10 Static IPs\nPriority Installation\nDedicated Line', '300000', '280000', NOW(), NOW()),
(8, '1', '1', 'Student Special', '20 Mbps', '1 Month', 'Up to 20 Mbps Download\nUp to 10 Mbps Upload\n200 GB Data Cap\nStudent Support\nValid Student ID Required', '10000', '8000', NOW(), NOW())
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 2. SLIDERS (Banners for mobile app)
INSERT INTO sliders (id, language_id, status, image, name, offer, `desc`, created_at, updated_at) VALUES
(1, '1', '1', 'slider1.jpg', 'Welcome to T-Link', '50% OFF', 'Get blazing fast internet at unbeatable prices. Sign up today!', NOW(), NOW()),
(2, '1', '1', 'slider2.jpg', 'Fiber Optic Speed', 'NEW', 'Experience lightning-fast fiber optic internet up to 200 Mbps', NOW(), NOW()),
(3, '1', '1', 'slider3.jpg', 'Business Solutions', 'SPECIAL', 'Enterprise-grade connectivity for your business needs', NOW(), NOW()),
(4, '1', '1', 'slider4.jpg', 'Student Discount', '20% OFF', 'Special rates for students. Study and stream without limits!', NOW(), NOW()),
(5, '1', '1', 'slider5.jpg', 'Refer & Earn', 'BONUS', 'Refer a friend and get 1 month free subscription', NOW(), NOW())
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 3. BRANCHES (Service Centers)
INSERT INTO branches (id, status, iframe, branch_name, manager, phone, email, address, created_at, updated_at, language_id) VALUES
(1, '1', 'https://maps.google.com', 'T-Link Yangon HQ', 'U Kyaw Zaw', '09-123456789', 'yangon@tlink.com.mm', 'No. 123, Pyay Road, Kamayut Township, Yangon', NOW(), NOW(), 1),
(2, '1', 'https://maps.google.com', 'T-Link Mandalay', 'U Aung Min', '09-987654321', 'mandalay@tlink.com.mm', 'No. 45, 78th Street, Chan Aye Tharzan, Mandalay', NOW(), NOW(), 1),
(3, '1', 'https://maps.google.com', 'T-Link Naypyidaw', 'Daw Hnin Ei', '09-555666777', 'naypyidaw@tlink.com.mm', 'No. 88, Yazathingaha Road, Naypyidaw', NOW(), NOW(), 1),
(4, '1', 'https://maps.google.com', 'T-Link Taunggyi', 'U Sai Kyaw', '09-444555666', 'taunggyi@tlink.com.mm', 'No. 12, Bogyoke Road, Taunggyi', NOW(), NOW(), 1),
(5, '1', 'https://maps.google.com', 'T-Link Mawlamyine', 'U Nay Win', '09-333444555', 'mawlamyine@tlink.com.mm', 'No. 56, Strand Road, Mawlamyine', NOW(), NOW(), 1)
ON DUPLICATE KEY UPDATE branch_name=VALUES(branch_name);

-- 4. SUB COMPANIES
INSERT INTO sub_companies (id, name, code, status, created_at, updated_at) VALUES
(1, 'T-Link Yangon', 'TLK-YGN', 1, NOW(), NOW()),
(2, 'T-Link Mandalay', 'TLK-MDY', 1, NOW(), NOW()),
(3, 'T-Link Naypyidaw', 'TLK-NPT', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 5. PROMOTIONS
INSERT INTO promotions (id, title, description, discount_percent, start_date, end_date, status, created_at, updated_at) VALUES
(1, 'New Year Special', 'Get 30% off on all annual packages for new customers', 30.00, '2025-12-01', '2026-01-31', 1, NOW(), NOW()),
(2, 'Student Discount', '20% off for students with valid ID card', 20.00, '2025-01-01', '2025-12-31', 1, NOW(), NOW()),
(3, 'Business Bundle', 'Get 2 months free when you sign up for business plan annually', 15.00, '2025-12-01', '2026-03-31', 1, NOW(), NOW()),
(4, 'Referral Bonus', 'Both referrer and referee get 1 month free', 100.00, '2025-01-01', '2025-12-31', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE title=VALUES(title);

-- 6. MBT_BIND_USER (Broadband accounts to bind to test user)
INSERT INTO mbt_bind_user (id, er_id, user_name, user_real_name, group_id, region_id, user_create_time, user_update_time, user_expire_time, user_status_mbt, balance, mgr_name_create, mgr_name_update, phone, email, Bandwidth, Service_type, Monthly_Cost, user_id, mbt_user_id, bind_status, created_at, updated_at) VALUES
(1, 1001, 'tlink_user_001', 'Aung Kyaw', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), '2026-01-31', 0, 50000.00, 'admin', 'admin', '09999888777', 'aungkyaw@gmail.com', '50 Mbps', 'Fiber', '35000', 1, 'MBT001', 1, NOW(), NOW()),
(2, 1002, 'tlink_user_002', 'May Thu', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), '2026-02-28', 0, 25000.00, 'admin', 'admin', '09888777666', 'maythu@gmail.com', '30 Mbps', 'Fiber', '22000', NULL, 'MBT002', 0, NOW(), NOW()),
(3, 1003, 'tlink_biz_001', 'ABC Company', 2, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), '2026-03-31', 0, 150000.00, 'admin', 'admin', '09777666555', 'abc@company.com', '100 Mbps', 'Dedicated', '140000', NULL, 'MBT003', 0, NOW(), NOW())
ON DUPLICATE KEY UPDATE user_name=VALUES(user_name);

-- 7. NOTIFICATIONS (For test user)
INSERT INTO notification (id, user_id, account_id, title, message, publish_info, install_user_id, is_read, is_multi, marketing_information_status, created_at, updated_at) VALUES
(1, 1, 'MBT001', 'Welcome to T-Link!', 'Thank you for choosing T-Link. Your account has been activated successfully. Enjoy high-speed internet!', NULL, NULL, 0, 0, 0, NOW(), NOW()),
(2, 1, 'MBT001', 'Payment Received', 'We have received your payment of 35,000 MMK for Premium Home package. Thank you!', NULL, NULL, 0, 0, 0, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 1, 'MBT001', 'Speed Upgrade Available', 'Great news! You are eligible for a free speed upgrade. Contact support to activate.', NULL, NULL, 0, 0, 0, DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY)),
(4, 1, 'MBT001', 'Scheduled Maintenance', 'We will perform network maintenance on Dec 28, 2025 from 2 AM to 4 AM. Service may be interrupted briefly.', NULL, NULL, 1, 0, 0, DATE_SUB(NOW(), INTERVAL 7 DAY), DATE_SUB(NOW(), INTERVAL 7 DAY)),
(5, 1, 'MBT001', 'New Year Promotion', 'Refer a friend this January and get 1 month FREE! Use code NEWYEAR2026.', NULL, NULL, 0, 0, 1, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
(6, NULL, NULL, 'System Update', 'Our mobile app has been updated with new features. Please update to the latest version.', NULL, NULL, 0, 1, 1, DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY))
ON DUPLICATE KEY UPDATE title=VALUES(title);

-- 8. FAULT_REPORT_QUERY (Fault reports for test user)
INSERT INTO fault_report_query (id, user_id, title, description, status, created_at, updated_at) VALUES
(1, 1, 'Slow Internet Speed', 'My internet speed has been slower than usual for the past 2 days. Expected 50 Mbps but only getting 10 Mbps.', 2, DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 8 DAY)),
(2, 1, 'Connection Dropping', 'Internet connection drops every few hours and needs router restart.', 1, DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 1, 'Router LED Blinking Red', 'The router power LED is blinking red continuously since morning.', 0, NOW(), NOW())
ON DUPLICATE KEY UPDATE title=VALUES(title);

-- 9. PAYMENT_NEW (Payment history for test user)
INSERT INTO payment_new (id, sub_com_id, product_id, user_id, begin_date, expire_date, order_id, payment_user_name, pack_expiery_date, trans_date, transaction_id, total_amt, invoice_no, admin_status, for_filter, payment_method, package_id, status, phone, discount, commercial_tax, created_at, updated_at) VALUES
(1, 1, 3, 1, '2025-11-01', '2025-11-30', 'ORD-2025-001', 'Aung Kyaw', '2025-11-30', '2025-10-28', 'TXN001122334', 35000.00, 'INV-2025-0001', 1, 'paid', 'KBZ Pay', 3, 1, '09999888777', 5000.00, 1750.00, DATE_SUB(NOW(), INTERVAL 60 DAY), DATE_SUB(NOW(), INTERVAL 60 DAY)),
(2, 1, 3, 1, '2025-12-01', '2025-12-31', 'ORD-2025-002', 'Aung Kyaw', '2025-12-31', '2025-11-28', 'TXN001122335', 35000.00, 'INV-2025-0002', 1, 'paid', 'Wave Pay', 3, 1, '09999888777', 5000.00, 1750.00, DATE_SUB(NOW(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 30 DAY)),
(3, 1, 3, 1, '2026-01-01', '2026-01-31', 'ORD-2025-003', 'Aung Kyaw', '2026-01-31', '2025-12-20', 'TXN001122336', 35000.00, 'INV-2025-0003', 1, 'paid', 'AYA Pay', 3, 1, '09999888777', 5000.00, 1750.00, DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
(4, 1, 3, 1, '2026-02-01', '2026-02-28', 'ORD-2026-001', 'Aung Kyaw', '2026-02-28', NULL, NULL, 35000.00, 'INV-2026-0001', 0, 'pending', NULL, 3, 0, '09999888777', 5000.00, 1750.00, NOW(), NOW())
ON DUPLICATE KEY UPDATE order_id=VALUES(order_id);

-- 10. SERVICES
INSERT INTO services (id, language_id, status, name, slug, icon, image, content, created_at, updated_at) VALUES
(1, '1', '1', 'Fiber Internet', 'fiber-internet', 'fa fa-wifi', 'service1.jpg', 'Experience lightning-fast fiber optic internet with speeds up to 200 Mbps. Perfect for streaming, gaming, and working from home.', NOW(), NOW()),
(2, '1', '1', 'Business Solutions', 'business-solutions', 'fa fa-building', 'service2.jpg', 'Dedicated business internet with guaranteed uptime, static IPs, and 24/7 enterprise support.', NOW(), NOW()),
(3, '1', '1', 'IPTV Service', 'iptv-service', 'fa fa-tv', 'service3.jpg', 'Watch your favorite channels in HD quality with our IPTV service. Over 100+ channels available.', NOW(), NOW()),
(4, '1', '1', 'VoIP Phone', 'voip-phone', 'fa fa-phone', 'service4.jpg', 'Crystal clear voice calls over internet. Perfect for home and business use with affordable rates.', NOW(), NOW()),
(5, '1', '1', 'Network Security', 'network-security', 'fa fa-shield', 'service5.jpg', 'Protect your network with our advanced security solutions including firewall, antivirus, and parental controls.', NOW(), NOW())
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 11. Update test user with bound broadband account
UPDATE users SET 
    bind_user_id = 1,
    bind_id = 1,
    activepackage = 3,
    address = 'No. 45, Insein Road, Hlaing Township',
    city = 'Yangon',
    state = 'Yangon Region',
    country = 'Myanmar',
    zipcode = '11051'
WHERE id = 1;

-- 12. SETTINGS (Website settings)
INSERT INTO settings (id, language_id, website_title, base_color, number, email, contactemail, address, footer_text, copyright_text, created_at, updated_at) VALUES
(1, '1', 'T-Link Group', '#1a73e8', '09-123456789', 'info@tlink.com.mm', 'support@tlink.com.mm', 'No. 123, Pyay Road, Kamayut Township, Yangon, Myanmar', 'Tremendous Link Group - Connecting Myanmar to the World', 'Copyright 2025 T-Link Group. All Rights Reserved.', NOW(), NOW())
ON DUPLICATE KEY UPDATE website_title=VALUES(website_title);

-- 13. BILLPAIDS (Bill payment records)
INSERT INTO billpaids (id, user_id, package_id, status, package_cost, method, currency_sign, currency_code, currency_value, payment_status, attendance_id, txn_id, yearmonth, fulldate, created_at, updated_at, invoice_number) VALUES
(1, 1, 3, 1, 35000, 'KBZ Pay', 'MMK', 'MMK', '1', 'completed', 'ATT001', 'TXN001122334', '2025-11', '2025-11-01', DATE_SUB(NOW(), INTERVAL 60 DAY), DATE_SUB(NOW(), INTERVAL 60 DAY), 'INV-2025-0001'),
(2, 1, 3, 1, 35000, 'Wave Pay', 'MMK', 'MMK', '1', 'completed', 'ATT002', 'TXN001122335', '2025-12', '2025-12-01', DATE_SUB(NOW(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 30 DAY), 'INV-2025-0002'),
(3, 1, 3, 1, 35000, 'AYA Pay', 'MMK', 'MMK', '1', 'completed', 'ATT003', 'TXN001122336', '2026-01', '2026-01-01', DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY), 'INV-2025-0003')
ON DUPLICATE KEY UPDATE txn_id=VALUES(txn_id);

-- 14. BIND_HISTORY (Broadband binding history)
INSERT INTO bind_history (id, user_id, bind_data, status, created_at, updated_at) VALUES
(1, 1, '{"mbt_user_id": "MBT001", "user_name": "tlink_user_001", "bound_at": "2025-10-15"}', 1, DATE_SUB(NOW(), INTERVAL 70 DAY), DATE_SUB(NOW(), INTERVAL 70 DAY))
ON DUPLICATE KEY UPDATE bind_data=VALUES(bind_data);

SELECT 'Test data inserted successfully!' as Result;
