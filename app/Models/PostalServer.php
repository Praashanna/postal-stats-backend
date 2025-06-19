<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;

class PostalServer extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'host', 
        'port',
        'database',
        'username',
        'password',
        'api_key',
        'api_url',
        'is_active',
        'additional_config'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'additional_config' => 'array',
        'password' => 'encrypted',
        'api_key' => 'encrypted'
    ];

    protected $hidden = [
        'password',
        'api_key'
    ];

    /**
     * Get the database connection configuration for this postal server
     */
    public function getConnectionConfig(): array
    {
        return [
            'driver' => 'mysql',
            'host' => $this->host,
            'port' => $this->port,
            'database' => $this->database,
            'username' => $this->username,
            'password' => $this->password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ];
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
}
