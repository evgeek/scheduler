<?php

namespace Evgeek\Scheduler\Handler;

use Carbon\Carbon;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Exception;

class DatabaseLogging implements LockHandlerInterface
{
    /** @var Connection */
    private $conn;
    /** @var string */
    private $tasksTableName;
    /** @var string */
    private $launchesTableName;

    /**
     * Writing each launch to the database
     * @param Connection $conn
     * @param string $tasksTable tasks table name
     * @param string $launchesTable launch log table name
     * @throws SchemaException
     * @throws \Doctrine\DBAL\Exception
     */
    public function __construct(
        Connection $conn,
        string     $tasksTable = 'scheduler_tasks',
        string     $launchesTable = 'scheduler_launches'
    )
    {
        $this->conn = $conn;
        $this->tasksTableName = $tasksTable;
        $this->launchesTableName = $launchesTable;

        $this->prepareTasksTable();
        $this->prepareLaunchesTable();
    }

    /**
     * @inheritDoc
     * @param int $taskId
     * @param string $taskName
     * @param string $taskDescription
     * @return Launch|null
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    public function getLastLaunch(int $taskId, string $taskType, string $taskName, string $taskDescription): ?Launch
    {
        $checkedId = $this->checkTaskById($taskId, $taskType, $taskName, $taskDescription);

        if ($checkedId === null) {
            return null;
        }

        $lastLaunch = $this->conn->createQueryBuilder()
            ->select('*')
            ->from($this->launchesTableName)
            ->where('task_id = :task_id')
            ->orderBy('start_time', 'desc')
            ->setParameter('task_id', $taskId)
            ->setMaxResults(1)
            ->fetchAllAssociative();

        if (count($lastLaunch) === 0) {
            return null;
        }

        return $this->createLaunchObject(array_shift($lastLaunch));
    }

    /**
     * @inheritDoc
     * @param int $taskId
     * @return int
     * @throws \Doctrine\DBAL\Exception
     */
    public function startNewLaunch(int $taskId): int
    {
        $this->conn->createQueryBuilder()
            ->insert($this->launchesTableName)
            ->values([
                'task_id' => ':task_id',
                'start_time' => ':start_time',
            ])
            ->setParameter('task_id', $taskId)
            ->setParameter('start_time', Carbon::now()->toIso8601String())
            ->executeStatement();

        return (int)$this->conn->lastInsertId();
    }

    /**
     * @inheritDoc
     * @param int $id
     * @return int
     * @throws \Doctrine\DBAL\Exception
     */
    public function restartExistingLaunch(int $id): int
    {
        $this->conn->createQueryBuilder()
            ->update($this->launchesTableName)
            ->set('start_time', ':start_time')
            ->set('end_time', ':end_time')
            ->set('is_working', ':is_working')
            ->where('id = :id')
            ->setParameter('start_time', Carbon::now()->toIso8601String())
            ->setParameter('end_time', null)
            ->setParameter('is_working', 1)
            ->setParameter('id', $id)
            ->executeStatement();

        return $id;
    }

    /**
     * @inheritDoc
     * @param int $id
     * @param string|null $errorText
     * @throws \Doctrine\DBAL\Exception
     */
    public function completeLaunchSuccessfully(int $id, string $errorText = null): void
    {
        $this->conn->createQueryBuilder()
            ->update($this->launchesTableName)
            ->set('end_time', ':end_time')
            ->set('is_working', ':is_working')
            ->set('error_count', ':error_count')
            ->set('error_text', ':error_text')
            ->where('id = :id')
            ->setParameter('end_time', Carbon::now()->toIso8601String())
            ->setParameter('is_working', 0)
            ->setParameter('error_count', 0)
            ->setParameter('error_text', null)
            ->setParameter('id', $id)
            ->executeStatement();
    }

    /**
     * @inheritDoc
     * @param int $id
     * @param string|null $errorText
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    public function completeLaunchUnsuccessfully(int $id, string $errorText): int
    {
        $this->conn->createQueryBuilder()
            ->update($this->launchesTableName)
            ->set('end_time', ':end_time')
            ->set('is_working', ':is_working')
            ->set('error_count', 'error_count + 1')
            ->set('error_text', ':error_text')
            ->where('id = :id')
            ->setParameter('id', $id)
            ->setParameter('end_time', Carbon::now()->toIso8601String())
            ->setParameter('is_working', 0)
            ->setParameter('error_text', $errorText)
            ->executeStatement();

        $errRow = $this->conn->createQueryBuilder()
            ->select('error_count')
            ->from($this->launchesTableName)
            ->where('id = :id')
            ->setParameter('id', $id)
            ->setMaxResults(1)
            ->fetchAllAssociative();

        $errorCounter = $errRow[0]['error_count'] ?? null;
        if ($errorCounter === null) {
            throw new Exception("Failed to get the error counts of launch $id");
        }

        return $errorCounter;
    }

    /**
     * @inheritDoc
     * @throws \Doctrine\DBAL\Exception
     */
    public function resetLock(int $id): void
    {
        $this->conn->createQueryBuilder()
            ->update($this->launchesTableName)
            ->set('end_time', ':end_time')
            ->set('is_working', ':is_working')
            ->set('error_count', 'error_count + 1')
            ->set('error_text', ':error_text')
            ->where('id = :id')
            ->setParameter('end_time', Carbon::now()->toIso8601String())
            ->setParameter('is_working', 0)
            ->setParameter('error_text', 'The task ran for too long and was reset by timeout')
            ->setParameter('id', $id)
            ->executeStatement();
    }

