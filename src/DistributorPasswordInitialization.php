<?php

namespace DigitalMarketingFramework\Distributor\PasswordProvider;

use DigitalMarketingFramework\Core\Initialization;
use DigitalMarketingFramework\Core\Registry\RegistryDomain;
use DigitalMarketingFramework\Core\Registry\RegistryInterface;
use DigitalMarketingFramework\Distributor\Core\DataProvider\DataProviderInterface;
use DigitalMarketingFramework\Distributor\PasswordProvider\DataProvider\PasswordDataProvider;
use DigitalMarketingFramework\Distributor\PasswordProvider\Service\PasswordGenerator;

class DistributorPasswordInitialization extends Initialization
{
    public const PLUGINS = [
        RegistryDomain::DISTRIBUTOR => [
            DataProviderInterface::class => [
                PasswordDataProvider::class,
            ],
        ],
    ];

    protected const SCHEMA_MIGRATIONS = [];

    public function __construct(string $packageAlias = '')
    {
        parent::__construct('distributor-password-provider', '1.0.0', $packageAlias);
    }

    protected function getAdditionalPluginArguments(string $interface, string $pluginClass, RegistryInterface $registry): array
    {
        if ($pluginClass === PasswordDataProvider::class) {
            return [$registry->createObject(PasswordGenerator::class)];
        }

        return parent::getAdditionalPluginArguments($interface, $pluginClass, $registry);
    }
}
