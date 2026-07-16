<?php

namespace avadim\FastExcelHelper;

class Helper
{
    public const EXCEL_2007_MAX_ROW = 1048576;
    public const EXCEL_2007_MAX_COL = 16384;


    /**
     * @param string $address
     *
     * @return int
     */
    public static function rowNumber(string $address): ?int
    {
        if (is_numeric($address)) {
            return (int)$address;
        }
        if (preg_match('/^\$?([a-z]+)\$?(\d+)(:\$?([a-z]+)\$?(\d+))?$/i', $address, $m)) {
            return (int)$m[2];
        }

        return 0;
    }

    /**
     * Converts an alphabetic column letter to a number (ONE based)
     *
     * @param string $colLetter
     *
     * @return int
     */
    public static function colNumber(string $colLetter): int
    {
        static $colNumbers = [];

        if (isset($colNumbers[$colLetter])) {
            return $colNumbers[$colLetter];
        }
        if (is_numeric($colLetter)) {
            $colNumbers[$colLetter] = (int)$colLetter;
        }
        else {
            // Strip cell reference down to just letters
            $letters = preg_replace('/[^A-Z]/i', '', strtoupper($colLetter));

            if (strlen($letters) >= 3 && $letters > 'XFD') {
                return self::EXCEL_2007_MAX_COL;
            }
            // Iterate through each letter, starting at the back to increment the value
            for ($index = 0, $i = 0; $letters !== ''; $letters = substr($letters, 0, -1), $i++) {
                $index += (ord(substr($letters, -1)) - 64) * (26 ** $i);
            }

            $colNumbers[$colLetter] = ($index <= self::EXCEL_2007_MAX_COL) ? (int)$index : -1;
        }

        return $colNumbers[$colLetter];
    }

    /**
     * Converts an alphabetic column letter to an index (ONE based)
     *
     * @param $colLetter
     *
     * @return int
     */
    public static function colIndex($colLetter): int
    {
        $colNumber = self::colNumber($colLetter);

        if ($colNumber > 0) {
            return $colNumber - 1;
        }

        return $colNumber;
    }

    /**
     * Convert column number to letter
     *
     * @param int $colNumber ONE based
     *
     * @return string
     */
    public static function colLetter(int $colNumber): string
    {
        static $colLetters = ['',
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
            'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ',
        ];

        if (isset($colLetters[$colNumber])) {
            return $colLetters[$colNumber];
        }

        if ($colNumber > 0 && $colNumber <= self::EXCEL_2007_MAX_COL) {
            $num = $colNumber - 1;
            for ($letter = ''; $num >= 0; $num = (int)($num / 26) - 1) {
                $letter = chr($num % 26 + 0x41) . $letter;
            }
            $colLetters[$colNumber] = $letter;

            return $letter;
        }

        return '';
    }

    /**
     * colLetterNext(2) => 'C'
     * colLetterNext('AB') => 'AC'
     * colLetterNext('FA34') => 'FB34'
     *
     * @param $col
     *
     * @return string
     */
    public static function colLetterNext($col): string
    {
        if (is_numeric($col)) {
            return self::colLetter($col + 1);
        }
        if (preg_match('/^([a-z]+)(\d+)?$/i', $col, $m)) {
            return self::colLetter(self::colNumber($m[1]) + 1) . ($m[2] ?? '');
        }

        return '';
    }

    /**
     * Create cell address by row and col numbers
     *
     * @param int $rowNumber ONE based
     * @param int $colNumber ONE based
     * @param bool|null $absolute
     * @param bool|null $absoluteRow
     *
     * @return string Cell label/coordinates, ex: A1, C3, AA42 (or if $absolute==true: $A$1, $C$3, $AA$42)
     */
    public static function cellAddress(int $rowNumber, int $colNumber, ?bool $absolute = false, ?bool $absoluteRow = null): string
    {
        if ($rowNumber > 0 && $colNumber > 0) {
            $letter = self::colLetter($colNumber);
            if ($letter) {
                if ($absolute) {
                    if (null === $absoluteRow || true === $absoluteRow) {
                        return '$' . $letter . '$' . $rowNumber;
                    }
                    return '$' . $letter . $rowNumber;
                }
                if ($absoluteRow) {
                    return $letter . '$' . $rowNumber;
                }
                return $letter . $rowNumber;
            }
        }

        return '';
    }

