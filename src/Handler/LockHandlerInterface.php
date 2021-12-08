<?php

namespace Evgeek\Scheduler\Handler;

interface LockHandlerInterface
{
    /**
     * Returns Launch object filled with last launch row data if last launch exists, or null if not exists or task was modified.
     * @param int $taskId
     * @param string $taskType
     * @param string $taskName
     * @param string $taskDescription
     * @return Launch|null
     */
    public function getLastLaunch(int $taskId, string $taskType, string $taskName, string $taskDescription): ?Launch;

    /**
     * Writes new launch in launches storage, and returns launch id (for full launch log storages).
     * If only one launch is stored in this storage type for each task (locking storage),
     *  it returns 0, because these storage types have no ids.
     * @param int $taskId
     * @return int
     */
    public function startNewLaunch(int $taskId): int;


    /** Restart existing launch without running new one.
     * id — launch_id for storage with launches log; task_id for storage with only last launch.
     * @param int $id
     * @return int
     */
    public function restartExistingLaunch(int $id): int;

    /**
     * Mark launch completed successfully.
     * id — launch_id for storage with launches log; task_id for storage with only last launch.
     * @param int $id
     */
    public function completeLaunchSuccessfully(int $id): void;

    /**
     * Mark launch completed unsuccessfully.
     * Returns error counter.
     * id — launch_id for storage with launches log; task_id for storage with only last launch.
     * @param int $id
     * @param string $errorText
     * @return int
     */
    public function completeLaunchUnsuccessfully(int $id, string $errorText): int;

    /**
     * Reset task lock and write locking error.
     * id — launch_id for storage with launches log; task_id for storage with only last launch.
     * @param int $id
     */
    public function resetLock(int $id): void;
}