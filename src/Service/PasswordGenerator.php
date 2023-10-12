<?php

namespace DigitalMarketingFramework\Distributor\Password\Service;

use DigitalMarketingFramework\Distributor\Password\Utility\PasswordUtility;

class PasswordGenerator implements PasswordGeneratorInterface
{
    public const DEFAULT_MIN_LENGTH = 8;
    public const DEFAULT_MAX_LENGTH = 12;

    public const DEFAULT_ALPHABETS = [
        'abcdefghijklmnopqrstuvwxyz',
        'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        '0123456789',
        '!#%&/(){}[]+-',
    ];

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
     * @return array<int, array<string, mixed>>
     */
    public static function getDefaultAlphabetOptions(): array
    {
        $options = [];
        foreach (static::DEFAULT_ALPHABETS as $alphabet) {
            $options[] = [
                'alphabet' => $alphabet,
                'min' => 0,
            ];
        }
        return $options;
    }

    /**
     * @param array<mixed> $options
     * @return string
     */
    public function generate(array $options = []): string
    {
        $minLength = $options['minLength'] ?? static::DEFAULT_MIN_LENGTH;
        $maxLength = $options['maxLength'] ?? static::DEFAULT_MAX_LENGTH;
        $alphabetOptions = isset($options['alphabetOptions']) && !empty($options['alphabetOptions'])
            ? $options['alphabetOptions']
            : static::getDefaultAlphabetOptions();

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
