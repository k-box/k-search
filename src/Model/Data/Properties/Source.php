<?php

namespace App\Model\Data\Properties;

use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SWG\Definition(
 *     definition="Data\Properties\Source",
 *     description="Information about the source file.",
 *     required={"format", "resolution"}
 * )
 */
class Source
{
    /**
     * Format of the video file.
     *
     * @var string
     * @Assert\NotBlank()
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="mkv",
     * )
     */
    public $format;

    /**
     * Resolution of the video.
     *
     * @var string
     * @Assert\NotBlank()
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="1080p",
     * )
     */
    public $resolution;

    /**
     * Bitrate of the video file.
     *
     * @var string
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="8 Mbps",
     * )
     */
    public $bitrate;
}
