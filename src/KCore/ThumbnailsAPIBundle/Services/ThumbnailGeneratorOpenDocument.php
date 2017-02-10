<?php

namespace KCore\ThumbnailsAPIBundle\Services;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\File;

class ThumbnailGeneratorOpenDocument implements ThumbnailGeneratorInterface
{
    /**
     * @var int
     */
    private $timeout = null;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ThumbnailGeneratorOpenDocument constructor.
     *
     * @param int             $timeout Timeout for thumbnail generation process
     * @param LoggerInterface $logger  The logger interface
     */
    public function __construct($timeout, $logger)
    {
        $this->timeout = $timeout;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function generateThumbnail(File $sourceFile, $thumbnailFilename, $width = 500, $height = 500, $format = 'png')
    {
        $zip = new \ZipArchive();
        if (true === $zip->open($sourceFile)) {
            // Access the default builtin preview
            $content = $zip->getFromName('Thumbnails/thumbnail.png');
            $zip->close();
            if ($content) {
                $sourceImage = imagecreatefromstring($content);
                if (!$sourceImage) {
                    $this->logger->error('Error generating thumbnail for {sourceFile}', [
                        'sourceFile' => $sourceFile->getFilename(),
                    ]);
                    throw new \RuntimeException('Error generating thumbnail for '.$sourceFile->getFilename());
                }

                $thumbnailImage = imagecreatetruecolor($width, $height);
                $size = getimagesizefromstring($content);

                // PNG alpha settings
                imagealphablending($thumbnailImage, false);
                imagesavealpha($thumbnailImage, true);

                // Resizing the original image
                imagecopyresampled(
                    $thumbnailImage,
                    $sourceImage,
                    0, 0, // int $dst_x , int $dst_y
                    0, 0, // int $src_x , int $src_y
                    $width, $height, // int $dst_w , int $dst_h
                    $size[0], $size[1] // int $src_w , int $src_h
                    );

                // Save the image
                return imagepng($thumbnailImage, $thumbnailFilename, 9);

                /*
                 * ImageMagick is not enabled
                 */
                /*
                $image = new \Imagick();
                $image->readImageBlob($content);
                $image->resizeImage($width, $height, \Imagick::FILTER_CATROM, 1);
                $image->setFormat('png');
                return !!file_put_contents($thumbnailFilename, $image->getImageBlob());
                */
            }
        }
    }
}
