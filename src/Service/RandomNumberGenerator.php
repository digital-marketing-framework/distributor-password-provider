<?php

namespace DigitalMarketingFramework\Distributor\PasswordProvider\Service;

use DigitalMarketingFramework\Core\Exception\DigitalMarketingFrameworkException;

class RandomNumberGenerator implements RandomNumberGeneratorInterface
{
    public function generate(int $min, int $max): int
    {
        try {
            return random_int($min, $max);
        } catch (\Exception $e) {
            throw new DigitalMarketingFrameworkException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
