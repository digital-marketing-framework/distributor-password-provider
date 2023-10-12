<?php

namespace DigitalMarketingFramework\Distributor\PasswordProvider\DataProvider;

use DigitalMarketingFramework\Core\ConfigurationDocument\SchemaDocument\Schema\Custom\ValueSchema;
use DigitalMarketingFramework\Core\ConfigurationDocument\SchemaDocument\Schema\CustomSchema;
use DigitalMarketingFramework\Core\ConfigurationDocument\SchemaDocument\Schema\MapSchema;
use DigitalMarketingFramework\Core\ConfigurationDocument\SchemaDocument\Schema\SchemaInterface;
use DigitalMarketingFramework\Core\ConfigurationDocument\SchemaDocument\Schema\StringSchema;
use DigitalMarketingFramework\Core\Context\ContextInterface;
use DigitalMarketingFramework\Distributor\Core\DataProvider\DataProvider;
use DigitalMarketingFramework\Distributor\PasswordProvider\Service\PasswordGenerator;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PasswordDataProvider extends DataProvider
{
    public const KEY_PASSWORDS = 'passwords';
    public const DEFAULT_PASSWORDS = [
        'password' => [
            'minLength' => 8,
            'maxLength' => 12,
            'alphabetOptions' => [],
        ],
    ];
    
    public function __construct(
        string $keyword,
        RegistryInterface $registry,
        SubmissionDataSetInterface $submission,
        protected PasswordGenerator $passwordGenerator
    ) {
        parent::__construct($keyword, $registry, $submission);
    }
    
    protected function processContext(ContextInterface $context): void
    {
        $passwords = $this->getMapConfig(static::KEY_PASSWORDS);
        foreach($passwords as $field => $generatorOptions) {
            $password = $this->passwordGenerator->generate(
                $generatorOptions
            );
            $this->submission->getContext()['passwords'][$field] = $password;
        }
    }
    
    protected function process(): void
    {
        foreach($this->submission->getContext()['passwords'] as $field => $password) {
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
