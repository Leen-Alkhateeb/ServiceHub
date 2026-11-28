SET NAMES utf8mb4;
START TRANSACTION;

INSERT INTO users (first_name, last_name, email, password, phone, location, gender, role, status, created_at, updated_at)
VALUES ('خالد', 'العمري', 'vendor.demo@servicehub.example', '$2y$10$coPcDgoA7UHzSoXGBLg8KOAZpiZWw/4hMF8EOcR/093BzF37Aq8fC', '0000000000', 'عمّان', 'male', 'vendor', 'active', NOW(), NOW());
SET @demo_vendor_id = LAST_INSERT_ID();

INSERT INTO users (first_name, last_name, email, password, phone, location, gender, role, status, created_at, updated_at)
VALUES ('سارة', 'الخطيب', 'customer.demo@servicehub.example', '$2y$10$coPcDgoA7UHzSoXGBLg8KOAZpiZWw/4hMF8EOcR/093BzF37Aq8fC', '0000000000', 'عمّان', 'female', 'customer', 'active', NOW(), NOW());
SET @demo_customer_id = LAST_INSERT_ID();

INSERT INTO vendor_profiles (user_id, experience_years, bio, work_type, work_permit_path, permit_expiry_date, application_status, applied_at, created_at, updated_at)
VALUES (@demo_vendor_id, 9, 'فني سباكة متخصص في كشف تسريبات المياه وصيانة التمديدات الصحية.', 'السباكة', 'demo/work-permit.pdf', '2035-12-31', 'approved', NOW(), NOW(), NOW());

INSERT INTO categories (name, description, image, created_at, updated_at)
VALUES
    ('السباكة', 'كشف التسريبات وصيانة التمديدات والسخانات.', '', NOW(), NOW()),
    ('الكهرباء', 'فحص لوحات التوزيع وإصلاح الأعطال والإنارة.', '', NOW(), NOW()),
    ('صيانة المكيفات', 'تنظيف المكيفات وفحص التبريد.', '', NOW(), NOW());
SET @demo_category_id = LAST_INSERT_ID();

INSERT INTO services (category_id, name, description, image, approval_status, created_at, updated_at)
VALUES (@demo_category_id, 'كشف وإصلاح تسريبات المياه', 'فحص التمديدات وإصلاح الوصلات التالفة.', '', 'approved', NOW(), NOW());
SET @demo_service_id = LAST_INSERT_ID();

INSERT INTO vendor_services (vendor_id, service_id, description, price, image, status, created_at, updated_at)
VALUES (@demo_vendor_id, @demo_service_id, 'كشف مصدر التسريب وفحص ضغط المياه بعد الإصلاح.', 40.00, '', 'active', NOW(), NOW());
SET @demo_offer_id = LAST_INSERT_ID();

INSERT INTO portfolio_projects (vendor_id, name, description, completion_date, created_at, updated_at)
VALUES (@demo_vendor_id, 'إصلاح تسريب في مبنى سكني', 'استبدال الوصلات التالفة واختبار ضغط الشبكة.', '2026-11-08', NOW(), NOW());

INSERT INTO recurring_availability (vendor_id, day_of_week, start_time, end_time, created_at, updated_at)
VALUES (@demo_vendor_id, 1, '09:00:00', '12:00:00', NOW(), NOW());
SET @demo_availability_id = LAST_INSERT_ID();

INSERT INTO bookings (customer_id, vendor_service_id, availability_id, booking_date, start_time, end_time, service_name, service_description, original_price, status, completed_at, created_at, updated_at)
VALUES (@demo_customer_id, @demo_offer_id, @demo_availability_id, '2026-11-09', '09:00:00', '10:00:00', 'كشف وإصلاح تسريبات المياه', 'فحص وإصلاح تمديدات المياه.', 40.00, 'completed', '2026-11-09 10:00:00', '2026-11-07 15:00:00', '2026-11-09 10:00:00');
SET @demo_booking_id = LAST_INSERT_ID();

INSERT INTO wallet_transactions (user_id, booking_id, transaction_type, amount, description, created_at)
VALUES (@demo_vendor_id, @demo_booking_id, 'credit', 40.00, 'تحرير كامل مبلغ الحجز بعد مرور 24 ساعة', '2026-11-10 10:00:00');

INSERT INTO reviews (booking_id, reviewer_id, reviewed_user_id, rating, comment, created_at)
VALUES (@demo_booking_id, @demo_customer_id, @demo_vendor_id, 5, 'وصل الفني في الموعد وأصلح التسريب بسرعة.', '2026-11-10 12:30:00');

COMMIT;