    /**
     * Convert values to letters array
     *
     *  Array [0, 1, 2] => ['A', 'B', 'C']
     *  String 'B, E, F' => ['B', 'E', 'F']
     *  String 'B-E, F' => ['B', 'C', 'D', 'E', 'F']
     *  String 'B1-E8' => ['B', 'C', 'D', 'E']
     *  String 'B1:E8' => ['B:E']
     *
     * @param array|string $colKeys
     * @param int|null $baseNum 0 or 1
     *
     * @return array
     */
    public static function colLetterRange($colKeys, ?int $baseNum = 0): array
    {
        if ($colKeys) {
            if (is_array($colKeys)) {
                $key = reset($colKeys);
                if (is_numeric($key)) {
                    $columns = [];
                    foreach ($colKeys as $key) {
                        $columns[] = self::colLetter($key + (1 - $baseNum));
                    }
                    return $columns;
                }
                else {
                    $res = [];
                    foreach ($colKeys as $col) {
                        $res[] = self::colLetterRange($col);
                    }
                    $columns = array_merge(...$res);
                }
                return $columns;
            }
            elseif (is_string($colKeys)) {
                if (strpos($colKeys, ',')) {
                    $colKeys = array_map('trim', explode(',', $colKeys));
                    $columns = [];
                    foreach ($colKeys as $col) {
                        $columns[] = self::colLetterRange($col);
                    }

                    return array_merge(...$columns);
                }
                elseif (strpos($colKeys, '-')) {
                    [$num1, $num2] = explode('-', $colKeys);
                    $columns = [];
                    for ($colNum = self::colNumber($num1); $colNum <= self::colNumber($num2); $colNum++) {
                        $columns[] = self::colLetter($colNum);
                    }
                    return $columns;
                }
                elseif (preg_match('/^[1-9:]+$/', $colKeys)) {
                    [$num1, $num2] = explode(':', $colKeys);
                    return [self::colLetter($num1) . ':' . self::colLetter($num2)];
                }
                elseif (preg_match('/^[a-z1-9:\$]+$/i', $colKeys)) {
                    $colKeys = preg_replace('/\d+|\$/', '', $colKeys);
                    return [strtoupper($colKeys)];
                }
            }
        }
        return [];
    }

    /**
     * @param string $range
     *
     * @return array
     */
    public static function rangeArray(string $range): array
    {
        $result = [];
        if (preg_match('/^(\$)?([a-z]+)(\$)?(\d+)(:(\$)?([a-z]+)(\$)?(\d+))?$/i', $range, $m)) {
            $result['min_col_letter'] = strtoupper($m[2]);
            $result['min_col_num'] = self::colNumber($m[2]);
            $result['min_row_num'] = (int)$m[4];
            $result['min_col_abs'] = $m[1];
            $result['min_row_abs'] = $m[3];
            $result['min_cell'] = strtoupper($m[2] . $m[4]);
            $result['min_cell_abs'] = strtoupper($m[1] . $m[2] . $m[3] . $m[4]);
            if (!empty($m[5])) {
                $result['max_col_letter'] = strtoupper($m[7]);
                $result['max_col_num'] = self::colNumber($m[7]);
                $result['max_row_num'] = (int)$m[9];
                $result['max_col_abs'] = $m[6];
                $result['max_row_abs'] = $m[8];
                $result['max_cell'] = strtoupper($m[7] . $m[9]);
                $result['max_cell_abs'] = strtoupper($m[6] . $m[7] . $m[8] . $m[9]);
            }
            $result['range'] = $result['min_cell'] . ':' . ($result['max_cell'] ?? $result['min_cell']);
            $result['range_abs'] = $result['min_cell_abs'] . ':' . ($result['max_cell_abs'] ?? $result['min_cell_abs']);
        }

        return $result;
    }

