<?php

namespace App\Model\Data\Properties;

use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SWG\Definition(
 *     definition="Data\Properties\Streaming",
 *     description="Information about the streaming service.",
 *     required={"type", "url"}
 * )
 */
class Streaming
{
    const TYPE_YOUTUBE = 'youtube';
    const TYPE_DASH = 'dash';
    const TYPE_HLS = 'hls';

    const TYPES = [
        self::TYPE_DASH,
        self::TYPE_HLS,
        self::TYPE_YOUTUBE,
    ];

    /**
     * Type of the video stream.
     *
     * @var string
     * @Assert\Choice(
     *     callback="getTypes",
     *     multiple=false,
     * )
     * @JMS\Type("string")
     * @SWG\Property(
     *     enum={"youtube", "dash", "hls"}
     *     example="youtube",
     * )
     */
    public $type;

    /**
     * URL of the video stream.
     *
     * @var string
     * @Assert\Url()
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="https://www.youtube.com/watch?v=M7g7Pfx6zjg",
     * )
     */
    public $url;

    public static function getTypes(): array
    {
        return self::TYPES;
    }
}
