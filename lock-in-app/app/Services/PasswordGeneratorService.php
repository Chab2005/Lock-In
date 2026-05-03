<?php

namespace App\Services;

use InvalidArgumentException;

class PasswordGeneratorService
{
    private const UPPERCASE = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    private const LOWERCASE = 'abcdefghijklmnopqrstuvwxyz';

    private const NUMBERS = '0123456789';

    private const SYMBOLS = '!@#$%^&*()_+-=[]{}|;:,.<>?/';

    private const AMBIGUOUS = 'il1Lo0O';

    /**
     * Generate a secure password
     *
     * @param  array  $options  Configuration options
     * @return array Generated password with metadata
     *
     * @throws InvalidArgumentException
     */
    public function generate(array $options): array
    {
        $length = $options['length'] ?? 32;
        $useUppercase = $options['uppercase'] ?? true;
        $useLowercase = $options['lowercase'] ?? true;
        $useNumbers = $options['numbers'] ?? true;
        $useSymbols = $options['symbols'] ?? true;
        $excludeAmbiguous = $options['exclude_ambiguous'] ?? false;

        // Validation
        if ($length < 4 || $length > 128) {
            throw new InvalidArgumentException('Password length must be between 4 and 128.');
        }

        if (! $useUppercase && ! $useLowercase && ! $useNumbers && ! $useSymbols) {
            throw new InvalidArgumentException('At least one character set must be selected.');
        }

        // Build character pool
        $charPool = '';
        $selectedSets = [];

        if ($useUppercase) {
            $charPool .= $useUppercase ? self::UPPERCASE : '';
            $selectedSets['uppercase'] = self::UPPERCASE;
        }
        if ($useLowercase) {
            $charPool .= $useLowercase ? self::LOWERCASE : '';
            $selectedSets['lowercase'] = self::LOWERCASE;
        }
        if ($useNumbers) {
            $charPool .= $useNumbers ? self::NUMBERS : '';
            $selectedSets['numbers'] = self::NUMBERS;
        }
        if ($useSymbols) {
            $charPool .= $useSymbols ? self::SYMBOLS : '';
            $selectedSets['symbols'] = self::SYMBOLS;
        }

        // Remove ambiguous characters if needed
        if ($excludeAmbiguous) {
            $charPool = str_replace(str_split(self::AMBIGUOUS), '', $charPool);
            // Remove duplicates while preserving order
            $charPool = implode('', array_unique(str_split($charPool)));
        }

        // Generate password ensuring all selected sets are represented
        $password = $this->generateSecure($charPool, $selectedSets, $length, $excludeAmbiguous);

        // Calculate entropy and strength
        $entropy = $this->calculateEntropy(strlen($charPool), $length);
        $strength = $this->calculateStrength($entropy, $length);
        $crackTime = $this->estimateCrackTime($entropy);

        return [
            'password' => $password,
            'length' => $length,
            'entropy' => round($entropy, 2),
            'strength' => $strength,
            'crack_time' => $crackTime,
            'combinations' => $this->formatCombinations(strlen($charPool), $length),
            'character_pool_size' => strlen($charPool),
            'selected_sets' => array_keys($selectedSets),
        ];
    }

    /**
     * Generate secure password ensuring all selected sets are represented
     */
    private function generateSecure(
        string $charPool,
        array $selectedSets,
        int $length,
        bool $excludeAmbiguous
    ): string {
        $password = '';
        $poolLength = strlen($charPool);

        // First, ensure at least one character from each selected set
        foreach ($selectedSets as $set) {
            $filteredSet = $set;
            if ($excludeAmbiguous) {
                $filteredSet = str_replace(str_split(self::AMBIGUOUS), '', $set);
            }

            if (strlen($filteredSet) > 0) {
                $index = random_int(0, strlen($filteredSet) - 1);
                $password .= $filteredSet[$index];
            }
        }

        // Fill remaining positions with random characters from full pool
        while (strlen($password) < $length) {
            $index = random_int(0, $poolLength - 1);
            $password .= $charPool[$index];
        }

        // Shuffle the password to avoid predictable pattern
        $passwordArray = str_split($password);
        shuffle($passwordArray);

        return implode('', $passwordArray);
    }

