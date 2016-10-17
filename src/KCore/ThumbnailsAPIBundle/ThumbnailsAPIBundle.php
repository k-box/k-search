<?php

namespace KCore\ThumbnailsAPIBundle;

use KCore\ThumbnailsAPIBundle\DependencyInjection\ThumbnailsAPIExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ThumbnailsAPIBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new ThumbnailsAPIExtension();
    }
}