    /**
     * addressRC('D8', 'b3') => 'R[5]C[2]'
     * addressRC('B3:D8', 'b3') => 'RC:R[5]C[2]'
     *
     * @param string $address
     * @param string|null $cellAddress
     *
     * @return string
     */
    public static function A1toRC(string $address, ?string $cellAddress = null): string
    {
        $dim = Helper::rangeArray($address);
        if ($cellAddress) {
            $adr = Helper::rangeArray($cellAddress);
            $rowOffset1 = $dim['min_row_num'] - $adr['min_row_num'];
            $colOffset1 = $dim['min_col_num'] - $adr['min_col_num'];

            if ($dim['min_row_abs']) {
                $result = 'R' . $dim['min_row_num'];
            }
            else {
                $result = 'R' . ($rowOffset1 ? '[' . $rowOffset1 . ']' : '');
            }
            if ($dim['min_col_abs']) {
                $result .= 'C' . $dim['min_col_num'];
            }
            else {
                $result .= 'C' . ($colOffset1 ? '[' . $colOffset1 . ']' : '');
            }
            if (strpos($address, ':')) {
                $rowOffset2 = $dim['max_row_num'] - $adr['min_row_num'];
                $colOffset2 = $dim['max_col_num'] - $adr['min_col_num'];

                if ($dim['max_row_abs']) {
                    $result .= ':R' . $dim['max_row_num'];
                }
                else {
                    $result .= ':R' . ($rowOffset2 ? '[' . $rowOffset2 . ']' : '');
                }
                if ($dim['max_col_abs']) {
                    $result .= 'C' . $dim['max_col_num'];
                }
                else {
                    $result .= 'C' . ($colOffset2 ? '[' . $colOffset2 . ']' : '');
                }
            }
        }
        else {
            $result = 'R' . $dim['min_row_num'] . 'C' . $dim['min_col_num'];
            if (strpos($address, ':')) {
                $result .= ':R' . $dim['max_row_num'] . 'C' . $dim['max_col_num'];
            }
        }

        return $result;
    }

    /**
     * RCtoA1('R[5]C[2]', 'B3') => 'D8'
     * RCtoA1('R5C[2]', 'B3') => 'D$5'
     * RCtoA1('R5C2', 'B3') => '$B$5'
     *
     * @param string $address
     * @param string|null $cellAddress
     *
     * @return string
     */
    public static function RCtoA1(string $address, ?string $cellAddress = ''): string
    {
        if (strpos($address, ':')) {
            [$cell1, $cell2] = explode(':', $address);

            return self::shiftAddressRC($cellAddress, $cell1) . ':' . self::shiftAddressRC($cellAddress, $cell2);
        }
        return self::shiftAddressRC($cellAddress, $address);
    }

    /**
     * @param string $cellAddress
     * @param string $range
     *
     * @return bool
     */
    public static function inRange(string $cellAddress, string $range): bool
    {
        $cellArr = self::rangeArray($cellAddress);
        $rangeArr = self::rangeArray($range);

        return $cellArr['min_col_num'] >= $rangeArr['min_col_num']
            && $cellArr['min_col_num'] <= $rangeArr['max_col_num']
            && $cellArr['min_row_num'] >= $rangeArr['min_row_num']
            && $cellArr['min_row_num'] <= $rangeArr['max_row_num'];
    }

    /**
     * Add cell address to range and return new range with cell
     *
     * $cellAddress = 'b2', $targetRange = 'd5'; result => 'B2:D5'
     * $cellAddress = 'C3', $targetRange = 'B3:D3'; result => 'B3:D3' // the range has not changed
     * $cellAddress = 'F5', $targetRange = 'B3:D3'; result => 'B3:F5'
     *
     * $cellAddress = 'd5', $targetRange = 'RC:RC'; result => 'D5:D5'
     * $cellAddress = 'd5', $targetRange = 'B3:D3'; result => 'RC:R2C'
     * $cellAddress = 'd5', $targetRange = 'B3:D3'; result => 'R[-1]C:R2C3'
     *
     * @param string $cellAddress
     * @param string $targetRange
     * @param bool|null $asArray
     *
     * @return array|string
     */
    public static function addToRange(string $cellAddress, string $targetRange, ?bool $asArray = false)
    {
        $cellArr = self::rangeArray($cellAddress);
        if (empty($cellArr['max_col_letter'])) {
            $cellArr['max_col_num'] = $cellArr['min_col_num'];
            $cellArr['max_row_num'] = $cellArr['min_row_num'];
        }
        if (preg_match('/^R\[?(-?\d+)?]?C\[?(-?\d+)?]?/i', $targetRange)) {
            $offsets = self::rangeRelOffsets($targetRange);
            $rangeArr = [
                'min_row_num' => $cellArr['min_row_num'] + $offsets[0],
                'min_col_num' => $cellArr['min_col_num'] + $offsets[1],
                'max_row_num' => $cellArr['min_row_num'] + $offsets[2],
                'max_col_num' => $cellArr['min_col_num'] + $offsets[3],
            ];
        }
        else {
            $rangeArr = self::rangeArray($targetRange);
            if (empty($rangeArr['max_col_letter'])) {
                $rangeArr['max_col_num'] = $rangeArr['min_col_num'];
                $rangeArr['max_row_num'] = $rangeArr['min_row_num'];
            }
        }

        $rangeArr['min_col_num'] = min($cellArr['min_col_num'], $rangeArr['min_col_num']);
        $rangeArr['min_row_num'] = min($cellArr['min_row_num'], $rangeArr['min_row_num']);
        $rangeArr['max_col_num'] = max($cellArr['max_col_num'], $rangeArr['max_col_num']);
        $rangeArr['max_row_num'] = max($cellArr['max_row_num'], $rangeArr['max_row_num']);
        $rangeArr['min_col_letter'] = self::colLetter($rangeArr['min_col_num']);
        $rangeArr['max_col_letter'] = self::colLetter($rangeArr['max_col_num']);
        $rangeArr['min_cell'] = $rangeArr['min_col_letter'] . $rangeArr['min_row_num'];
        $rangeArr['max_cell'] = $rangeArr['max_col_letter'] . $rangeArr['max_row_num'];

        return $asArray ? $rangeArr : $rangeArr['min_cell'] . ':' . $rangeArr['max_cell'];
    }

