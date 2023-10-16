<?php

namespace DigitalMarketingFramework\Distributor\PasswordProvider\Service;

use DigitalMarketingFramework\Distributor\PasswordProvider\Utility\PasswordUtility;

class PasswordGenerator implements PasswordGeneratorInterface
{
    /** @var RandomNumberGeneratorInterface */
    protected $rng;

    /** @var array<int> */
    protected $passwordIndices;

    /** @var array<string> */
    protected $password;

    public function __construct(?RandomNumberGeneratorInterface $rng = null)
    {
        $this->rng = $rng ?? new RandomNumberGenerator();
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
        return count($this->passwordIndices) > 0;
    }

    /**
     * @param string $minLength
     * @param string $minLength
     * @param array<mixed> $alphabetOptions
     * @return string
     */
    public function generate($minLength, $maxLength, $alphabetOptions = []): string
    {
        $this->init($minLength, $maxLength);

        $allAlphabets = '';
        foreach ($alphabetOptions as $key => $alphabetOption) {
            $alphabet = is_string($alphabetOption) ? $alphabetOption : $alphabetOption['alphabet'];
            $min = is_string($alphabetOption) ? 0 : $alphabetOption['min'];
            while ($min > 0) {
                $this->addCharacter(PasswordUtility::getRandomCharacter($this->rng, $alphabet));
                $min--;
            }
            $allAlphabets .= $alphabet;
        }

        while ($this->moreCharactersNeeded()) {
            $this->addCharacter(PasswordUtility::getRandomCharacter($this->rng, $allAlphabets));
        }

        return implode('', $this->password);
    }
}
