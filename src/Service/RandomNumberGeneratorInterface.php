<?php

namespace DigitalMarketingFramework\Distributor\Password\Service;

interface RandomNumberGeneratorInterface
{
    public function generate(int $min, int $max): int;
}
