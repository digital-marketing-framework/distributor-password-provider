<?php

namespace DigitalMarketingFramework\Distributor\PasswordProvider\DataProvider;

use DigitalMarketingFramework\Core\ConfigurationDocument\SchemaDocument\Schema\ContainerSchema;
use DigitalMarketingFramework\Core\ConfigurationDocument\SchemaDocument\Schema\IntegerSchema;
use DigitalMarketingFramework\Core\ConfigurationDocument\SchemaDocument\Schema\ListSchema;
use DigitalMarketingFramework\Core\ConfigurationDocument\SchemaDocument\Schema\SchemaInterface;
use DigitalMarketingFramework\Core\ConfigurationDocument\SchemaDocument\Schema\StringSchema;
use DigitalMarketingFramework\Core\Context\ContextInterface;
use DigitalMarketingFramework\Distributor\Core\DataProvider\DataProvider;
use DigitalMarketingFramework\Distributor\PasswordProvider\Service\PasswordGenerator;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PasswordDataProvider extends DataProvider
{   
    public const KEY_MIN_LENGTH = 'minLength';
    public const DEFAULT_MIN_LENGTH = 8;
    public const KEY_MAX_LENGTH = 'maxLength';
    public const DEFAULT_MAX_LENGTH = 12;
    public const KEY_ALPHABETS = 'alphabets';
    public const DEFAULT_ALPHABETS = [
        [
            'alphabet' => 'abcdefghijklmnopqrstuvwxyz',
            'min' => 0,
        ],
        [
            'alphabet' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'min' => 0,
        ],
        [
            'alphabet' => '0123456789',
            'min' => 0,
        ],
        [
            'alphabet' => '!#%&/(){}[]+-',
            'min' => 0,
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
        $schema->addProperty(static::KEY_MIN_LENGTH, new IntegerSchema(static::DEFAULT_MIN_LENGTH));
        $schema->addProperty(static::KEY_MAX_LENGTH, new IntegerSchema(static::DEFAULT_MAX_LENGTH));
        
        $alphabetSchema = new ContainerSchema();
        $alphabetSchema->addProperty('alphabet', new StringSchema());
        $alphabetSchema->addProperty('min', new IntegerSchema());
        $alphabetListSchema = new ListSchema($alphabetSchema);
        $alphabetListSchema->setDefaultValue(static::DEFAULT_ALPHABETS);
        $schema->addProperty(static::KEY_ALPHABETS, $alphabetListSchema);
        
        return $schema;
    }
}
