<?php
namespace App\Model\Data\Properties;

use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SWG\Definition(
 *     definition="Data\Properties\Audio",
 *     description="Object with information on one audio track",
 *     required={}
 * )
 */
class Audio
{
    /**
     * Main language(s) spoken in the audio track, free text.
     *
     * @var string
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="es",
     * )
     */
    public $language;

    /**
     * Bitrate of the audio track.
     *
     * @var string
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="1 Mbps",
     * )
     */
    public $bitrate;

    /**
     * Format of the audio track.
     *
     * @var string
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="mp3",
     * )
     */
    public $format;
}