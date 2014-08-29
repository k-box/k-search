<?php

namespace KCore\CoreBundle;

use KCore\CoreBundle\DependencyInjection\CoreExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CoreBundle extends Bundle
{

    public function getContainerExtension() {
        return new CoreExtension();
    }
}
