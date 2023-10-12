<?php

namespace DigitalMarketingFramework\Distributor\Password\Utility;

use DigitalMarketingFramework\Distributor\Password\Service\RandomNumberGeneratorInterface;

class PasswordUtility
{
    /**
     * @param RandomNumberGeneratorInterface $rng
     * @param array<int, int|string> $array
     * @return array<int|string>
     */
    public static function shuffleArray(RandomNumberGeneratorInterface $rng, array $array): array
    {
        $result = [];
        while (count($array) > 0) {
            $array = array_values($array);
            $index = $rng->generate(0, count($array) - 1);
            $result[] = $array[$index];
            unset($array[$index]);
        }
        return $result;
    }

    public static function shuffleString(RandomNumberGeneratorInterface $rng, string $string): string
    {
        if (function_exists('mb_str_split')) {
            $input = mb_str_split($string);
        } else {
            $input = str_split($string);
        }
        $result = static::shuffleArray($rng, $input);
        return implode('', $result);
    }

    public static function getRandomCharacter(RandomNumberGeneratorInterface $rng, string $alphabet): string
    {
        return substr($alphabet, $rng->generate(0, strlen($alphabet) - 1), 1);
    }

    public static function generateRandomString(RandomNumberGeneratorInterface $rng, int $length, string $alphabet): string
    {
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= static::getRandomCharacter($rng, $alphabet);
        }
        return $result;
    }
}
