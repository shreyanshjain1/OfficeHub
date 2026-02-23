INSERT INTO request_comments (request_id, author_user_id, body, visibility)
VALUES
  ((SELECT id FROM requests ORDER BY id ASC LIMIT 1),
   (SELECT id FROM users WHERE email='employee@office.local' LIMIT 1),
   'Issue started after Windows update last week. I can reproduce consistently.',
   'ALL'),
  ((SELECT id FROM requests ORDER BY id DESC LIMIT 1),
   (SELECT id FROM users WHERE email='manager@office.local' LIMIT 1),
   'Noted. Reviewing budget and current supply levels.',
   'ALL');

UPDATE requests
SET assigned_to_user_id=(SELECT id FROM users WHERE email='admin@office.local' LIMIT 1),
    status='IN_PROGRESS',
    status_updated_at=NOW()
WHERE title LIKE 'Laptop keeps disconnecting%';
