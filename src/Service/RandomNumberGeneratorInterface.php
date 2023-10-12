<?php

namespace DigitalMarketingFramework\Distributor\PasswordProvider\Service;

interface RandomNumberGeneratorInterface
{
    public function generate(int $min, int $max): int;
}
