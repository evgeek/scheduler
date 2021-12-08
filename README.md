# Simple PHP Scheduler
Supports various types of tasks, task batches, customizable logging with any [PSR-3](https://www.php-fig.org/psr/psr-3/) logger or stdout/stderr, full launch log in the database (powered by Doctrine DBAL).
## Installation
Requires PHP version 7.2 or higher
```bash
$ composer require evgeek/scheduler
```
## Basic usage
Create php file (```scheduler.php``` for example) with scheduler setup code: 
```php
<?php

use Doctrine\DBAL\DriverManager;
use Evgeek\Scheduler\Scheduler;
use Evgeek\Scheduler\Handler\DatabaseLogging;

require_once '/path/to/vendor/autoload.php';

//Create DBAL connector
$uri = 'mysql://user:secret@localhost/mydb';
$conn = \Doctrine\DBAL\DriverManager::getConnection(['url' => $uri]);

//Create new instance of the scheduler
$handler = new DatabaseLogging($conn);
$scheduler = new Scheduler($handler);

//Create, add to scheduler and setup new task
$scheduler->task(function() {
    echo 'Hello world!';
})
    ->schedule()
    ->every(1);

//Run the scheduler
$scheduler->run();
```
And add new line to crontab to run your scheduler file every minute:
```
* * * * * /usr/bin/php /path/to/your/scheduler.php
```
## Supported task types
All tasks are created using the ```$scheduler->task()``` method. The task type is recognized automatically.
### Closure
```php
$scheduler->task(function() {echo 'Hello world!';});
```
### Bash command
```php
$scheduler->task('ls -la');
```
### Php file
```php
$scheduler->task('/path/to/your/file.php');
```
### Job interface
The scheduler can work with any class that implements a simple interface ```Evgeek\Scheduler\JobInterface``` (single required method ```dispatch()``` must run the task).
```php
$scheduler->task(new Job());
```
### Bunch
You can combine several tasks of different types into a batch and manage their launch in the same way as a single task.
```php
$scheduler->task([
    $scheduler->task(function() {echo 'Hello world!';}),
    $scheduler->task('ls -la'),
    $scheduler->task('/path/to/your/file.php'),
    $scheduler->task(new Job())
]);
```
## Setting up a task
After creating a task using the ```task()``` method, you can add it to the scheduler using the ```schedule()``` method. After that, various methods of setting up the task launch become available.
### Work mode methods
* **_repetitive_** - using the ```every()``` (every X minutes) or ```delay()``` (X minutes after previous launch finished) methods.
* **_interval_** - using the ```addInterval()```, ```daysOfWeek()```, ```daysOfMonth()```, ```months()``` and ```years()``` methods. If interval mode is used with repetitive mode, the task will be launched repetitive at the specified intervals, otherwise the task will be launched once per interval.
#### Examples
* Every hour
```php
$scheduler->task(new Job())
    ->schedule()
    ->every(60);
```
* Every night all night with 5 minutes delay
```php
$scheduler->task(new Job())
    ->schedule()
    ->addInterval('00:00', '06:00')
    ->delay(5);
```
* Every Sunday, once from 03:00 to 04:00 and once from 15:00 to 16:00 
```php
$scheduler->task(new Job())
    ->schedule()
    ->addInterval('03:00', '04:00')
    ->addInterval('15:00', '16:00')
    ->daysOfWeek([7]);
```
* Once on 1st January and December and every Monday and Wednesday in January and December
```php
$scheduler->task(new Job())
    ->schedule()    
    ->daysOfWeek(['Mon', 'wednesday'])
    ->daysOfMonth([1])
    ->months(['Jan'])
    ->months(['Dec']);
```
* Every minute January 1, 2022
```php
$scheduler->task(new Job())
    ->schedule()    
    ->every(1)
    ->daysOfMonth([1])
    ->months(['Jan'])
    ->years([2022]);
```
### Setup methods
```php
$scheduler->task(new Job())
    ->schedule()    
    ->every(1)
    ->name('Job')
    ->description('A very useful task')
    ->tries(3);
```
* ```name()``` - task name for the log.
* ```description()``` - task description for the log.
* ```preventOverlapping()``` (default ```false```) - if true, the task cannot start if another instance of this task is currently running.
* ```lockResetTimeout()``` (default ```360```) - how long (in minutes) the task lock should be recognized as frozen and reset.
* ```tries()``` (default ```1```) - how many attempts to complete the task should be made in case of an error.
* ```tryDelay()``` (default ```0```) - how long (in minutes) to wait before retrying the failed task.
### Helper methods
* ```getSettings()``` - returns array with task settings.
* ```logDebug()``` - send a message to the debug channel immediately.
* ```logError()``` - send a message to the error channel immediately.
## Scheduler setup
You can configure scheduler with handler object implements ```\Evgeek\Scheduler\Handler\LockHandlerInterface``` and config object ```Evgeek\Scheduler\Config```. Example:
```php
//Creates and setup handler
$uri = 'mysql://user:secret@localhost/mydb';
$conn = \Doctrine\DBAL\DriverManager::getConnection(['url' => $uri]);
$handler = new \Evgeek\Scheduler\Handler\DatabaseLogging(
    $conn, 
    'scheduler_tasks', 
    'scheduler_launches'
);

//Creates and setup config
$config = new \Evgeek\Scheduler\Config();
$config
    ->setDebugLogging(true)
    ->setDefaultTries(3);

//Creates scheduler with handler and (optional) config
$scheduler = new Scheduler($handler, $config);
```
### Handlers
Lock handler, implements ```\Evgeek\Scheduler\Handler\LockHandlerInterface```. So far, only one is available.
* ```\Evgeek\Scheduler\Handler\DatabaseLogging```\
Stores locks in the database with tasks information in one table and a full launch log in another. Needs configured [Doctrine DBAL](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/introduction.html) object as first parameter of constructor. You can pass custom task table name to the second optional parameter, and table name to the third.
### Config
Allows you to configure other scheduling options. You can do this using ```Config``` constructor parameters or using ```$config``` methods:
#### Logger
The scheduler has two log channels: ```debug``` for getting detailed launch information and ```error``` for task errors. Methods for configure:
* ```setDebugLogging()``` (default ```false```) - enable/disable ```debug``` channel.
* ```setErrorLogging()``` (default ```true```) - enable/disable ```error``` channel.
* ```setLogger()``` (default ```null```) - set [PSR-3](https://www.php-fig.org/psr/psr-3/) compatible logger. If passed ```null```, log will be sent to ```STDOUT```/```STDERR```.
* ```setDebugLogLevel()``` (default ```null```) - sets custom log level for the PSR-3 logger for ```debug``` messages. By default, this is ```debug```.
* ```setDErrorLogLevel()``` (default ```null```) - sets custom log level for the PSR-3 logger for ```error``` messages. By default, this is ```error```.
* ```setLogUncaughtErrors()``` (default ```false```) - registers shutdown function for log uncaught exceptions such as PHP fatal errors or incorrect task settings.
* ```setLogMessageFormat()``` (default ```"[{{task_id}}. {{TASK_TYPE}} '{{task_name}}']: {{message}}"```) - formatting template for task logger. Available variables: 
  * ```{{task_id}}```
  * ```{{task_type}}```
  * ```{{TASK_TYPE}}```
  * ```{{task_name}}```
  * ```{{TASK_NAME}}```
  * ```{{message}}```
  * ```{{MESSAGE}}```
  * ```{{task_description}}```
  * ```{{TASK_DESCRIPTION}}```

Lowercase for regular case, uppercase - for forced uppercase. Log message example with default formatting:
```php
/* ... */
$config->setDebugLogging(true);
/* ... */
$scheduler->task('ls -la')
    ->schedule()
    ->delay(0)
    ->tries(3);
```
```
[0. COMMAND 'ls -la']: Checking if it's time to start
[0. COMMAND 'ls -la']: Launched (try 1/3)
[0. COMMAND 'ls -la']: Completed in 00s
```
* ```setCommandOutput()``` (default ```false```) - enable/disable shell output for `bash command` tasks.
#### Default task options
Some options for setting default task options. The parameters specified in the task overwrite the default values.
* ```setDefaultPreventOverlapping()``` (default ```false```) - if true, the task cannot start if another instance of this task is currently running.
* ```setDefaultLockResetTimeout()``` (default ```360```) - how long (in minutes) the task lock should be recognized as frozen and reset.
* ```setDefaultTries()``` (default ```1```) - how many attempts to complete the task should be made in case of an error.
* ```setDefaultTryDelay()``` (default ```0```) - how long (in minutes) to wait before retrying the failed task.
#### Others
* ```setMinimumIntervalLength()``` (default ```30```) - Minimum interval size in minutes (for task method ```addInterval()```). Currently, tasks are started sequentially and synchronously, so the scheduler cannot guarantee the exact time when the task will start. Because of this, I had to limit the minimum size of the interval to make sure that the task will not be missed because the interval is too small. This is not a good decision. In future updates, task launching will be implemented asynchronously, and the interval limitation will be removed.  
## Future plans
* Asynchronous task launch.
* Tests.
* More lock handlers, first - file lock handler as default behavior.
* More scheduling options, including launch at exact time.
* Managing the scheduler using console commands - list of task, force start all or specific task etc.
