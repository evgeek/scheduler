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
     * False/null - debug log disabled. True - enabled (STDOUT/DEBUG). Or set custom PSR-3 level.
     * @var bool
     */
    private $debugLog;
    /**
     * False/null - error log disabled. True - enabled (STDERR/ERROR). Or set custom PSR-3 level.
     * @var bool
     */
    private $errorLog;

    /**
     * Wrapper around PSR-3 logger and scheduler config.
     * If logging disabled in config - wrapper disable writing.
     * If PSR-3 configured - wrapper use it, otherwise - sending to stdout/stderr
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->logger = $config->getLogger();
        $this->debugLog = $config->getDebugLog();
        $this->errorLog = $config->getErrorLog();
    }

    /**
     * Write debug message
     * @param string $message
     */
    public function debug(string $message): void
    {
        if ($this->debugLog === false || $this->debugLog === null) {
            return;
        }

        if ($this->logger === null) {
            $stream = fopen('php://stdout', 'wb');
            fwrite($stream, $message . PHP_EOL);
            fclose($stream);
            return;
        }

        $this->debugLog === true ?
            $this->logger->debug($message) :
            $this->logger->log($this->debugLog, $message);
    }

    /**
     * Write error message
     * @param string $message
     */
    public function error(string $message): void
    {
        if ($this->errorLog === false || $this->errorLog === null) {
            return;
        }

        if ($this->logger === null) {
            $stream = fopen('php://stderr', 'wb');
            fwrite($stream, $message . PHP_EOL);
            fclose($stream);
            return;
        }

        $this->errorLog === true ?
            $this->logger->error($message) :
            $this->logger->log($this->errorLog, $message);
    }
}