<?php

namespace KCore\ThumbnailsAPIBundle\Services;

use Symfony\Component\HttpFoundation\File\File;

interface ThumbnailGeneratorInterface
{
    /**
     * Creates a thumbnail for the given sourceFile.
     *
     * @param File   $sourceFile        The File to create the thumbnail from
     * @param string $thumbnailFilename The filename where to store the generated thumbnail
     * @param int    $width             The thumbnail width
     * @param int    $height            The thumbnail height
     * @param string $format            The thumbnail image format
     *
     * @return
     */
    public function generateThumbnail(
        File $sourceFile,
        $thumbnailFilename,
        $width = 500,
        $height = 500,
        $format = 'png'
    );
}
