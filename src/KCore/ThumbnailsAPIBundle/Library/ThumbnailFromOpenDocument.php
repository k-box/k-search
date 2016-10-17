<?php

namespace KCore\ThumbnailsAPIBundle\Library;

use Symfony\Component\HttpFoundation\File\File;

class ThumbnailFromOpenDocument
{
    /**
     * Creates a thumbnail for the given sourceFile.
     *
     * @param File   $sourceFile        The File to create the thumbnail from
     * @param string $thumbnailFilename The output filename
     * @param int    $width             The thumbnail width
     * @param int    $height            The thumbnail height
     *
     * @return bool Returns true if the thumbnail was created correctly
     */
    public static function generateThumbnail(File $sourceFile, $thumbnailFilename, $width = 500, $height = 400)
    {
        $zip = new \ZipArchive();
        if (true === $zip->open($sourceFile)) {
            // Access the default builtin preview
            $content = $zip->getFromName('Thumbnails/thumbnail.png');
            $zip->close();
            if ($content) {
                $sourceImage = imagecreatefromstring($content);
                if (!$sourceImage) {
                    throw new \RuntimeException();
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
