# open-ai-task-bundle

[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Build Status](https://img.shields.io/badge/build-passing-success.svg)]()
[![Code Coverage](https://img.shields.io/badge/coverage-100%25-success.svg)]()

[English](README.md) | [中文](README.zh-CN.md)

Task decomposition system - An intelligent task execution and management system based on OpenAI, 
which completes complex tasks through the collaborative mode of executor and manager dual roles.

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Entity Description](#entity-description)
- [Advanced Usage](#advanced-usage)
- [Examples](#examples)
- [Best Practices](#best-practices)
- [Dependencies](#dependencies)
- [Reference Documentation](#reference-documentation)
- [License](#license)

## Features

- **Dual-Role Collaboration**: Executor and manager roles work together to complete complex tasks
- **AI-Powered Task Execution**: Leverages OpenAI's advanced language models for intelligent task processing
- **Real-time Progress Tracking**: Monitor task execution progress with detailed logging
- **Flexible Task Management**: Support for various task statuses and execution modes
- **Debug Mode Support**: Detailed execution logs for development and troubleshooting
- **Database Integration**: Full Doctrine ORM integration with proper entity management
- **Exception Handling**: Comprehensive error handling and exception management

## Installation

```bash
composer require tourze/open-ai-task-bundle
```

## Configuration

### 1. Register Bundle

Add in `config/bundles.php`:

```php
return [
    // ...
    Tourze\OpenAITaskBundle\OpenAITaskBundle::class => ['all' => true],
];
```

### 2. Database Migration

Execute the following commands to create necessary tables:

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

## Usage

### Console Commands

#### open-ai:task

Command to execute AI tasks through collaboration between executor and manager to complete complex tasks.

**Command Format:**
```bash
php bin/console open-ai:task -t <task-id> [-d]
```

**Parameters:**
- `-t, --task`: Required, the task ID to execute
- `-d, --debug`: Optional, enable debug mode to show detailed execution process

**Usage Examples:**
```bash
# Execute task with ID 1
php bin/console open-ai:task -t 1

# Execute task in debug mode
php bin/console open-ai:task -t 1 -d
```

**How it Works:**
1. System creates two independent AI conversations: executor conversation and manager conversation
2. Executor receives task requirements and starts execution
3. Manager evaluates executor's output and provides next instructions
4. Loop execution until task is completed or failed
5. Manager provides final summary of task results

## Entity Description

### Task Entity

The task entity contains the following main properties:

- `name`: Task name
- `requirements`: Detailed requirements of the task
- `executor`: AI role executing the task
- `manager`: AI role managing and evaluating the task
- `status`: Task status (pending, running, completed, failed)
- `result`: Final result of the task
- `executorConversation`: Executor's conversation history
- `managerConversation`: Manager's conversation history

### Task Status

Tasks support the following statuses:
- `PENDING`: Pending
- `RUNNING`: Running
- `COMPLETED`: Completed
- `FAILED`: Failed

### Exception Handling

- `ApiKeyNotConfiguredException`: Thrown when role is not configured with API key
- `TaskExecutionException`: Exception during task execution

## Advanced Usage

### Custom Task Configurations

```php
// Configure task with custom parameters
$task = new Task();
$task->setName('Complex Analysis Task');
$task->setRequirements('Analyze data patterns and generate insights');
$task->setExecutor($specializedExecutor);
$task->setManager($supervisorManager);

// Advanced execution with custom settings
$taskCommand = new TaskCommand($entityManager, $openAiService, $functionService);
$taskCommand->execute($task, ['max_rounds' => 100, 'timeout' => 3600]);
```

### Batch Task Processing

```php
// Process multiple tasks in sequence
foreach ($tasks as $task) {
    try {
        $taskCommand->execute($task);
        $logger->info("Task {$task->getId()} completed successfully");
    } catch (TaskExecutionException $e) {
        $logger->error("Task {$task->getId()} failed: {$e->getMessage()}");
    }
}
```

### Integration with Workflow Systems

```php
// Example workflow integration
class TaskWorkflow
{
    public function processWorkflow(array $workflowSteps): array
    {
        $results = [];
        foreach ($workflowSteps as $step) {
            $task = $this->createTaskFromStep($step);
            $result = $this->executeTask($task);
            $results[] = $result;
        }
        return $results;
    }
}
```

## Examples

### Creating a Task

```php
use Tourze\OpenAITaskBundle\Entity\Task;
use OpenAIBundle\Enum\TaskStatus;

$task = new Task();
$task->setName('Write Product Documentation');
$task->setRequirements('Write user manual for new feature, including feature introduction, usage steps and FAQs');
$task->setExecutor($executorCharacter);
$task->setManager($managerCharacter);
$task->setStatus(TaskStatus::PENDING);

$entityManager->persist($task);
$entityManager->flush();
```

### Executing a Task

```bash
php bin/console open-ai:task -t {task_id}
```

## Best Practices

1. **Role Configuration**: Ensure both executor and manager roles are configured with valid API keys
2. **Task Description**: Provide clear and specific task requirements for better execution results
3. **Monitor Execution**: Use debug mode to monitor task execution process and understand AI decision logic
4. **Round Limit**: System defaults to maximum 50 rounds to avoid infinite loops

## Dependencies

This Bundle depends on the following components:
- OpenAI Bundle: Provides AI conversation functionality
- Doctrine ORM: Data persistence
- Symfony Console: Command line support
- Various Tourze packages: Provides snowflake ID, timestamp and other features

## Reference Documentation

- [OpenAI API Documentation](https://platform.openai.com/docs)
- [Symfony Console Documentation](https://symfony.com/doc/current/console.html)
- [Doctrine ORM Documentation](https://www.doctrine-project.org/projects/orm.html)

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).