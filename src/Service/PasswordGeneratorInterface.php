<?php

namespace DigitalMarketingFramework\Distributor\PasswordProvider\Service;

interface PasswordGeneratorInterface
{
    /**
     * @param string $minLength
     * @param string $minLength
     * @param array<mixed> $alphabetOptions
     * @return string
     */
    public function generate($minLength, $maxLength, $alphabetOptions = []): string;
}
