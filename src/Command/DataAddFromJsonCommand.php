<?php

namespace App\Command;

use App\Model\Data\AddRequest;
use App\Model\Data\Data;
use App\Service\DataService;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DataAddFromJsonCommand extends ContainerAwareCommand
{
    /** @var DataService */
    private $dataService;

    /** @var SerializerInterface */
    private $serializer;

    /** @var ValidatorInterface */
    private $validator;

    public function __construct(
        DataService $dataService,
        ValidatorInterface $validator,
        SerializerInterface $serializer
    ) {
        parent::__construct();
        $this->dataService = $dataService;
        $this->validator = $validator;
        $this->serializer = $serializer;
    }

    protected function configure()
    {
        $this->setName('ksearch:data:add-from-json')
            ->setDescription('')
            ->addArgument(
                'json-data',
                InputArgument::REQUIRED,
                'The JSON data file to be sent as a Data model'
            )
            ->addOption(
                'as-json-add-request',
                'r',
                InputOption::VALUE_NONE,
                'Use the provided JSON data as a DataAdd request'
            )
            ->addOption(
                'file',
                null,
                InputOption::VALUE_REQUIRED,
                'Define the file to be indexed for the given data'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jsonDataFile = $input->getArgument('json-data');
        $file = $input->getOption('file');
        $asAddRequest = $input->getOption('as-json-add-request');

        if (!file_exists($jsonDataFile)) {
            $output->writeln(sprintf('<error>Error</error> File %s does not exist!', $jsonDataFile));

            return 1;
        }
        $output->writeln(sprintf('Handling Data.Add from file: <comment>%s</comment>', $jsonDataFile));

        $dataModel = null;
        if ($asAddRequest) {
            /** @var AddRequest $addRequestModel */
            $addRequestModel = $this->serializer->deserialize(file_get_contents($jsonDataFile), AddRequest::class, 'json');
            $this->validate($addRequestModel);
            $dataModel = $addRequestModel->params->data;
        } else {
            /** @var Data $dataModel */
            $dataModel = $this->serializer->deserialize(file_get_contents($jsonDataFile), Data::class, 'json');
            $this->validate($dataModel);
        }

        if (!$file) {
            $res = $this->dataService->addData($dataModel);
        } else {
            $fileInfo = new \SplFileInfo($file);
            $res = $this->dataService->addDataWithFileExtraction($dataModel, $fileInfo);
        }
    }

    private function validate($object)
    {
        $errors = $this->validator->validate($object);
        if ($errors->count()) {
            dump($errors);
            throw new \RuntimeException('');
        }
    }
}
