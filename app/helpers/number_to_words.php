<?php
declare(strict_types=1);

function numberToVietnameseWords(int $number): string
{
    if ($number === 0) {
        return 'Không đồng';
    }

    $units = ['', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];
    $placeValues = ['', 'nghìn', 'triệu', 'tỷ'];

    $number = (int) $number;
    $isNegative = $number < 0;
    $number = abs($number);

    if ($number === 0) {
        return 'Không đồng';
    }

    $words = [];
    $placeIndex = 0;

    while ($number > 0) {
        $threeDigits = $number % 1000;
        $number = (int) ($number / 1000);

        if ($threeDigits > 0) {
            $words[] = convertThreeDigits($threeDigits, $units) . ($placeValues[$placeIndex] ? ' ' . $placeValues[$placeIndex] : '');
        }

        $placeIndex++;
    }

    $result = implode(' ', array_reverse($words));
    $result = preg_replace('/\s+/', ' ', $result);
    $result = trim($result);

    if ($isNegative) {
        $result = 'Âm ' . $result;
    }

    return ucfirst($result) . ' đồng';
}

function convertThreeDigits(int $num, array $units): string
{
    $result = [];

    $hundreds = (int) ($num / 100);
    $remainder = $num % 100;
    $tens = (int) ($remainder / 10);
    $ones = $remainder % 10;

    if ($hundreds > 0) {
        $result[] = $units[$hundreds] . ' trăm';
    }

    if ($tens > 0) {
        if ($tens === 1) {
            $result[] = 'mười';
        } else {
            $result[] = $units[$tens] . ' mươi';
        }
    }

    if ($ones > 0) {
        if ($tens === 0 && $hundreds > 0) {
            $result[] = 'lẻ ' . $units[$ones];
        } elseif ($ones === 1) {
            $result[] = ($tens > 0) ? 'mốt' : $units[$ones];
        } elseif ($ones === 5) {
            $result[] = ($tens > 0) ? 'lăm' : $units[$ones];
        } else {
            $result[] = $units[$ones];
        }
    }

    return implode(' ', $result);
}
