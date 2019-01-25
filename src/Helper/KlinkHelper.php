<?php

namespace App\Helper;

use App\Exception\InvalidKlinkException;

final class KlinkHelper
{
    public static function get(string $id): array
    {
        return [
            'id' => $id,
            'name' => "K-Link name $id",
        ];
    }

    public static function validateKlinksForApplication(array $requestedKlinks, array $applicationKlinks)
    {
        if (empty($applicationKlinks)) {
            throw new InvalidKlinkException('Application not allowed to publish on K-Link');
        }

        if (\count(array_intersect($requestedKlinks, $applicationKlinks)) !== \count($requestedKlinks)) {
            throw new InvalidKlinkException('Some K-Links are invalid');
        }
    }

    /**
     * Verify that the specified K-Link is valid, and in case is empty a default value is correctly set.
     */
    public static function ensureKlinkIsValid(array $requestedKlinks, array $applicationKlinks): array
    {
        $valid = self::validateKlinksForApplication($requestedKlinks, $applicationKlinks);

        if (empty($requestedKlinks) && 1 === \count($applicationKlinks)) {
            return $applicationKlinks[0];
        }

        return $requestedKlinks;
    }
}