    /**
     * Calculate entropy in bits
     */
    private function calculateEntropy(int $poolSize, int $length): float
    {
        if ($poolSize <= 0 || $length <= 0) {
            return 0;
        }

        return $length * log($poolSize, 2);
    }

    /**
     * Calculate password strength level
     */
    private function calculateStrength(float $entropy, int $length): string
    {
        if ($entropy < 32 || $length < 8) {
            return 'Weak';
        }
        if ($entropy < 64 || $length < 16) {
            return 'Medium';
        }

        return 'Strong';
    }

    /**
     * Estimate time to crack password
     * Assumes 1 billion guesses per second
     */
    private function estimateCrackTime(float $entropy): string
    {
        if ($entropy < 0) {
            return '< 1 second';
        }

        // Total possibilities: 2^entropy
        // Guesses needed (average): 2^entropy / 2
        // Guesses per second: 1,000,000,000
        $totalSeconds = (pow(2, $entropy) / 2) / 1_000_000_000;

        if ($totalSeconds < 1) {
            return '< 1 second';
        }
        if ($totalSeconds < 60) {
            return round($totalSeconds).' seconds';
        }
        if ($totalSeconds < 3600) {
            $minutes = round($totalSeconds / 60);

            return $minutes.' '.($minutes === 1 ? 'minute' : 'minutes');
        }
        if ($totalSeconds < 86400) {
            $hours = round($totalSeconds / 3600);

            return $hours.' '.($hours === 1 ? 'hour' : 'hours');
        }
        if ($totalSeconds < 2592000) {
            $days = round($totalSeconds / 86400);

            return $days.' '.($days === 1 ? 'day' : 'days');
        }
        if ($totalSeconds < 31536000) {
            $months = round($totalSeconds / 2592000);

            return $months.' '.($months === 1 ? 'month' : 'months');
        }

        $years = round($totalSeconds / 31536000);

        return $years.' '.($years === 1 ? 'year' : 'years');
    }

    /**
     * Format the number of combinations in readable form
     */
    private function formatCombinations(int $poolSize, int $length): string
    {
        if ($poolSize <= 0 || $length <= 0) {
            return '0';
        }

        // Calculate 2^(length * log2(poolSize))
        $exponent = $length * log($poolSize, 2);

        if ($exponent > 308) {
            return '> 10^308';
        }

        $combinations = pow($poolSize, $length);

        return $this->formatLargeNumber($combinations);
    }

    /**
     * Format large numbers in scientific notation or readable form
     */
    private function formatLargeNumber(float $num): string
    {
        if ($num < 1000) {
            return (int) $num.'';
        }
        if ($num < 1_000_000) {
            return round($num / 1_000, 1).'K';
        }
        if ($num < 1_000_000_000) {
            return round($num / 1_000_000, 1).'M';
        }
        if ($num < 1_000_000_000_000) {
            return round($num / 1_000_000_000, 1).'B';
        }
        if ($num < 1_000_000_000_000_000) {
            return round($num / 1_000_000_000_000, 1).'T';
        }

        return '> 10^15';
    }

    /**
     * Validate password options
     */
    public function validate(array $options): array
    {
        $errors = [];

        if (isset($options['length'])) {
            $length = (int) $options['length'];
            if ($length < 4 || $length > 128) {
                $errors['length'] = 'Password length must be between 4 and 128.';
            }
        }

        $hasSelection = ($options['uppercase'] ?? false) ||
            ($options['lowercase'] ?? false) ||
            ($options['numbers'] ?? false) ||
            ($options['symbols'] ?? false);

        if (! $hasSelection) {
            $errors['charsets'] = 'At least one character set must be selected.';
        }

        return $errors;
    }
}
