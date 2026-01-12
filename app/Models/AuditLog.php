<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Immutable audit log model.
 * Records cannot be updated or deleted through the application.
 */
class AuditLog extends Model
{
    protected $fillable = [
        'action',
        'user_id',
        'ip',
        'user_agent',
        'subject_type',
        'subject_id',
        'before',
        'after',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
    ];

    /**
     * Boot the model and register immutability guards.
     */
    protected static function booted(): void
    {
        // Prevent updates - audit logs are immutable
        static::updating(function (AuditLog $log) {
            throw new RuntimeException('Audit logs are immutable and cannot be updated.');
        });

        // Prevent deletes from web - only console/scheduled commands can delete
        static::deleting(function (AuditLog $log) {
            if (!app()->runningInConsole()) {
                throw new RuntimeException('Audit logs cannot be deleted through the web interface.');
            }
        });
    }

    /**
     * Scope for filtering by action type.
     *
     * @param \Illuminate\Database\Eloquent\Builder<AuditLog> $query
     * @param string $action
     * @return \Illuminate\Database\Eloquent\Builder<AuditLog>
     */
    public function scopeAction(\Illuminate\Database\Eloquent\Builder $query, string $action): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for filtering by action prefix (e.g., 'login.').
     *
     * @param \Illuminate\Database\Eloquent\Builder<AuditLog> $query
     * @param string $prefix
     * @return \Illuminate\Database\Eloquent\Builder<AuditLog>
     */
    public function scopeActionPrefix(\Illuminate\Database\Eloquent\Builder $query, string $prefix): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('action', 'like', $prefix . '%');
    }

    /**
     * Scope for filtering by subject.
     *
     * @param \Illuminate\Database\Eloquent\Builder<AuditLog> $query
     * @param string $type
     * @param int|null $id
     * @return \Illuminate\Database\Eloquent\Builder<AuditLog>
     */
    public function scopeForSubject(\Illuminate\Database\Eloquent\Builder $query, string $type, ?int $id = null): \Illuminate\Database\Eloquent\Builder
    {
        $query->where('subject_type', $type);
        if ($id !== null) {
            $query->where('subject_id', $id);
        }
        return $query;
    }

    /**
     * Scope for filtering by user who performed the action.
     *
     * @param \Illuminate\Database\Eloquent\Builder<AuditLog> $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder<AuditLog>
     */
    public function scopeByUser(\Illuminate\Database\Eloquent\Builder $query, int $userId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('user_id', $userId);
    }
}
