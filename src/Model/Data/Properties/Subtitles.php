<?php

namespace App\Model\Data\Properties;

use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;

/**
 * @SWG\Definition(
 *     definition="Data\Properties\Subtitles",
 *     description="Object with information on one subtitles track.",
 *     required={}
 * )
 */
class Subtitles
{
    /**
     * Language of the subtitles.
     *
     * @var string
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="en",
     * )
     */
    public $language;

    /**
     * The URI where the subtitle file is stored and retrievable (or "built-in for wrapped subtitles").
     *
     * @var string
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="http://opensubtitles.org/get/uniq_id"
     * )
     */
    public $file;

    /**
     * Format of the subtitles track.
     *
     * @var string
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="txt"
     * )
     */
    public $format;
}
