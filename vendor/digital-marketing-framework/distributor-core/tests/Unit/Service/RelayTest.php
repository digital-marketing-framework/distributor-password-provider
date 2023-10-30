<?php

namespace DigitalMarketingFramework\Distributor\Core\Tests\Unit\Service;

use DigitalMarketingFramework\Core\Context\ContextInterface;
use DigitalMarketingFramework\Core\Log\LoggerInterface;
use DigitalMarketingFramework\Core\Model\Queue\JobInterface;
use DigitalMarketingFramework\Core\Queue\QueueInterface;
use DigitalMarketingFramework\Core\Queue\QueueProcessorInterface;
use DigitalMarketingFramework\Core\Tests\ListMapTestTrait;
use DigitalMarketingFramework\Distributor\Core\Factory\QueueDataFactoryInterface;
use DigitalMarketingFramework\Distributor\Core\Model\Configuration\SubmissionConfigurationInterface;
use DigitalMarketingFramework\Distributor\Core\Model\DataSet\SubmissionDataSetInterface;
use DigitalMarketingFramework\Distributor\Core\Registry\RegistryInterface;
use DigitalMarketingFramework\Distributor\Core\Route\RouteInterface;
use DigitalMarketingFramework\Distributor\Core\Service\Relay;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RelayTest extends TestCase
{
    use ListMapTestTrait;

    protected RegistryInterface&MockObject $registry;

    protected LoggerInterface&MockObject $logger;

    protected ContextInterface&MockObject $context;

    protected QueueInterface&MockObject $persistentQueue;

    protected QueueInterface&MockObject $temporaryQueue;

    protected QueueDataFactoryInterface&MockObject $queueDataFactory;

    protected QueueProcessorInterface&MockObject $persistentQueueProcessor;

    protected QueueProcessorInterface&MockObject $temporaryQueueProcessor;

    /** @var array<RouteInterface&MockObject> */
    protected array $routes = [];

    /** @var array<JobInterface&MockObject> */
    protected array $jobs = [];

    /** @var array<mixed> */
    protected array $routeConfigs = [];

    protected SubmissionDataSetInterface&MockObject $submission;

    protected SubmissionConfigurationInterface&MockObject $submissionConfiguration;

    protected Relay $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jobs = [];
        $this->routeConfigs = [];
        $this->routes = [];

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->context = $this->createMock(ContextInterface::class);
        $this->persistentQueue = $this->createMock(QueueInterface::class);
        $this->temporaryQueue = $this->createMock(QueueInterface::class);
        $this->queueDataFactory = $this->createMock(QueueDataFactoryInterface::class);
        $this->persistentQueueProcessor = $this->createMock(QueueProcessorInterface::class);
        $this->temporaryQueueProcessor = $this->createMock(QueueProcessorInterface::class);

        $this->registry = $this->createMock(RegistryInterface::class);
        $this->registry->method('getContext')->willReturn($this->context);
        $this->registry->method('getPersistentQueue')->willReturn($this->persistentQueue);
        $this->registry->method('getNonPersistentQueue')->willReturn($this->temporaryQueue);
        $this->registry->method('getQueueDataFactory')->willReturn($this->queueDataFactory);

        $this->registry->method('getRoutes')->willReturnCallback(function () {
            return $this->routes;
        });

        $this->subject = new Relay($this->registry);
        $this->subject->setLogger($this->logger);
        $this->subject->setContext($this->context);

        $this->registry->method('getQueueProcessor')->willReturnMap([
            [$this->persistentQueue, $this->subject, $this->persistentQueueProcessor],
            [$this->temporaryQueue, $this->subject, $this->temporaryQueueProcessor],
        ]);
    }

    protected function initSubmission(): void
    {
        $this->submissionConfiguration = $this->createMock(SubmissionConfigurationInterface::class);
        $this->submission = $this->createMock(SubmissionDataSetInterface::class);
        $this->submission->method('getConfiguration')->willReturn($this->submissionConfiguration);
    }

    /**
     * @param array<string,mixed> $config
     */
    protected function addRoute(string $keyword, string $id, int $weight, array $config, bool $enabled = true): void
    {
        $this->routeConfigs[$id] = [
            'uuid' => $id,
            'weight' => $weight,
            'value' => $config,
        ];

        $route = $this->createMock(RouteInterface::class);
        $route->method('getKeyword')->willReturn($keyword);
        $route->method('getRouteId')->willReturn($id);
        $route->method('enabled')->willReturn($enabled);
        $route->method('async')->willReturn($config['async'] ?? null);
        $route->method('disableStorage')->willReturn($config['disableStorage'] ?? null);
        $this->routes[] = $route;

        $job = $this->createMock(JobInterface::class);
        $this->jobs[$id] = $job;
    }

    /** @test */
    public function processSyncOneRouteOnePassWithStorage(): void
    {
        $this->initSubmission();
        $this->addRoute('route1', 'routeId1', 10, [
            'async' => false,
            'disableStorage' => false,
        ]);

        $this->logger->expects($this->never())->method('error');

        $this->queueDataFactory
            ->expects($this->exactly(1))
            ->method('convertSubmissionToJob')
            ->withConsecutive(
                [$this->submission, 'routeId1', QueueInterface::STATUS_PENDING]
            )
            ->willReturnOnConsecutiveCalls(...array_values($this->jobs));

        $this->persistentQueue
            ->expects($this->exactly(1))
            ->method('addJob')
            ->withConsecutive(
                [$this->jobs['routeId1']]
            )
            ->willReturnCallback(static function (JobInterface $job) {
                return $job;
            });

        $this->temporaryQueue
            ->expects($this->never())
            ->method('addJob');

        $this->persistentQueueProcessor
            ->expects($this->once())
            ->method('processJobs')
            ->with([
                $this->jobs['routeId1'],
            ]);

        $this->temporaryQueueProcessor
            ->expects($this->never())
            ->method('processJobs');

        $this->subject->process($this->submission);
    }

    /** @test */
    public function processSyncOneRouteOnePassWithoutStorage(): void
    {
        $this->initSubmission();
        $this->addRoute('route1', 'routeId1', 10, [
            'async' => false,
            'disableStorage' => true,
        ]);

        $this->logger->expects($this->never())->method('error');

        $this->queueDataFactory
            ->expects($this->exactly(1))
            ->method('convertSubmissionToJob')
            ->withConsecutive(
                [$this->submission, 'routeId1', QueueInterface::STATUS_PENDING]
            )
            ->willReturnOnConsecutiveCalls(...array_values($this->jobs));

        $this->persistentQueue
            ->expects($this->never())
            ->method('addJob');

        $this->temporaryQueue
            ->expects($this->exactly(1))
            ->method('addJob')
            ->withConsecutive(
                [$this->jobs['routeId1']]
            )
            ->willReturnCallback(static function (JobInterface $job) {
                return $job;
            });

        $this->persistentQueueProcessor
            ->expects($this->never())
            ->method('processJobs');

        $this->temporaryQueueProcessor
            ->expects($this->once())
            ->method('processJobs')
            ->with([
                $this->jobs['routeId1'],
            ]);

        $this->subject->process($this->submission);
    }

    /** @test */
    public function processAsyncOneRouteOnePassWithStorage(): void
    {
        $this->initSubmission();
        $this->addRoute('route1', 'routeId1', 10, [
            'async' => true,
            'disableStorage' => false,
        ]);

        $this->logger->expects($this->never())->method('error');

        $this->queueDataFactory
            ->expects($this->exactly(1))
            ->method('convertSubmissionToJob')
            ->withConsecutive(
                [$this->submission, 'routeId1', QueueInterface::STATUS_QUEUED]
            )
            ->willReturnOnConsecutiveCalls(...array_values($this->jobs));

        $this->persistentQueue
            ->expects($this->exactly(1))
            ->method('addJob')
            ->withConsecutive(
                [$this->jobs['routeId1']]
            )
            ->willReturnCallback(static function (JobInterface $job) {
                return $job;
            });

        $this->temporaryQueue
            ->expects($this->never())
            ->method('addJob');

        $this->persistentQueueProcessor
            ->expects($this->never())
            ->method('processJobs');

        $this->temporaryQueueProcessor
            ->expects($this->never())
            ->method('processJobs');

        $this->subject->process($this->submission);
    }

    /** @test */
    public function processSyncOneRouteWithMultiplePasses(): void
    {
        $this->initSubmission();
        $this->addRoute('route1', 'routeId1', 10, [
            'async' => false,
            'disableStorage' => false,
        ]);
        $this->addRoute('route1', 'routeId2', 20, [
            'async' => false,
            'disableStorage' => false,
        ]);

        $this->logger->expects($this->never())->method('error');

        $this->queueDataFactory
            ->expects($this->exactly(2))
            ->method('convertSubmissionToJob')
            ->withConsecutive(
                [$this->submission, 'routeId1', QueueInterface::STATUS_PENDING],
                [$this->submission, 'routeId2', QueueInterface::STATUS_PENDING]
            )
            ->willReturnOnConsecutiveCalls(...array_values($this->jobs));

        $this->persistentQueue
            ->expects($this->exactly(2))
            ->method('addJob')
            ->withConsecutive(
                [$this->jobs['routeId1']],
                [$this->jobs['routeId2']]
            )
            ->willReturnCallback(static function (JobInterface $job) {
                return $job;
            });

        $this->temporaryQueue
            ->expects($this->never())
            ->method('addJob');

        $this->persistentQueueProcessor
            ->expects($this->once())
            ->method('processJobs')
            ->with([
                $this->jobs['routeId1'],
                $this->jobs['routeId2'],
            ]);

        $this->temporaryQueueProcessor
            ->expects($this->never())
            ->method('processJobs');

        $this->subject->process($this->submission);
    }

    /** @test */
    public function processAsyncOneRouteWithMultiplePasses(): void
    {
        $this->initSubmission();
        $this->addRoute('route1', 'routeId1', 10, [
            'async' => true,
            'disableStorage' => false,
        ]);
        $this->addRoute('route1', 'routeId2', 20, [
            'async' => true,
            'disableStorage' => false,
        ]);

        $this->logger->expects($this->never())->method('error');

        $this->queueDataFactory
            ->expects($this->exactly(2))
            ->method('convertSubmissionToJob')
            ->withConsecutive(
                [$this->submission, 'routeId1', QueueInterface::STATUS_QUEUED],
                [$this->submission, 'routeId2', QueueInterface::STATUS_QUEUED]
            )
            ->willReturnOnConsecutiveCalls(...array_values($this->jobs));

        $this->persistentQueue
            ->expects($this->exactly(2))
            ->method('addJob')
            ->withConsecutive(
                [$this->jobs['routeId1']],
                [$this->jobs['routeId2']]
            )
            ->willReturnCallback(static function (JobInterface $job) {
                return $job;
            });

        $this->temporaryQueue
            ->expects($this->never())
            ->method('addJob');

        $this->persistentQueueProcessor
            ->expects($this->never())
            ->method('processJobs');

        $this->temporaryQueueProcessor
            ->expects($this->never())
            ->method('processJobs');

        $this->subject->process($this->submission);
    }

    /** @test */
    public function processSyncAndAsyncOneRouteWithMultiplePasses(): void
    {
        $this->initSubmission();
        $this->addRoute('route1', 'routeId1', 10, [
            'async' => false,
            'disableStorage' => false,
        ]);
        $this->addRoute('route1', 'routeId2', 20, [
            'async' => true,
            'disableStorage' => false,
        ]);

        $this->logger->expects($this->never())->method('error');

        $this->queueDataFactory
            ->expects($this->exactly(2))
            ->method('convertSubmissionToJob')
            ->withConsecutive(
                [$this->submission, 'routeId1', QueueInterface::STATUS_PENDING],
                [$this->submission, 'routeId2', QueueInterface::STATUS_QUEUED]
            )
            ->willReturnOnConsecutiveCalls(...array_values($this->jobs));

        $this->persistentQueue
            ->expects($this->exactly(2))
            ->method('addJob')
            ->withConsecutive(
                [$this->jobs['routeId1']],
                [$this->jobs['routeId2']]
            )
            ->willReturnCallback(static function (JobInterface $job) {
                return $job;
            });

        $this->temporaryQueue
            ->expects($this->never())
            ->method('addJob');

        $this->persistentQueueProcessor
            ->expects($this->once())
            ->method('processJobs')
            ->with([
                $this->jobs['routeId1'],
            ]);

        $this->temporaryQueueProcessor
            ->expects($this->never())
            ->method('processJobs');

        $this->subject->process($this->submission);
    }

    /** @test */
    public function processAsyncWithoutStorageLogsErrorConvertsToSync(): void
    {
        $this->initSubmission();
        $this->addRoute('route1', 'routeId1', 10, [
            'async' => true,
            'disableStorage' => true,
        ]);

        $this->logger->expects($this->once())->method('error')->with('Async submissions without storage are not possible. Using sync submission instead.');

        $this->queueDataFactory
            ->expects($this->exactly(1))
            ->method('convertSubmissionToJob')
            ->withConsecutive(
                [$this->submission, 'routeId1', QueueInterface::STATUS_PENDING]
            )
            ->willReturnOnConsecutiveCalls(...array_values($this->jobs));

        $this->persistentQueue
            ->expects($this->never())
            ->method('addJob');

        $this->temporaryQueue
            ->expects($this->exactly(1))
            ->method('addJob')
            ->withConsecutive(
                [$this->jobs['routeId1']],
            )
            ->willReturnCallback(static function (JobInterface $job) {
                return $job;
            });

        $this->persistentQueueProcessor
            ->expects($this->never())
            ->method('processJobs');

        $this->temporaryQueueProcessor
            ->expects($this->once())
            ->method('processJobs')
            ->with([
                $this->jobs['routeId1'],
            ]);

        $this->subject->process($this->submission);
    }

    /** @test */
    public function processMixedSyncMixedStorageMultipleRoutesWithMultiplePasses(): void
    {
        $this->initSubmission();
        $this->addRoute('route1', 'routeId1', 10, [
            'async' => false,
            'disableStorage' => false,
        ]);
        $this->addRoute('route1', 'routeId2', 20, [
            'async' => true,
            'disableStorage' => false,
        ]);
        $this->addRoute('route2', 'routeId3', 30, [
            'async' => false,
            'disableStorage' => true,
        ]);
        $this->addRoute('route2', 'routeId4', 40, [
            'async' => true,
            'disableStorage' => true, // should be converted to be sync
        ]);

        $this->logger->expects($this->once())->method('error')->with('Async submissions without storage are not possible. Using sync submission instead.');

        $this->queueDataFactory
            ->expects($this->exactly(4))
            ->method('convertSubmissionToJob')
            ->withConsecutive(
                [$this->submission, 'routeId1', QueueInterface::STATUS_PENDING],
                [$this->submission, 'routeId2', QueueInterface::STATUS_QUEUED],
                [$this->submission, 'routeId3', QueueInterface::STATUS_PENDING],
                [$this->submission, 'routeId4', QueueInterface::STATUS_PENDING]
            )
            ->willReturnOnConsecutiveCalls(...array_values($this->jobs));

        $this->persistentQueue
            ->expects($this->exactly(2))
            ->method('addJob')
            ->withConsecutive(
                [$this->jobs['routeId1']],
                [$this->jobs['routeId2']]
            )
            ->willReturnCallback(static function (JobInterface $job) {
                return $job;
            });

        $this->temporaryQueue
            ->expects($this->exactly(2))
            ->method('addJob')
            ->withConsecutive(
                [$this->jobs['routeId3']],
                [$this->jobs['routeId4']],
            )
            ->willReturnCallback(static function (JobInterface $job) {
                return $job;
            });

        $this->persistentQueueProcessor
            ->expects($this->once())
            ->method('processJobs')
            ->with([
                $this->jobs['routeId1'],
            ]);

        $this->temporaryQueueProcessor
            ->expects($this->once())
            ->method('processJobs')
            ->with([
                $this->jobs['routeId3'],
                $this->jobs['routeId4'],
            ]);

        $this->subject->process($this->submission);
    }

    /** @test */
    public function disabledRouteAsyncWithStorageDoesNotCreateAJobAndIsNotProcessed(): void
    {
        $this->initSubmission();
        $this->addRoute('route1', 'routeId1', 10, [
            'async' => true,
            'disableStorage' => false,
        ], enabled: false);

        $this->logger->expects($this->never())->method('error');

        $this->queueDataFactory->expects($this->never())->method('convertSubmissionToJob');
        $this->persistentQueue->expects($this->never())->method('addJob');
        $this->temporaryQueue->expects($this->never())->method('addJob');
        $this->persistentQueueProcessor->expects($this->never())->method('processJobs');
        $this->temporaryQueueProcessor->expects($this->never())->method('processJobs');

        $this->subject->process($this->submission);
    }

    /** @test */
    public function disabledRouteSyncWithStorageDoesNotCreateAJobAndIsNotProcessed(): void
    {
        $this->initSubmission();
        $this->addRoute('route1', 'routeId1', 10, [
            'async' => false,
            'disableStorage' => false,
        ], enabled: false);

        $this->logger->expects($this->never())->method('error');

        $this->queueDataFactory->expects($this->never())->method('convertSubmissionToJob');
        $this->persistentQueue->expects($this->never())->method('addJob');
        $this->temporaryQueue->expects($this->never())->method('addJob');
        $this->persistentQueueProcessor->expects($this->never())->method('processJobs');
        $this->temporaryQueueProcessor->expects($this->never())->method('processJobs');

        $this->subject->process($this->submission);
    }

    /** @test */
    public function disabledRouteSyncWithoutStorageDoesNotCreateAJobAndIsNotProcessed(): void
    {
        $this->initSubmission();
        $this->addRoute('route1', 'routeId1', 10, [
            'async' => false,
            'disableStorage' => true,
        ], enabled: false);

        $this->logger->expects($this->never())->method('error');

        $this->queueDataFactory->expects($this->never())->method('convertSubmissionToJob');
        $this->persistentQueue->expects($this->never())->method('addJob');
        $this->temporaryQueue->expects($this->never())->method('addJob');
        $this->persistentQueueProcessor->expects($this->never())->method('processJobs');
        $this->temporaryQueueProcessor->expects($this->never())->method('processJobs');

        $this->subject->process($this->submission);
    }
}
