<?php

/**
 * File-based logger writing leveled messages to a configurable log path.
 * Supports debug, info, warning, error, and other PSR-like severity levels with timestamps.
 * Defaults to framework.log in the application base path.
 *
 * @package    Framework
 * @subpackage Managers
 * @since      1.0.0
 */
namespace Kirki\Framework\Managers;

\defined('ABSPATH') || exit;
use DateTime;
use Kirki\Framework\Supports\Facades\File;
use function Kirki\Framework\base_path;
class LogManager
{
    /**
     * The path.
     *
     * @var string
     *
     * @since 1.0.0
     */
    protected string $path;
    /**
     * Create a new instance.
     *
     * @param ?string $path The path.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function __construct(?string $path = null)
    {
        $this->path = $path ?? base_path('framework.log');
    }
    /**
     * Log a debug message.
     *
     * @param mixed $message The message.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function debug($message)
    {
        $this->write($message, 'debug');
    }
    /**
     * Log an info message.
     *
     * @param mixed $message The message.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function info($message)
    {
        $this->write($message, 'info');
    }
    /**
     * Log a warning message.
     *
     * @param mixed $message The message.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function warning($message)
    {
        $this->write($message, 'warning');
    }
    /**
     * Log an error message.
     *
     * @param mixed $message The message.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function error($message)
    {
        $this->write($message, 'error');
    }
    /**
     * Log an emergency message.
     *
     * @param mixed $message The message.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function emergency($message)
    {
        $this->write($message, 'emergency');
    }
    /**
     * Log a critical message.
     *
     * @param mixed $message The message.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function critical($message)
    {
        $this->write($message, 'critical');
    }
    /**
     * Log an alert message.
     *
     * @param mixed $message The message.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function alert($message)
    {
        $this->write($message, 'alert');
    }
    /**
     * Clear the log file.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function clear()
    {
        @\file_put_contents($this->path, '');
    }
    /**
     * Format the message.
     *
     * @param mixed $message The message.
     * @param mixed $type The type.
     *
     * @return string
     *
     * @since 1.0.0
     */
    protected function format($message, $type)
    {
        return \sprintf("[%s] [%s] %s\n", (new DateTime())->format('Y-m-d H:i:s'), \strtoupper($type), $message);
    }
    /**
     * Write the message to the log file.
     *
     * @param mixed $message The message.
     * @param mixed $type The type.
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function write($message, $type)
    {
        File::make_dir($this->path);
        @\file_put_contents($this->path, $this->format($message, $type), \FILE_APPEND);
    }
}
