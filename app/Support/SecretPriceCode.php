<?php

namespace App\Support;

use App\Models\SecretPriceSetting;
use InvalidArgumentException;

class SecretPriceCode
{
    public static function encode(int|float|string|null $value): string
    {
        $normalized = preg_replace('/\D+/', '', number_format((float) ($value ?? 0), 0, '', ''));

        if (blank($normalized)) {
            return '-';
        }

        $encoded = '';
        $length = strlen($normalized);
        $index = 0;

        while ($index < $length) {
            $digit = $normalized[$index];
            $runLength = 1;

            while (($index + $runLength) < $length && $normalized[$index + $runLength] === $digit) {
                $runLength++;
            }

            $encoded .= self::encodeRun($digit, $runLength, self::digitMap(), self::repeatMap());
            $index += $runLength;
        }

        return $encoded;
    }

    public static function decode(string|null $secret): string
    {
        $secret = trim((string) $secret);

        if ($secret === '') {
            return '0';
        }

        $digitMap = self::digitMap();
        $repeatMap = self::repeatMap();
        $digitTokens = collect($digitMap)
            ->map(fn (string $token, string $digit): array => ['digit' => $digit, 'token' => $token])
            ->sortByDesc(fn (array $item): int => strlen($item['token']))
            ->values()
            ->all();
        $repeatTokens = collect($repeatMap)
            ->map(fn (string $token, int $repeat): array => ['repeat' => $repeat, 'token' => $token])
            ->sortByDesc(fn (array $item): int => strlen($item['token']))
            ->values()
            ->all();

        $result = '';
        $cursor = 0;
        $length = strlen($secret);

        while ($cursor < $length) {
            $digitMatch = null;

            foreach ($digitTokens as $candidate) {
                if (substr($secret, $cursor, strlen($candidate['token'])) === $candidate['token']) {
                    $digitMatch = $candidate;
                    break;
                }
            }

            if ($digitMatch === null) {
                throw new InvalidArgumentException('Kode rahasia tidak valid.');
            }

            $cursor += strlen($digitMatch['token']);
            $repeatCount = 1;

            foreach ($repeatTokens as $candidate) {
                if (substr($secret, $cursor, strlen($candidate['token'])) === $candidate['token']) {
                    $repeatCount = (int) $candidate['repeat'];
                    $cursor += strlen($candidate['token']);
                    break;
                }
            }

            $result .= str_repeat((string) $digitMatch['digit'], $repeatCount);
        }

        return ltrim($result, '0') === '' ? '0' : ltrim($result, '0');
    }

    /** @return array<string, string> */
    public static function digitMap(): array
    {
        return SecretPriceSetting::singleton()->digitMap();
    }

    /** @return array<int, string> */
    public static function repeatMap(): array
    {
        return SecretPriceSetting::singleton()->repeatMap();
    }

    /**
     * @param array<string, string> $digitMap
     * @param array<int, string> $repeatMap
     */
    protected static function encodeRun(string $digit, int $runLength, array $digitMap, array $repeatMap): string
    {
        $baseCode = $digitMap[$digit] ?? $digit;

        if ($runLength <= 1) {
            return $baseCode;
        }

        if ($runLength <= 8) {
            return $baseCode . ($repeatMap[$runLength] ?? '');
        }

        $encoded = '';
        $remaining = $runLength;

        while ($remaining > 0) {
            $chunk = min(8, $remaining);
            $encoded .= $baseCode;

            if ($chunk > 1) {
                $encoded .= $repeatMap[$chunk] ?? '';
            }

            $remaining -= $chunk;
        }

        return $encoded;
    }
}
