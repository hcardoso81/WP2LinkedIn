<?php
if (!defined('ABSPATH')) exit;

class WPLP_Logger
{
    const LEVEL_INFO  = 'info';
    const LEVEL_ERROR = 'error';

    private static function log_dir(): string
    {
        return WP_CONTENT_DIR . '/plugins/wp2linkedin/logs';
    }

    private static function log_file(string $level): string
    {
        return self::log_dir() . '/' . $level . '.log';
    }

    private static function ensure_log_dir(): void
    {
        $dir = self::log_dir();
        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
        }
    }

    public static function info(string $message, array $context = []): void
    {
        self::write(self::LEVEL_INFO, $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write(self::LEVEL_ERROR, $message, $context);
    }

    private static function write(string $level, string $message, array $context): void
    {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        self::ensure_log_dir();

        $timestamp = current_time('Y-m-d H:i:s');
        $entry = "[{$timestamp}] {$message}";

        if (!empty($context)) {
            $entry .= ' | ' . wp_json_encode($context, JSON_UNESCAPED_UNICODE);
        }

        $entry .= PHP_EOL;

        @file_put_contents(
            self::log_file($level),
            $entry,
            FILE_APPEND | LOCK_EX
        );
    }
}
