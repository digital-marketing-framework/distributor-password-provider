<?php

namespace DigitalMarketingFramework\Distributor\Password\Service;

interface PasswordGeneratorInterface
{
    /**
     * @param array<mixed> $options
     * @return string
     */
    public function generate(array $options = []): string;
}
