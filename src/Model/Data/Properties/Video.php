<?php
namespace App\Model\Data\Properties;

use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SWG\Definition(
 *     definition="Data\Properties\Video",
 *     description="Object containing information on the video file.",
 *     required={"duration", "source"}
 * )
 */
class Video
{
    /**
     * Duration of the video.
     *
     * @var string
     * @Assert\NotBlank()
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="11:13 min",
     * )
     */
    public $duration;

    /**
     * Information about the source file.
     *
     * @var Source
     * @Assert\NotBlank()
     * @Assert\Valid()
     * @JMS\Type("App\Model\Data\Properties\Source")
     * @SWG\Property()
     */
    public $source;

    /**
     * Information about the streaming services.
     *
     * @var Streaming[]
     * @JMS\Type("array<App\Model\Data\Properties\Streaming>")
     * @SWG\Property()
     */
    public $streaming;
}