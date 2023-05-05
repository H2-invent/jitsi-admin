<?php

namespace App\Util;

use InvalidArgumentException;

class CsvHandler
{
    private static string $DEFAULT_SEPERATOR = ',';
    public static string $ARRAY_NOT_MULTIDIMENSIONAL = 'Input array must be multidimensional';
    public static string $ARRAY_LAYERS_NOT_EQUAL = 'Input array must have equally build layers';
    public static string $CSV_LINE_MULTIDIMENSIONAL = 'A single CSV line must NOT be multidimensional';
    public static string $ARRAY_NOT_ASSOCIATIVE = 'Input array must be associative';

    public static function generateFromArray(array $data, ?string $seperator = null): array
    {
        if (!self::checkArrayIsMultiDimensional($data)) {
            throw new InvalidArgumentException(self::$ARRAY_NOT_MULTIDIMENSIONAL);
        }

        if (!self::checkMultiDimensionalArrayHasEqualLayers($data)) {
            throw new InvalidArgumentException(self::$ARRAY_LAYERS_NOT_EQUAL);
        }

        $seperator = $seperator ?? self::$DEFAULT_SEPERATOR;

        $csv = [self::getCsvLineFromArray(array_keys($data[0]), $seperator)];

        foreach ($data as $line) {
            $csv[] = self::getCsvLineFromArray($line, $seperator);
        }

        return $csv;
    }

    private static function getCsvLineFromArray(array $csvLine, string $seperator): string
    {
        if (self::checkArrayIsMultiDimensional($csvLine)) {
            throw new InvalidArgumentException(self::$CSV_LINE_MULTIDIMENSIONAL);
        }

        return implode($seperator, $csvLine);
    }

    private static function checkArrayIsMultiDimensional(array $arrayToCheck): bool
    {
        return !(count($arrayToCheck) === count($arrayToCheck, COUNT_RECURSIVE));
    }

    private static function checkMultiDimensionalArrayHasEqualLayers(array $arrayToCheck): bool
    {
        $lastDimension = null;

        foreach ($arrayToCheck as $currentDimension) {
            if(!self::checkArrayAssociative($currentDimension)) {
                throw new InvalidArgumentException(self::$ARRAY_NOT_ASSOCIATIVE);
            }

            if ($lastDimension === null) {
                $lastDimension = $currentDimension;
                continue;
            }

            if (count($lastDimension) !== count($currentDimension)
                || count(array_diff_key($lastDimension, $currentDimension)) > 0
            ) {
                return false;
            }

            $lastDimension = $currentDimension;
        }

        return true;
    }

    private static function checkArrayAssociative(array $arrayToCheck): bool
    {
        return (
            count($arrayToCheck) !== 0
            && array_keys($arrayToCheck) !== range(0, count($arrayToCheck) - 1)
        );
    }
}
