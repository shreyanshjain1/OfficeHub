SET NAMES utf8mb4;

INSERT INTO departments (name) VALUES
  ('IT'),
  ('Admin'),
  ('Sales'),
  ('Warehouse')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Passwords (bcrypt 12 rounds):
-- admin@office.local   Admin123!
-- manager@office.local Manager123!
-- employee@office.local Employee123!

INSERT INTO users (email, full_name, role, department_id, password_hash, is_active)
VALUES
  ('admin@office.local',   'Ava Admin',     'admin',   (SELECT id FROM departments WHERE name='IT' LIMIT 1),
   '$2b$12$5k5goWaBRlluZFktZLu7dOL6mSUWdF9CNU2OhfKAxlArh6Zg/UZDC', 1),
  ('manager@office.local', 'Mason Manager', 'manager', (SELECT id FROM departments WHERE name='Sales' LIMIT 1),
   '$2b$12$TgGdL46pNNtnQEhfK14WDu88u2Y.erHWeek6M/rjCAWNhpqVUEDRa', 1),
  ('employee@office.local','Eden Employee', 'employee',(SELECT id FROM departments WHERE name='Sales' LIMIT 1),
   '$2b$12$G8KNO4ax0M3DeAAV8zAxa.6EM0RDPgnU1do6Jui9yHuAQAQmVV.de', 1)
ON DUPLICATE KEY UPDATE
  full_name=VALUES(full_name),
  role=VALUES(role),
  department_id=VALUES(department_id),
  password_hash=VALUES(password_hash),
  is_active=VALUES(is_active);

INSERT INTO requests (requester_user_id, department_id, type, title, description, priority, due_date, status, status_updated_at)
VALUES
  ((SELECT id FROM users WHERE email='employee@office.local' LIMIT 1),
   (SELECT id FROM departments WHERE name='Sales' LIMIT 1),
   'IT',
   'Laptop keeps disconnecting from Wi-Fi',
   'Wi-Fi drops every ~10 minutes. Happens on both 2.4G and 5G. Please check drivers/router settings.',
   'HIGH',
   DATE_ADD(CURDATE(), INTERVAL 3 DAY),
   'OPEN',
   NOW()),
  ((SELECT id FROM users WHERE email='employee@office.local' LIMIT 1),
   (SELECT id FROM departments WHERE name='Sales' LIMIT 1),
   'SUPPLIES',
   'A4 paper restock',
   'Need 10 reams of A4, 70gsm for Sales printing. Current stock is almost empty.',
   'MEDIUM',
   DATE_ADD(CURDATE(), INTERVAL 7 DAY),
   'IN_REVIEW',
   NOW());
