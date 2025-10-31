# open-ai-task-bundle

[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Build Status](https://img.shields.io/badge/build-passing-success.svg)]()
[![Code Coverage](https://img.shields.io/badge/coverage-100%25-success.svg)]()

[English](README.md) | [中文](README.zh-CN.md)

任务分解系统 - 一个基于 OpenAI 的智能任务执行和管理系统，通过执行者和负责人的双角色协作模式来完成复杂任务。

## 目录

- [功能特性](#功能特性)
- [安装](#安装)
- [配置](#配置)
- [使用方法](#使用方法)
- [实体说明](#实体说明)
- [高级用法](#高级用法)
- [示例](#示例)
- [最佳实践](#最佳实践)
- [依赖说明](#依赖说明)
- [参考文档](#参考文档)
- [许可证](#许可证)

## 功能特性

- **双角色协作**：执行者和负责人角色协同工作完成复杂任务
- **AI 驱动的任务执行**：利用 OpenAI 的先进语言模型进行智能任务处理
- **实时进度跟踪**：详细日志记录监控任务执行进度
- **灵活的任务管理**：支持多种任务状态和执行模式
- **调试模式支持**：详细的执行日志用于开发和故障排除
- **数据库集成**：完整的 Doctrine ORM 集成和实体管理
- **异常处理**：全面的错误处理和异常管理

## 安装

```bash
composer require tourze/open-ai-task-bundle
```

## 配置

### 1. 注册 Bundle

在 `config/bundles.php` 中添加：

```php
return [
    // ...
    Tourze\OpenAITaskBundle\OpenAITaskBundle::class => ['all' => true],
];
```

### 2. 数据库迁移

执行以下命令创建必要的数据表：

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

## 使用方法

### 控制台命令

#### open-ai:task

执行 AI 任务的命令，通过执行者和负责人的协作来完成复杂任务。

**命令格式：**
```bash
php bin/console open-ai:task -t <任务ID> [-d]
```

**参数说明：**
- `-t, --task`: 必填，要执行的任务 ID
- `-d, --debug`: 可选，开启调试模式，显示详细的执行过程

**使用示例：**
```bash
# 执行任务 ID 为 1 的任务
php bin/console open-ai:task -t 1

# 以调试模式执行任务
php bin/console open-ai:task -t 1 -d
```

**工作原理：**
1. 系统创建两个独立的 AI 对话：执行者对话和负责人对话
2. 执行者接收任务要求并开始执行
3. 负责人评估执行者的输出并给出下一步指令
4. 循环执行直到任务完成或失败
5. 负责人最终总结任务成果

## 实体说明

### Task 实体

任务实体包含以下主要属性：

- `name`: 任务名称
- `requirements`: 任务的详细要求
- `executor`: 执行任务的 AI 角色
- `manager`: 管理和评估任务的 AI 角色
- `status`: 任务状态（待处理、运行中、已完成、失败）
- `result`: 任务的最终结果
- `executorConversation`: 执行者的对话记录
- `managerConversation`: 负责人的对话记录

### 任务状态

任务支持以下状态：
- `PENDING`: 待处理
- `RUNNING`: 运行中
- `COMPLETED`: 已完成
- `FAILED`: 失败

### 异常处理

- `ApiKeyNotConfiguredException`: 当角色未配置 API 密钥时抛出
- `TaskExecutionException`: 任务执行过程中的异常

## 高级用法

### 自定义任务配置

```php
// 配置自定义参数的任务
$task = new Task();
$task->setName('复杂分析任务');
$task->setRequirements('分析数据模式并生成洞察');
$task->setExecutor($specializedExecutor);
$task->setManager($supervisorManager);

// 使用自定义设置进行高级执行
$taskCommand = new TaskCommand($entityManager, $openAiService, $functionService);
$taskCommand->execute($task, ['max_rounds' => 100, 'timeout' => 3600]);
```

### 批量任务处理

```php
// 按顺序处理多个任务
foreach ($tasks as $task) {
    try {
        $taskCommand->execute($task);
        $logger->info("任务 {$task->getId()} 执行成功");
    } catch (TaskExecutionException $e) {
        $logger->error("任务 {$task->getId()} 执行失败: {$e->getMessage()}");
    }
}
```

### 与工作流系统集成

```php
// 工作流集成示例
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

## 示例

### 创建任务

```php
use Tourze\OpenAITaskBundle\Entity\Task;
use OpenAIBundle\Enum\TaskStatus;

$task = new Task();
$task->setName('编写产品文档');
$task->setRequirements('为新功能编写用户手册，包括功能介绍、使用步骤和常见问题');
$task->setExecutor($executorCharacter);
$task->setManager($managerCharacter);
$task->setStatus(TaskStatus::PENDING);

$entityManager->persist($task);
$entityManager->flush();
```

### 执行任务

```bash
php bin/console open-ai:task -t {task_id}
```

## 最佳实践

1. **角色配置**：确保执行者和负责人角色都配置了有效的 API 密钥
2. **任务描述**：提供清晰、具体的任务要求，以获得更好的执行效果
3. **监控执行**：使用调试模式监控任务执行过程，了解 AI 的决策逻辑
4. **轮次限制**：系统默认最大执行 50 轮，避免无限循环

## 依赖说明

本 Bundle 依赖以下组件：
- OpenAI Bundle：提供 AI 对话功能
- Doctrine ORM：数据持久化
- Symfony Console：命令行支持
- 各种 Tourze 工具包：提供雪花 ID、时间戳等功能

## 参考文档

- [OpenAI API 文档](https://platform.openai.com/docs)
- [Symfony Console 文档](https://symfony.com/doc/current/console.html)
- [Doctrine ORM 文档](https://www.doctrine-project.org/projects/orm.html)

## 许可证

本包是根据 [MIT 许可证](https://opensource.org/licenses/MIT) 开源的软件。
