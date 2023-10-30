<?php

namespace DigitalMarketingFramework\Distributor\PasswordProvider\Service;

use DigitalMarketingFramework\Distributor\PasswordProvider\Utility\PasswordUtility;

class PasswordGenerator implements PasswordGeneratorInterface
{
    /** @var array<int> */
    protected array $passwordIndices = [];

    /** @var array<string> */
    protected array $password = [];

    public function __construct(protected RandomNumberGeneratorInterface $rng = new RandomNumberGenerator())
    {
    }

    protected function init(int $minLength, int $maxLength): int
    {
        $length = $this->rng->generate($minLength, $maxLength);
        $this->password = array_fill(0, $length, '');
        $this->passwordIndices = PasswordUtility::shuffleArray($this->rng, array_keys($this->password));

        return $length;
    }

    protected function addCharacter(string $character): bool
    {
        $index = array_shift($this->passwordIndices);
        if ($index !== null) {
            $this->password[$index] = $character;

            return true;
        }

        return false;
    }

    protected function moreCharactersNeeded(): bool
    {
        return $this->passwordIndices !== [];
    }

    /**
     * @param array<array{alphabet:string,min:int}> $alphabetOptions
     */
    public function generate(int $minLength, int $maxLength, array $alphabetOptions = []): string
    {
        $this->init($minLength, $maxLength);

        $allAlphabets = '';
        foreach ($alphabetOptions as $alphabetOption) {
            $alphabet = $alphabetOption['alphabet'];
            $min = $alphabetOption['min'];
            while ($min > 0) {
                $this->addCharacter(PasswordUtility::getRandomCharacter($this->rng, $alphabet));
                --$min;
            }

            $allAlphabets .= $alphabet;
        }

        while ($this->moreCharactersNeeded()) {
            $this->addCharacter(PasswordUtility::getRandomCharacter($this->rng, $allAlphabets));
        }

        return implode('', $this->password);
    }
}
