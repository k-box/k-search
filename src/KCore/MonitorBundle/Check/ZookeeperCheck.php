<?php

namespace KCore\MonitorBundle\Check;

use Symfony\Component\Process\ProcessBuilder;
use ZendDiagnostics\Check\CheckInterface;
use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\ResultInterface;
use ZendDiagnostics\Result\Success;
use ZendDiagnostics\Result\Warning;

class ZookeeperCheck implements CheckInterface
{
    protected $zookeeperDir = null;

    public function __construct($zookeeperDir)
    {
        $this->zookeeperDir = $zookeeperDir;
    }

    /**
     * Perform the actual check and return a ResultInterface.
     *
     * @return ResultInterface
     */
    public function check()
    {
        $command = $this->zookeeperDir.'zkServer.sh';
        $builder = ProcessBuilder::create();
        $builder->setPrefix($command);
        $builder->setArguments(['status']);
        $process = $builder->getProcess();
        $process->run();

        // executes after the command finishes
        if (!$process->isSuccessful()) {
            return new Failure($process->getErrorOutput());
        }

        $out = $process->getOutput();
        $matches = [];
        if (preg_match('/Mode: (follower|leader)/', $out, $matches) == 1) {
            array_shift($matches);

            return new Success(
              'Zookeeper is active, status: '.current($matches)
            );
        }

        return new Warning('Not tested. Message:'.$process->getOutput());
    }

    /**
     * Return a label describing this test instance.
     *
     * @return string
     */
    public function getLabel()
    {
        return 'KCore Zookeeper';
    }
}