    /**
     * Check and fill task table.
     * Returns null if the task can be launched without last launch check (new or changed), or (int) task_id
     * @param int $taskId
     * @param string $taskType
     * @param string $taskName
     * @param string $taskDescription
     * @return int|null
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    private function checkTaskById(int $taskId, string $taskType, string $taskName, string $taskDescription): ?int
    {
        $results = $this->conn->createQueryBuilder()
            ->select('*')
            ->from($this->tasksTableName)
            ->where('id = :id')
            ->setParameter('id', $taskId)
            ->fetchAllAssociative();

        if (count($results) === 0) {
            $this->writeDbTaskRecord($taskId, $taskType, $taskName, $taskDescription);
            return null;
        }

        $row = array_shift($results);

        $updateTaskQuery = $this->conn->createQueryBuilder()
            ->update($this->tasksTableName)
            ->set('last_activity', ":last_activity")
            ->where('id = :id')
            ->setParameter('last_activity', Carbon::now()->toIso8601String())
            ->setParameter('id', $taskId);

        if (
            (string)$this->getRowValue($row, 'type') !== $taskType ||
            (string)$this->getRowValue($row, 'name') !== $taskName ||
            (string)$this->getRowValue($row, 'description') !== $taskDescription
        ) {
            $updateTaskQuery
                ->set('type', ":type")
                ->set('name', ":name")
                ->set('description', ":description")
                ->setParameter('type', $taskType)
                ->setParameter('name', $taskName)
                ->setParameter('description', $taskDescription)
                ->executeStatement();

            return null;
        }

        $updateTaskQuery->executeStatement();

        return $taskId;
    }

    /**
     * Write new task record to database
     * @param int $taskId
     * @param string $taskType
     * @param string $taskName
     * @param string $taskDescription
     * @throws \Doctrine\DBAL\Exception
     */
    private function writeDbTaskRecord(int $taskId, string $taskType, string $taskName, string $taskDescription): void
    {
        $this->conn->createQueryBuilder()
            ->insert($this->tasksTableName)
            ->values([
                'id' => ':id',
                'type' => ':type',
                'name' => ':name',
                'description' => ':description',
                'last_activity' => ':last_activity',
            ])
            ->setParameter('id', $taskId)
            ->setParameter('type', $taskType)
            ->setParameter('name', $taskName)
            ->setParameter('description', $taskDescription)
            ->setParameter('last_activity', Carbon::now()->toIso8601String())
            ->executeStatement();
    }

    /**
     * Create launch object from database row
     * @param array $launch
     * @return Launch
     * @throws Exception
     */
    private function createLaunchObject(array $launch): Launch
    {
        $id = (int)$this->getRowValue($launch, 'id');
        $taskId = (int)$this->getRowValue($launch, 'task_id');
        $startTime = Carbon::parse($this->getRowValue($launch, 'start_time'));
        $endTime = $this->getRowValue($launch, 'end_time');
        $endTime = $endTime === null ? null : Carbon::parse($endTime);
        $isWorking = (bool)$this->getRowValue($launch, 'is_working');
        $errorCount = (int)$this->getRowValue($launch, 'error_count');
        $errorText = (string)$this->getRowValue($launch, 'error_text');

        return new Launch($id, $taskId, $startTime, $endTime, $isWorking, $errorCount, $errorText);
    }


    /**
     * Creates a task table if it doesn't exist
     * @throws \Doctrine\DBAL\Exception
     * @throws SchemaException
     */
    private function prepareTasksTable(): void
    {
        $sm = $this->conn->createSchemaManager();
        if (!$sm->tablesExist($this->tasksTableName)) {
            $table = new Table($this->tasksTableName);

            $table->addColumn('id', Types::BIGINT)->setUnsigned(true);
            $table->addColumn('type', Types::TEXT);
            $table->addColumn('name', Types::TEXT);
            $table->addColumn('description', Types::TEXT);
            $table->addColumn('last_activity', Types::DATETIMETZ_IMMUTABLE);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['name']);
            $table->addIndex(['last_activity']);

            $sm->createTable($table);
        }
    }

    /**
     * Creates a launches table if it doesn't exist
     * @throws SchemaException
     * @throws \Doctrine\DBAL\Exception
     */
    private function prepareLaunchesTable(): void
    {
        $sm = $this->conn->createSchemaManager();
        if (!$sm->tablesExist($this->launchesTableName)) {
            $table = new Table($this->launchesTableName);

            $table->addColumn('id', Types::BIGINT)->setUnsigned(true)->setAutoincrement(true);
            $table->addColumn('task_id', Types::BIGINT);
            $table->addColumn('start_time', Types::DATETIMETZ_IMMUTABLE);
            $table->addColumn('end_time', Types::DATETIMETZ_IMMUTABLE)->setNotnull(false);
            $table->addColumn('is_working', Types::BOOLEAN)->setDefault(true);
            $table->addColumn('error_count', Types::INTEGER)->setDefault(0);
            $table->addColumn('error_text', Types::TEXT)->setNotnull(false);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['task_id', 'start_time', 'end_time', 'is_working', 'error_count']);
            $table->addForeignKeyConstraint(
                $this->tasksTableName,
                ['task_id'],
                ['id'],
                ['onUpdate' => 'NO ACTION', 'onDelete' => 'NO ACTION']
            );

            $sm->createTable($table);
        }
    }

    /**
     * Checks existence $key in a $row. Returns it value if exists, or exception, if not.
     * @param array $row
     * @param string $key
     * @return mixed
     * @throws Exception
     */
    private function getRowValue(array $row, string $key)
    {
        if (!array_key_exists($key, $row)) {
            throw new Exception("Column $key not found in db row");
        }
        return $row[$key];
    }

}