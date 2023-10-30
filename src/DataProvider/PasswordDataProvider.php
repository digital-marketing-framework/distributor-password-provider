<?php

namespace DigitalMarketingFramework\Distributor\PasswordProvider\DataProvider;

use DigitalMarketingFramework\Core\ConfigurationDocument\SchemaDocument\Schema\ContainerSchema;
use DigitalMarketingFramework\Core\ConfigurationDocument\SchemaDocument\Schema\IntegerSchema;
use DigitalMarketingFramework\Core\ConfigurationDocument\SchemaDocument\Schema\ListSchema;
use DigitalMarketingFramework\Core\ConfigurationDocument\SchemaDocument\Schema\SchemaInterface;
use DigitalMarketingFramework\Core\ConfigurationDocument\SchemaDocument\Schema\StringSchema;
use DigitalMarketingFramework\Core\Context\ContextInterface;
use DigitalMarketingFramework\Distributor\Core\DataProvider\DataProvider;
use DigitalMarketingFramework\Distributor\Core\Model\DataSet\SubmissionDataSetInterface;
use DigitalMarketingFramework\Distributor\Core\Registry\RegistryInterface;
use DigitalMarketingFramework\Distributor\PasswordProvider\Service\PasswordGenerator;

class PasswordDataProvider extends DataProvider
{
    public const KEY_FIELD = 'field';

    public const DEFAULT_FIELD = 'password';

    public const KEY_MIN_LENGTH = 'minLength';

    public const DEFAULT_MIN_LENGTH = 8;

    public const KEY_MAX_LENGTH = 'maxLength';

    public const DEFAULT_MAX_LENGTH = 12;

    public const KEY_ALPHABETS = 'alphabets';

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
    }

    protected function process(): void
    {
        $password = $this->passwordGenerator->generate(
            $this->getConfig(static::KEY_MIN_LENGTH),
            $this->getConfig(static::KEY_MAX_LENGTH),
            $this->getListConfig(static::KEY_ALPHABETS)
        );
        $this->setField($this->getConfig(static::KEY_FIELD), $password);
    }

    public static function getSchema(): SchemaInterface
    {
        /** @var ContainerSchema */
        $schema = parent::getSchema();
        $schema->addProperty(static::KEY_FIELD, new StringSchema(static::DEFAULT_FIELD));
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
