<?php

namespace App\Tests\Service;


class QueueServiceTest extends \Symfony\Bundle\FrameworkBundle\Test\KernelTestCase
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    public function setUp()
    {
        self::bootKernel();

        $this->container = self::$kernel->getContainer();
    }

    public function testItQueuesAMessage() {
        $this->assertFalse(true);
        $queueService = $this->container->get(\App\Service\QueueService::class);
        var_dump($queueService);
    }
}