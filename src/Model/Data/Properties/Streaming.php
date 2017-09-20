<?php
namespace App\Model\Data\Properties;

use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SWG\Definition(
 *     definition="Data\Properties\Streaming",
 *     description="Information about the streaming service.",
 *     required={}
 * )
 */
class Streaming
{
    /**
     * URL of the video stream type (youtube, dash, hls).
     *
     * @var string
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="youtube",
     * )
     */
    public $type;

    /**
     * URL of the video stream.
     *
     * @var string
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="https://www.youtube.com/watch?v=M7g7Pfx6zjg",
     * )
     */
    public $resolution;
}