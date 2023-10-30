<?php

namespace DigitalMarketingFramework\Distributor\Core\Factory;

use DigitalMarketingFramework\Core\ConfigurationDocument\ConfigurationDocumentManagerInterface;
use DigitalMarketingFramework\Core\Exception\DigitalMarketingFrameworkException;
use DigitalMarketingFramework\Core\Model\Data\Data;
use DigitalMarketingFramework\Core\Model\Queue\Job;
use DigitalMarketingFramework\Core\Model\Queue\JobInterface;
use DigitalMarketingFramework\Core\Queue\QueueInterface;
use DigitalMarketingFramework\Core\Utility\GeneralUtility;
use DigitalMarketingFramework\Distributor\Core\Model\DataSet\SubmissionDataSet;
use DigitalMarketingFramework\Distributor\Core\Model\DataSet\SubmissionDataSetInterface;

class QueueDataFactory implements QueueDataFactoryInterface
{
    public const KEY_ROUTE_ID = 'routeId';

    public const KEY_SUBMISSION = 'submission';

    public const DEFAULT_LABEL = 'undefined';

    public function __construct(
        protected ConfigurationDocumentManagerInterface $configurationDocumentManager,
    ) {
    }

    protected function createJob(): JobInterface
    {
        return new Job();
    }

    /**
     * @param array{data:array<string,array{type:string,value:mixed}>,configuration:array<string,mixed>,context:array<string,mixed>} $submissionData
     */
    protected function getSubmissionDataHash(array $submissionData): string
    {
        return GeneralUtility::calculateHash($submissionData);
    }

    public function getSubmissionHash(SubmissionDataSetInterface $submission): string
    {
        return $this->getSubmissionDataHash($this->pack($submission));
    }

    public function getJobHash(JobInterface $job): string
    {
        return $this->getSubmissionDataHash($this->getJobSubmissionData($job));
    }

    /**
     * @param array{data:array<string,array{type:string,value:mixed}>,configuration:array<string,mixed>,context:array<string,mixed>} $submissionData
     */
    protected function getSubmissionDataLabel(array $submissionData, string $routeId, string $hash = ''): string
    {
        if ($hash === '') {
            $hash = $this->getSubmissionDataHash($submissionData);
        }

        try {
            $submission = $this->unpack($submissionData);

            return $this->getSubmissionLabel($submission, $routeId, $hash);
        } catch (DigitalMarketingFrameworkException) {
            return static::DEFAULT_LABEL;
        }
    }

    public function getSubmissionLabel(SubmissionDataSetInterface $submission, string $routeId, string $hash = ''): string
    {
        if ($hash === '') {
            $hash = $this->getSubmissionHash($submission);
        }

        return GeneralUtility::shortenHash($hash)
            . '#' . $submission->getConfiguration()->getRouteLabel($routeId);
    }

    public function getJobLabel(JobInterface $job): string
    {
        return $this->getSubmissionDataLabel(
            $this->getJobSubmissionData($job),
            $this->getJobRouteId($job),
            $job->getHash()
        );
    }

    /**
     * @return array{data:array<string,array{type:string,value:mixed}>,configuration:array<string,mixed>,context:array<string,mixed>}
     */
    protected function getJobSubmissionData(JobInterface $job): array
    {
        $jobData = $job->getData();
        if (!isset($jobData[static::KEY_SUBMISSION])) {
            throw new DigitalMarketingFrameworkException('job does not seem to have submission data');
        }

        return $jobData[static::KEY_SUBMISSION];
    }

    public function getJobRouteId(JobInterface $job): string
    {
        $jobData = $job->getData();
        if (!isset($jobData[static::KEY_ROUTE_ID])) {
            throw new DigitalMarketingFrameworkException('job does not seem to have a route id');
        }

        return $jobData[static::KEY_ROUTE_ID];
    }

    public function convertSubmissionToJob(SubmissionDataSetInterface $submission, string $routeId, int $status = QueueInterface::STATUS_QUEUED): JobInterface
    {
        $submissionData = $this->pack($submission);
        $job = $this->createJob();
        $job->setStatus($status);
        $job->setData([
            static::KEY_ROUTE_ID => $routeId,
            static::KEY_SUBMISSION => $submissionData,
        ]);
        $job->setHash($this->getSubmissionDataHash($submissionData));
        $job->setLabel($this->getSubmissionLabel($submission, $routeId, $job->getHash()));

        return $job;
    }

    public function convertJobToSubmission(JobInterface $job): SubmissionDataSetInterface
    {
        return $this->unpack($this->getJobSubmissionData($job));
    }

    /**
     * @return array{data:array<string,array{type:string,value:mixed}>,configuration:array<string,mixed>,context:array<string,mixed>}
     */
    protected function pack(SubmissionDataSetInterface $submission): array
    {
        return [
            'data' => $submission->getData()->pack(),
            'configuration' => $submission->getConfiguration()->getRootConfiguration(),
            'context' => $submission->getContext()->toArray(),
        ];
    }

    /**
     * @param array<mixed> $data
     *
     * @throws DigitalMarketingFrameworkException
     */
    protected function validatePackage(array $data): void
    {
        if ($data === []) {
            throw new DigitalMarketingFrameworkException('job data is empty');
        }

        if (!isset($data['data']) || !is_array($data['data'])) {
            throw new DigitalMarketingFrameworkException('job has no valid submission data');
        }

        if (!isset($data['configuration']) || !is_array($data['configuration'])) {
            throw new DigitalMarketingFrameworkException('job has no valid submission configuration');
        }

        if (!isset($data['context']) || !is_array($data['context'])) {
            throw new DigitalMarketingFrameworkException('job has no valid submission context');
        }
    }

    /**
     * @param array{data:array<string,array{type:string,value:mixed}>,configuration:array<string,mixed>,context:array<string,mixed>} $data
     */
    protected function unpack(array $data): SubmissionDataSetInterface
    {
        $this->validatePackage($data);

        $submissionData = Data::unpack($data['data']);
        $submissionConfiguration = $this->configurationDocumentManager->getConfigurationStackFromConfiguration($data['configuration']);

        return new SubmissionDataSet(
            $submissionData->toArray(),
            $submissionConfiguration,
            $data['context']
        );
    }
}
