<?php

declare(strict_types=1);

final class AppHelper
{
    public static function h(?string $value): string
    {
        return Security::e((string)$value);
    }

    public static function currentPage(): string
    {
        $page = $_GET['page'] ?? 'dashboard';
        return is_string($page) && $page !== '' ? $page : 'dashboard';
    }

    public static function isActivePage(string $page): bool
    {
        return self::currentPage() === $page;
    }

    public static function statusBadgeClass(string $status): string
    {
        return match ($status) {
            'OPEN' => 'text-bg-primary',
            'IN_REVIEW' => 'text-bg-info',
            'APPROVED' => 'text-bg-success',
            'REJECTED' => 'text-bg-danger',
            'IN_PROGRESS' => 'text-bg-warning',
            'DONE' => 'text-bg-success',
            'CLOSED' => 'text-bg-secondary',
            default => 'text-bg-light',
        };
    }

    public static function priorityBadgeClass(string $priority): string
    {
        return match ($priority) {
            'LOW' => 'text-bg-light',
            'MEDIUM' => 'text-bg-secondary',
            'HIGH' => 'text-bg-warning',
            'URGENT' => 'text-bg-danger',
            default => 'text-bg-light',
        };
    }

    public static function typeBadgeClass(string $type): string
    {
        return match ($type) {
            'IT' => 'border-primary text-primary',
            'SUPPLIES' => 'border-success text-success',
            'OFFICE' => 'border-warning text-warning',
            'OTHER' => 'border-secondary text-secondary',
            default => 'border-secondary text-secondary',
        };
    }

    public static function roleLabel(string $role): string
    {
        return match ($role) {
            'admin' => 'Administrator',
            'manager' => 'Manager',
            'employee' => 'Employee',
            default => ucfirst($role),
        };
    }

    public static function formatDate(?string $date, string $fallback = '—'): string
    {
        if (!$date) {
            return $fallback;
        }

        try {
            return (new DateTimeImmutable($date))->format('M d, Y');
        } catch (Throwable) {
            return $fallback;
        }
    }

    public static function formatDateTime(?string $dateTime, string $fallback = '—'): string
    {
        if (!$dateTime) {
            return $fallback;
        }

        try {
            return (new DateTimeImmutable($dateTime))->format('M d, Y g:i A');
        } catch (Throwable) {
            return $fallback;
        }
    }

    public static function ageDays(?string $createdAt): ?int
    {
        if (!$createdAt) {
            return null;
        }

        try {
            $created = new DateTimeImmutable($createdAt);
            $now = new DateTimeImmutable('now');
            return max(0, (int)$created->diff($now)->format('%a'));
        } catch (Throwable) {
            return null;
        }
    }

    public static function slaBucket(array $request): string
    {
        $status = (string)($request['status'] ?? '');
        if (in_array($status, ['DONE', 'CLOSED', 'REJECTED'], true)) {
            return 'resolved';
        }

        $ageDays = self::ageDays((string)($request['created_at'] ?? ''));
        if ($ageDays === null) {
            return 'unknown';
        }

        $priority = (string)($request['priority'] ?? 'MEDIUM');

        $threshold = match ($priority) {
            'URGENT' => 1,
            'HIGH' => 2,
            'MEDIUM' => 5,
            'LOW' => 10,
            default => 5,
        };

        if ($ageDays > $threshold) {
            return 'breached';
        }

        if ($ageDays === $threshold || $ageDays === max(0, $threshold - 1)) {
            return 'at_risk';
        }

        return 'healthy';
    }

    public static function slaLabel(string $bucket): string
    {
        return match ($bucket) {
            'healthy' => 'Healthy',
            'at_risk' => 'At Risk',
            'breached' => 'Breached',
            'resolved' => 'Resolved',
            default => 'Unknown',
        };
    }

    public static function slaBadgeClass(string $bucket): string
    {
        return match ($bucket) {
            'healthy' => 'text-bg-success',
            'at_risk' => 'text-bg-warning',
            'breached' => 'text-bg-danger',
            'resolved' => 'text-bg-secondary',
            default => 'text-bg-light',
        };
    }

    public static function avatarLetter(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '?';
        }

        return mb_strtoupper(mb_substr($name, 0, 1));
    }

    public static function pageTitle(string $page): string
    {
        return match ($page) {
            'dashboard' => 'Dashboard',
            'requests' => 'Requests',
            'request_new' => 'New Request',
            'request_view' => 'Request Details',
            'admin_users' => 'User Management',
            'analytics' => 'Analytics',
            'login' => 'Sign In',
            default => 'OfficeHub',
        };
    }
}