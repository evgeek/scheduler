<?php

namespace Evgeek\Scheduler\Wrapper;

use Evgeek\Scheduler\Config;
use Psr\Log\LoggerInterface;
use Throwable;

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
     * Mapping specific exceptions to PSR-3 log channels (class => level).
     * @var array
     */
    private $exceptionLogMatching;

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
        $this->exceptionLogMatching = $config->getExceptionLogMatching();
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
     * @param Throwable|null $e
     */
    public function error(string $message, ?Throwable $e = null): void
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

        if ($e !== null) {
            $matchedLogLevel = $this->getMatchedLogLevel($e);
            if ($matchedLogLevel !== null) {
                $this->logger->log($matchedLogLevel, $message);
                return;
            }
        }

        $this->errorLog === true ?
            $this->logger->error($message) :
            $this->logger->log($this->errorLog, $message);
    }

    /**
     * @param Throwable $e
     * @return mixed|null
     */
    private function getMatchedLogLevel(Throwable $e)
    {
        $class = get_class($e);
        if (array_key_exists($class, $this->exceptionLogMatching)) {
            return $this->exceptionLogMatching[$class];
        }

        foreach ($this->exceptionLogMatching as $class => $code) {
            if ($e instanceof $class) {
                return $code;
            }
        }

        return null;
    }
}