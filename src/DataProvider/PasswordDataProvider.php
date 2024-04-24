<?php

namespace DigitalMarketingFramework\Distributor\PasswordProvider\DataProvider;

use DigitalMarketingFramework\Core\SchemaDocument\Schema\ContainerSchema;
use DigitalMarketingFramework\Core\SchemaDocument\Schema\IntegerSchema;
use DigitalMarketingFramework\Core\SchemaDocument\Schema\ListSchema;
use DigitalMarketingFramework\Core\SchemaDocument\Schema\SchemaInterface;
use DigitalMarketingFramework\Core\SchemaDocument\Schema\StringSchema;
use DigitalMarketingFramework\Core\Context\ContextInterface;
use DigitalMarketingFramework\Distributor\Core\DataProvider\DataProvider;
use DigitalMarketingFramework\Distributor\Core\Model\DataSet\SubmissionDataSetInterface;
use DigitalMarketingFramework\Distributor\Core\Registry\RegistryInterface;
use DigitalMarketingFramework\Distributor\PasswordProvider\Service\PasswordGenerator;

class PasswordDataProvider extends DataProvider
{
    public const KEY_FIELDS = 'fields';

    public const KEY_FIELD = 'field';

    public const DEFAULT_FIELD = 'password';

    public const KEY_MIN_LENGTH = 'minLength';

    public const DEFAULT_MIN_LENGTH = 8;

    public const KEY_MAX_LENGTH = 'maxLength';

    public const DEFAULT_MAX_LENGTH = 12;

    public const KEY_ALPHABETS = 'alphabets';

    public const KEY_PASSWORDS_CONTEXT = 'distributorPasswordProvider';

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
        $passwords = [];
        foreach ($this->getListConfig(static::KEY_FIELDS) as $field) {
            if (empty($field['field'])) {
                continue;
            }

            $passwords[$field['field']] = $this->passwordGenerator->generate(
                $this->getConfig(static::KEY_MIN_LENGTH),
                $this->getConfig(static::KEY_MAX_LENGTH),
                $this->getListConfig(static::KEY_ALPHABETS)
            );
        }

        $this->submission->getContext()[self::KEY_PASSWORDS_CONTEXT] = $passwords;
    }

    protected function process(): void
    {
        $passwords = $this->submission->getContext()[self::KEY_PASSWORDS_CONTEXT];
        foreach ($passwords as $field => $password) {
            $this->setField($field, $password);
        }
    }

    public static function getSchema(): SchemaInterface
    {
        /** @var ContainerSchema */
        $schema = parent::getSchema();

        $fieldsSchema = new ContainerSchema();
        $fieldsSchema->addProperty(static::KEY_FIELD, new StringSchema(static::DEFAULT_FIELD));
        $fieldsSchema->getRenderingDefinition()->setLabel('Field');

        $schema->addProperty(static::KEY_FIELDS, new ListSchema($fieldsSchema));
        $schema->addProperty(static::KEY_MIN_LENGTH, new IntegerSchema(static::DEFAULT_MIN_LENGTH));
        $schema->addProperty(static::KEY_MAX_LENGTH, new IntegerSchema(static::DEFAULT_MAX_LENGTH));

        $alphabetSchema = new ContainerSchema();
        $alphabetSchema->addProperty('alphabet', new StringSchema());
        $alphabetSchema->addProperty('min', new IntegerSchema());

        $alphabetListSchema = new ListSchema($alphabetSchema);
        $schema->addProperty(static::KEY_ALPHABETS, $alphabetListSchema);

        return $schema;
    }
}
