<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Yaml\Yaml;

class LoadPostalConfig extends Command
{
    protected $signature = 'postal:load-config
                            {path=/opt/postal/config/postal.yml : Path to Postal YAML config}';

    protected $description = 'Load Postal database settings from postal.yml';

    public function handle(): int
    {
        $path = $this->argument('path');

        if (!is_file($path)) {
            $this->error("Postal config file not found: {$path}");
            return self::FAILURE;
        }

        $config = Yaml::parseFile($path);
        $mainDb = $config['main_db'] ?? [];
        $messageDb = $config['message_db'] ?? [];

        foreach (['host', 'username', 'password', 'database'] as $key) {
            if (!array_key_exists($key, $mainDb)) {
                $this->error("Missing main_db.{$key} in {$path}");
                return self::FAILURE;
            }
        }

        foreach (['host', 'username', 'password', 'prefix'] as $key) {
            if (!array_key_exists($key, $messageDb)) {
                $this->error("Missing message_db.{$key} in {$path}");
                return self::FAILURE;
            }
        }

        $values = [
            'POSTAL_DB_HOST' => $mainDb['host'],
            'POSTAL_DB_PORT' => $mainDb['port'] ?? '3306',
            'POSTAL_DB_NAME' => $mainDb['database'],
            'POSTAL_DB_USER' => $mainDb['username'],
            'POSTAL_DB_PASS' => $mainDb['password'],
            'POSTAL_MESSAGE_DB_HOST' => $messageDb['host'],
            'POSTAL_MESSAGE_DB_PORT' => $messageDb['port'] ?? '3306',
            'POSTAL_MESSAGE_DB_PREFIX' => $messageDb['prefix'],
            'POSTAL_MESSAGE_DB_USER' => $messageDb['username'],
            'POSTAL_MESSAGE_DB_PASS' => $messageDb['password'],
        ];

        $this->writeEnvironmentValues($values);
        $this->refreshRuntimeConfig($values);
        $this->info('.env updated with Postal database settings.');

        return self::SUCCESS;
    }

    private function refreshRuntimeConfig(array $values): void
    {
        Config::set('postal.main_db', [
            'host' => $values['POSTAL_DB_HOST'],
            'port' => $values['POSTAL_DB_PORT'],
            'database' => $values['POSTAL_DB_NAME'],
            'username' => $values['POSTAL_DB_USER'],
            'password' => $values['POSTAL_DB_PASS'],
        ]);

        Config::set('postal.message_db', [
            'host' => $values['POSTAL_MESSAGE_DB_HOST'],
            'port' => $values['POSTAL_MESSAGE_DB_PORT'],
            'prefix' => $values['POSTAL_MESSAGE_DB_PREFIX'],
            'username' => $values['POSTAL_MESSAGE_DB_USER'],
            'password' => $values['POSTAL_MESSAGE_DB_PASS'],
        ]);
    }

    private function writeEnvironmentValues(array $values): void
    {
        $envPath = base_path('.env');
        $content = is_file($envPath) ? file_get_contents($envPath) : '';

        foreach ($values as $key => $value) {
            $line = "{$key}=".$this->formatEnvironmentValue((string) $value);

            if (preg_match("/^{$key}=.*$/m", $content)) {
                $content = preg_replace("/^{$key}=.*$/m", $line, $content);
            } else {
                $content = rtrim($content).PHP_EOL.$line.PHP_EOL;
            }
        }

        file_put_contents($envPath, $content);
    }

    private function formatEnvironmentValue(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (preg_match('/\s|#|"|\'|=/', $value)) {
            return '"'.str_replace('"', '\"', $value).'"';
        }

        return $value;
    }
}
