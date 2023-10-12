<?php

namespace DigitalMarketingFramework\Distributor\PasswordProvider\DataProvider;

use DigitalMarketingFramework\Core\ConfigurationDocument\SchemaDocument\Schema\Custom\ValueSchema;
use DigitalMarketingFramework\Core\ConfigurationDocument\SchemaDocument\Schema\CustomSchema;
use DigitalMarketingFramework\Core\ConfigurationDocument\SchemaDocument\Schema\MapSchema;
use DigitalMarketingFramework\Core\ConfigurationDocument\SchemaDocument\Schema\SchemaInterface;
use DigitalMarketingFramework\Distributor\Core\DataProvider\DataProvider;
use DigitalMarketingFramework\Distributor\PasswordProvider\Service\PasswordService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PasswordDataProvider extends DataProvider implements DataProcessorAwareInterface
{
    public const KEY_PASSWORDS = 'passwords';
    public const DEFAULT_PASSWORDS = [
        'password' => [
            'minLength' => 8,
            'maxLength' => 12,
            'alphabetOptions' => [],
        ],
    ];
    
    /** @var PasswordGeneratorInterface */
    protected $passwordGenerator;
    
    public function __construct(ClassRegistryInterface $registry, LoggerInterface $logger, ?PasswordGeneratorInterface $passwordGenerator = null)
    {
        parent::__construct($registry, $logger);
        $this->passwordGenerator = $passwordGenerator ?? new PasswordGenerator();
    }
    
    protected function processContext(ContextInterface $context): void
    {
        $passwords = $this->getConfig(static::KEY_PASSWORDS);
        foreach($passwords as $field => $generatorOptions) {
            $password = $this->passwordGenerator->generate(
                $generatorOptions
            );
            $submission->getContext()['passwords'][$field] = $password;
        }
    }
    
    protected function process(SubmissionInterface $submission): void
    {
        foreach($submission->getContext()['passwords'] as $field => $password) {
            $this->setField($submission, $field, $password);
        }
    }
    
    public static function getSchema(): SchemaInterface
    {
        $schema = parent::getSchema();
        $schema->addProperty(static::KEY_PASSWORDS, new MapSchema(new CustomSchema(ValueSchema::TYPE), new StringSchema('mapKey'), static::DEFAULT_PASSWORDS));
    
        return $schema;
    }
}
