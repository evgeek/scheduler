<?php

namespace Evgeek\Scheduler\Wrapper;

use Evgeek\Scheduler\Config;
use Psr\Log\LoggerInterface;

class LoggerWrapper
{
    /**
     * PSR-3 is a compatible logger. If null - the log will be sent to the stdout/stderr.
     * @var ?LoggerInterface
     */
    private $logger;
    /**
     * If true, a debug log will be written.
     * The logging method is defined by the $logger parameter of the constructor
     * @var bool
     */
    private $debugLogging;
    /**
     * If true, an error log will be written.
     * The logging method is defined by the $logger parameter of the constructor
     * @var bool
     */
    private $errorLogging;
    /**
     * Log level for information/debug messages (DEBUG by default).
     * @var mixed
     */
    private $debugLogLevel;
    /**
     * Log level for error messages (ERROR by default).
     * @var mixed
     */
    private $errorLogLevel;

    /**
     * Wrapper around PSR-3 logger and scheduler config.
     * If logging disabled in config - wrapper disable writing.
     * If PSR-3 configured - wrapper use it, otherwise - sending to stdout/stderr
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->logger = $config->getLogger();
        $this->debugLogging = $config->getDebugLogging();
        $this->errorLogging = $config->getErrorLogging();
        $this->debugLogLevel = $config->getDebugLogLevel();
        $this->errorLogLevel = $config->getErrorLogLevel();
    }

    /**
     * Write debug message
     * @param string $message
     */
    public function debug(string $message): void
    {
        if (!$this->debugLogging) {
            return;
        }

        if ($this->logger === null) {
            $stdout = fopen('php://stdout', 'wb');
            fwrite($stdout, $message . PHP_EOL);
            fclose($stdout);
            return;
        }

        $this->debugLogLevel === null ?
            $this->logger->debug($message) :
            $this->logger->log($this->debugLogLevel, $message);
    }

    /**
     * Write error message
     * @param string $message
     */
    public function error(string $message): void
    {
        if (!$this->errorLogging) {
            return;
        }

        if ($this->logger === null) {
            $stdout = fopen('php://stderr', 'wb');
            fwrite($stdout, $message . PHP_EOL);
            fclose($stdout);
            return;
        }

        $this->errorLogLevel === null ?
            $this->logger->error($message) :
            $this->logger->log($this->errorLogLevel, $message);
    }
}