    /**
     * Return offsets by relative address (zero based)
     *
     * @param string $relAddress
     * @param array|null $absolute
     *
     * @return int[]
     */
    public static function rangeRelOffsets(string $relAddress, ?array &$absolute = []): array
    {
        $rowOffset1 = $colOffset1 = $rowOffset2 = $colOffset2 = null;
        if (preg_match('/^R(\[)?(-?\d+)?]?C(\[)?(-?\d+)?]?(:R(\[)?(-?\d+)?]?C(\[)?(-?\d+)?]?)?$/', strtoupper($relAddress), $matches)) {
            $rowOffset1 = !empty($matches[2]) ? (int)$matches[2] : 0;
            $colOffset1 = !empty($matches[4]) ? (int)$matches[4] : 0;
            if (!empty($matches[5])) {
                $rowOffset2 = !empty($matches[7]) ? (int)$matches[7] : 0;
                $colOffset2 = !empty($matches[9]) ? (int)$matches[9] : 0;
                $absolute = [
                    empty($matches[1]) && !empty($matches[2]),
                    empty($matches[3]) && !empty($matches[4]),
                    empty($matches[6]) && !empty($matches[7]),
                    empty($matches[8]) && !empty($matches[9]),
                ];
            }
            else {
                $rowOffset2 = $rowOffset1;
                $colOffset2 = $colOffset1;
                $absolute = [
                    empty($matches[1]) && !empty($matches[2]),
                    empty($matches[3]) && !empty($matches[4]),
                    empty($matches[1]) && !empty($matches[2]),
                    empty($matches[3]) && !empty($matches[4]),
                ];
            }
        }

        return [
            $rowOffset1,
            $colOffset1,
            $rowOffset2,
            $colOffset2,
        ];
    }

    /**
     * @param string $cellAddress
     * @param int $rowOffset
     * @param int $colOffset
     * @param array|null $absolute
     *
     * @return string
     */
    public static function shiftAddress(string $cellAddress, int $rowOffset, int $colOffset, ?array $absolute = []): string
    {
        if (strpos($cellAddress, ':')) {
            [$cell1, $cell2] = explode(':', $cellAddress);
            return self::shiftAddress($cell1, $rowOffset, $colOffset, $absolute) . ':'
                . self::shiftAddress($cell2, $rowOffset, $colOffset, [!empty($absolute[2]), !empty($absolute[3])]);
        }
        if ($cellAddress) {
            $arr = self::rangeArray($cellAddress);
        }
        else {
            $arr = [
                'min_col_num' => 0,
                'min_row_num' => 0,
            ];
        }

        return (!empty($absolute[1]) ? '$' . self::colLetter($colOffset) : self::colLetter($arr['min_col_num'] + $colOffset))
            . (!empty($absolute[0]) ? '$' . $rowOffset : ($arr['min_row_num'] + $rowOffset));
    }

