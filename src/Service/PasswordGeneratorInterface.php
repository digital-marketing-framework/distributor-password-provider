<?php

namespace DigitalMarketingFramework\Distributor\PasswordProvider\Service;

interface PasswordGeneratorInterface
{
    /**
     * @param array<array{alphabet:string,min:int}> $alphabetOptions
     */
    public function generate(int $minLength, int $maxLength, array $alphabetOptions = []): string;
}
