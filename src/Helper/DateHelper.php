<?php

namespace App\Helper;

final class DateHelper
{
    private const DATE_FORMAT = 'Y-m-d\TH:i:s\Z';

    public static function createUtcDate(string $dateString = 'now'): \DateTimeInterface
    {
        return new \DateTime($dateString, new \DateTimeZone('UTC'));
    }

    public static function formatDate(\DateTimeInterface $dateTime): string
    {
        return $dateTime->format(self::DATE_FORMAT);
    }
}
