<?php

namespace App\Model\Data\Properties;

use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SWG\Definition(
 *     definition="Data\Properties\Subtitles",
 *     description="Object with information on one subtitles track.",
 *     required={"language", "file", "format"}
 * )
 */
class Subtitles
{
    /**
     * Language of the subtitles.
     *
     * @var string
     * @Assert\NotBlank()
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
     * @Assert\NotBlank()
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
     * @Assert\NotBlank()
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="txt"
     * )
     */
    public $format;
}
