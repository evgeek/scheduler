<?php

namespace Evgeek\Scheduler\Handler;

use Carbon\Carbon;


/**
 * @property int $id
 * @property int $taskId
 * @property Carbon $startTime
 * @property ?Carbon $endTime
 * @property bool $isWorking
 * @property int $errorCount
 * @property string $errorText
 */
class Launch
{
    /** @var int */
    private $id;
    /** @var int */
    private $taskId;
    /** @var Carbon */
    private $startTime;
    /** @var Carbon|null */
    private $endTime;
    /** @var bool */
    private $isWorking;
    /** @var int */
    private $errorCount;
    /** @var string|null */
    private $errorText;

    public function __construct(
        int     $id,
        int     $taskId,
        Carbon  $startTime,
        ?Carbon $endTime,
        bool    $isWorking,
        int     $errorCount,
        ?string  $errorText
    )
    {
        $this->id = $id;
        $this->taskId = $taskId;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->isWorking = $isWorking;
        $this->errorCount = $errorCount;
        $this->errorText = $errorText;
    }

    public function __get($name)
    {
        return $this->$name;
    }

    public function __set($name, $value)
    {
        return false;
    }

    public function __isset($name)
    {
        return isset($this->$name);
    }

    public function __toString()
    {
        $properties = [
            'id' => $this->id,
            'taskId' => $this->taskId,
            'startTime' => $this->startTime->toIso8601String(),
            'endTime' => $this->endTime === null ? $this->endTime : $this->endTime->toIso8601String(),
            'isWorking' => $this->isWorking,
            'errorCount' => $this->errorCount,
            'errorText' => $this->errorText,
        ];

        return (string)json_encode($properties);

    }
}