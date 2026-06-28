<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class PostalServer extends Model
{
    protected $connection = 'postal_main';

    protected $table = 'servers';

    public $timestamps = false;

    protected $appends = [
        'host',
        'port',
        'database',
        'username',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('not_deleted', function ($builder) {
            $builder->whereNull('deleted_at');
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(PostalOrganization::class, 'organization_id');
    }

    /**
     * Get the database connection configuration for this postal server
     */
    public function getConnectionConfig(): array
    {
        $messageDb = $this->getMessageDatabaseConfig();

        return [
            'driver' => 'mariadb',
            'host' => $messageDb['host'],
            'port' => $messageDb['port'],
            'database' => $this->getMessageDatabaseName(),
            'username' => $messageDb['username'],
            'password' => $messageDb['password'],
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ];
    }

    public function getHostAttribute(): ?string
    {
        return $this->getMessageDatabaseConfigValue('host');
    }

    public function getPortAttribute(): string
    {
        return (string) $this->getMessageDatabaseConfigValue('port', '3306');
    }

    public function getDatabaseAttribute(): ?string
    {
        return $this->getMessageDatabaseName();
    }

    public function getUsernameAttribute(): ?string
    {
        return $this->getMessageDatabaseConfigValue('username');
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->deleted_at === null && $this->suspended_at === null;
    }

    /**
     * Get the dynamic connection name for this postal server
     */
    public function getDynamicConnectionName(): string
    {
        if (!$this->id) {
            throw new \InvalidArgumentException('Cannot generate connection name for postal server without an ID');
        }
        return 'postal_' . $this->id;
    }

    private function getMessageDatabaseName(): ?string
    {
        $prefix = $this->getMessageDatabaseConfigValue('prefix');

        return $prefix ? "{$prefix}-server-{$this->id}" : null;
    }

    private function getMessageDatabaseConfig(): array
    {
        $config = [
            'host' => $this->getMessageDatabaseConfigValue('host'),
            'port' => $this->getMessageDatabaseConfigValue('port', '3306'),
            'prefix' => $this->getMessageDatabaseConfigValue('prefix'),
            'username' => $this->getMessageDatabaseConfigValue('username'),
            'password' => $this->getMessageDatabaseConfigValue('password'),
        ];

        foreach (['host', 'prefix', 'username'] as $key) {
            if ($config[$key] === null || $config[$key] === '') {
                throw new \RuntimeException("Missing Postal message DB config: {$key}");
            }
        }

        return $config;
    }

    private function getMessageDatabaseConfigValue(string $key, mixed $default = null): mixed
    {
        return config("postal.message_db.{$key}", $default);
    }
}
