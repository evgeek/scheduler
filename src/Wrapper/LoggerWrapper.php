<?php

namespace Evgeek\Scheduler\Wrapper;

use Evgeek\Scheduler\Config;

class LoggerWrapper
{
    /** @var Config */
    private $config;

    /**
     * Wrapper around PSR-3 logger and scheduler config.
     * If logging disabled in config - wrapper disable writing.
     * If PSR-3 configured - wrapper use it, otherwise - sending to stdout/stderr
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Write debug message
     * @param string $message
     */
    public function debug(string $message): void
    {
        if (!$this->config->getDebugLogging()) {
            return;
        }

        $logger = $this->config->getLogger();
        if ($logger === null) {
            $stdout = fopen('php://stdout', 'wb');
            fwrite($stdout, $message . PHP_EOL);
            fclose($stdout);
            return;
        }

        $logLevel = $this->config->getDebugLogLevel();
        $logLevel === null ?
            $logger->debug($message) :
            $logger->log($logLevel, $message);
    }

    /**
     * Write error message
     * @param string $message
     */
    public function error(string $message): void
    {
        if (!$this->config->getErrorLogging()) {
            return;
        }

        $logger = $this->config->getLogger();
        if ($logger === null) {
            $stdout = fopen('php://stderr', 'wb');
            fwrite($stdout, $message . PHP_EOL);
            fclose($stdout);
            return;
        }

        $logLevel = $this->config->getErrorLogLevel();
        $logLevel === null ?
            $logger->error($message) :
            $logger->log($logLevel, $message);
    }
}