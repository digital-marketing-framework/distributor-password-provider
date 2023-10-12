<?php

namespace DigitalMarketingFramework\Distributor\PasswordProvider\Service;

interface PasswordGeneratorInterface
{
    /**
     * @param array<mixed> $options
     * @return string
     */
    public function generate(array $options = []): string;
}
