<?php

namespace DigitalMarketingFramework\Distributor\PasswordProvider;

use DigitalMarketingFramework\Core\Initialization;
use DigitalMarketingFramework\Core\Registry\RegistryDomain;
use DigitalMarketingFramework\Distributor\Core\DataProvider\DataProviderInterface;
use DigitalMarketingFramework\Distributor\PasswordProvider\DataProvider\PasswordDataProvider;

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

    public function __construct()
    {
        parent::__construct('distributor-password-provider', '1.0.0');
    }
}
