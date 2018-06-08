<?php

namespace App\Controller;

use App\Exception\SolrEntityNotFoundException;
use App\Service\DataDownloader;
use App\Service\DataService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FilesController extends Controller
{
    /**
     * @var bool
     */
    private $retainDataContents;

    /**
     * @var DataService
     */
    private $dataService;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var DataDownloader
     */
    private $dataDownloader;

    public function __construct(
        DataService $dataService,
        DataDownloader $dataDownloader,
        ValidatorInterface $validator
    ) {
        $this->dataService = $dataService;
        $this->dataDownloader = $dataDownloader;
        $this->validator = $validator;
    }

    /**
     * Serves a Data file previously downloaded, given the UUID.
     *
     * @Route(
     *     path="files/{uuid}",
     *     methods={"GET"},
     *     requirements={
     *        "uuid":"[a-zA-Z0-9-]{36}"
     *     }
     * )
     *
     * @param string $uuid
     *
     * @return Response
     */
    public function getDataFile(string $uuid): Response
    {
        $this->validateUuid($uuid);

        try {
            $data = $this->dataService->getData($uuid);
        } catch (SolrEntityNotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage());
        }

        $filename = $this->dataDownloader->dataFileExistsAndIsCurrent($data);
        if (!$filename) {
            return $this->redirect($data->url, Response::HTTP_TEMPORARY_REDIRECT);
        }

        $response = $this->file($filename, $data->properties->filename);
        $response->headers->set('E-Tag', $data->hash);

        return $response;
    }

    private function validateUuid(string $uuid): void
    {
        $violations = $this->validator->validate($uuid, [
            new Uuid([
                'versions' => [Uuid::V4_RANDOM],
                'message' => 'Invalid UUID provided, use UUID version 4',
            ]),
        ]);

        if (\count($violations)) {
            throw new BadRequestHttpException($violations->get(0)->getMessage());
        }
    }
}