    /**
     * shiftAddressRC('A1', 'R2C3') => 'D3
     *
     * @param string $cellAddress
     * @param string $addressRC
     *
     * @return string
     */
    public static function shiftAddressRC(string $cellAddress, string $addressRC): string
    {
        $offsets = self::rangeRelOffsets($addressRC, $absolute);

        return self::shiftAddress($cellAddress, $offsets[0], $offsets[1], $absolute);
    }

    /**
     * @param string $rgb
     * @param float $tint
     *
     * @return string
     */
    public static function correctColor(string $rgb, float $tint): string
    {
        $hsl = self::rgbToHsl($rgb);
        // MS excel's tint function expects that HLS is base 240.
        // see: https://social.msdn.microsoft.com/Forums/en-US/e9d8c136-6d62-4098-9b1b-dac786149f43/excel-color-tint-algorithm-incorrect?forum=os_binaryfile#d3c2ac95-52e0-476b-86f1-e2a697f24969
        $HLSMAX = 240;
        $L = $hsl['l'] * $HLSMAX;
        if ($tint < 0) {
            $hsl['l'] = ($L * (1 + $tint)) / $HLSMAX;
        }
        else {
            $hsl['l'] = ($L * (1 - $tint) + ($HLSMAX - $HLSMAX * (1.0 - $tint))) / $HLSMAX;
        }

        return self::hslToRgb($hsl);
    }

    /**
     * @param string $rgb
     *
     * @return array
     */
    public static function rgbToHsl(string $rgb): array
    {
        if ($rgb[0] === '#') {
            $rgb = substr($rgb, 1);
        }

        $r = hexdec(substr($rgb, 0, 2)) / 255;
        $g = hexdec(substr($rgb, 2, 2)) / 255;
        $b = hexdec(substr($rgb, 4, 2)) / 255;

        $min = min($r, $g, $b);
        $max = max($r, $g, $b);
        $delta = $max - $min;

        $l = ($max + $min) / 2;

        $h = 0;
        $s = 0.0;
        if ($delta > 0) {
            $s = ($l <= 0.5) ? ($delta / ($max + $min)) : ($delta / (2 - $max - $min));

            if ($r == $max) {
                $hue = (($g - $b) / 6) / $delta;
            }
            elseif ($g == $max) {
                $hue = (1.0 / 3) + (($b - $r) / 6) / $delta;
            }
            else {
                $hue = (2.0 / 3) + (($r - $g) / 6) / $delta;
            }

            if ($hue < 0) {
                $hue += 1;
            }
            if ($hue > 1) {
                $hue -= 1;
            }

            $h = (int)($hue * 360);
        }
        $s = round($s, 3);
        $l = round($l, 6);

        return ['h' => $h, 's' => $s, 'l' => $l];
    }

    /**
     * @param array $hsl
     *
     * @return string
     */
    public static function hslToRgb (array $hsl): string
    {
        $h = $hsl['h'];
        $s = $hsl['s'];
        $l = $hsl['l'];

        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod(($h / 60), 2) - 1));
        $m = $l - ($c / 2);

        if ($h < 60) {
            $r = $c;
            $g = $x;
            $b = 0;
        }
        elseif ($h < 120) {
            $r = $x;
            $g = $c;
            $b = 0;
        }
        elseif ($h < 180) {
            $r = 0;
            $g = $c;
            $b = $x;
        }
        elseif ($h < 240) {
            $r = 0;
            $g = $x;
            $b = $c;
        }
        elseif ($h < 300) {
            $r = $x;
            $g = 0;
            $b = $c;
        }
        else {
            $r = $c;
            $g = 0;
            $b = $x;
        }

        $r = (int)floor((($r + $m) < 0) ? 0 : ($r + $m) * 255);
        $g = (int)floor((($g + $m) < 0) ? 0 : ($g + $m) * 255);
        $b = (int)floor((($b + $m) < 0) ? 0 : ($b + $m) * 255);

        return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT)
            . str_pad(dechex($g), 2, '0', STR_PAD_LEFT)
            . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
    }


    /**
     * @param string $str
     *
     * @return string
     */
    public static function escapeString(string $str): string
    {
        return preg_replace_callback('/(_x[0-9A-Fa-f]{4}_)/', function($matches) {
            return '_x005F' . $matches[1];
        }, $str);
    }

    /**
     * @param string $str
     *
     * @return string
     */
    public static function unescapeString(string $str): string
    {
        return preg_replace_callback('/_x005F(_x[0-9A-Fa-f]{4}_)/', function($matches) {
            return $matches[1];
        }, $str);
    }

}