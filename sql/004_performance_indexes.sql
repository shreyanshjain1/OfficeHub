SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE requests
    ADD INDEX idx_requests_status_priority_created (status, priority, created_at),
    ADD INDEX idx_requests_department_status_created (department_id, status, created_at),
    ADD INDEX idx_requests_assigned_status_created (assigned_to_user_id, status, created_at),
    ADD INDEX idx_requests_due_date_status (due_date, status);

ALTER TABLE audit_logs
    ADD INDEX idx_audit_action_created (action, created_at);