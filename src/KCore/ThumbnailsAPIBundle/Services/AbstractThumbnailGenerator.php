<?php

namespace KCore\ThumbnailsAPIBundle\Services;

use Symfony\Component\HttpFoundation\File\File;

abstract class AbstractThumbnailGenerator implements ThumbnailGeneratorInterface
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
    abstract public function generateThumbnail(
        File $sourceFile,
        $thumbnailFilename,
        $width = 500,
        $height = 500,
        $format = 'png'
    );

    /**
     * @param array  $parameters
     * @param string $nameValueSeparator
     *
     * @return string
     */
    public static function buildShellCommandOptions($parameters, $nameValueSeparator = '=')
    {
        $opts = [];
        foreach ($parameters as $name => $value) {
            $opts[] = $name.$nameValueSeparator.$value;
        }

        return implode(' ', $opts);
    }
}
