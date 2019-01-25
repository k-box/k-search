<?php

namespace App\Controller;

use App\Entity\ApiUser;
use App\Exception\BadRequestException;
use App\Exception\DataDownloadErrorException;
use App\Exception\InvalidKlinkException;
use App\Exception\OutdatedDataRequestException;
use App\Exception\SolrEntityNotFoundException;
use App\Helper\KlinkHelper;
use App\Model\Data\AddRequest;
use App\Model\Data\AddResponse;
use App\Model\Data\Data;
use App\Model\Data\Uploader;
use App\Security\Authorization\Voter\DataVoter;
use App\Service\DataService;
use JMS\Serializer\SerializerInterface;
use Swagger\Annotations as SWG;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DataController extends AbstractRpcController
{
    /**
     * @var DataService
     */
    private $dataService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        DataService $searchService,
        ValidatorInterface $validator,
        SerializerInterface $serializer,
        LoggerInterface $logger
    ) {
        parent::__construct($validator, $serializer);
        $this->dataService = $searchService;
        $this->logger = $logger;
    }

    /**
     * Add piece of data to the index.
     *
     * @Route(
     *     name="api.v3.data.add",
     *     path="api/{version}/data.add",
     *     methods={"POST"},
     *     requirements={
     *        "version":"3.\d++"
     *     }
     * )
     *
     * @SWG\Post(
     *     path="/api/3.6/data.add",
     *     description="Add piece of data to the index. This API requires the `data-add` permission.",
     *     tags={"Data"},
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(ref="#/definitions/Data\AddRequest")
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returned when successful",
     *         @SWG\Schema(ref="#/definitions/Data\AddResponse")
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Returned when the data is not correct",
     *         @SWG\Schema(ref="#/definitions/Error\ErrorResponse"),
     *     ),
     * )
     *
     * @throws BadRequestException
     * @throws SolrEntityNotFoundException
     * @throws DataDownloadErrorException
     * @throws OutdatedDataRequestException
     */
    public function postDataAddV3(Request $request, string $version): JsonResponse
    {
        $this->denyAccessUnlessGranted(DataVoter::PERMISSION_ADD);

        /** @var AddRequest $addRequest */
        $addRequest = $this->buildRpcRequestModelFromJson($request, AddRequest::class, $version);

        $data = $addRequest->params->data;
        $this->updateDataWithAPIUser($data);

        // verify and attach K-Links information to the Data
        $this->updateDataWithKlinks($data, $addRequest->params->klinks ?? []);

        $this->dataService->addData($data, $addRequest->params->dataTextualContents, $addRequest->id);

        //$data = $this->dataService->getData($addRequest->params->data->uuid);
        $addResponse = new AddResponse($data, $addRequest->id);

        return $this->buildRpcJsonResponse($addResponse, $version);
    }

    private function updateDataWithAPIUser(Data $data): void
    {
        // Updating Data with the current API user
        /** @var ApiUser $apiUser */
        $apiUser = $this->getUser();

        if (!$data->uploader) {
            $data->uploader = new Uploader();
        }
        $data->uploader->appUrl = $apiUser->getUsername();
        $data->uploader->email = null; // this is enforced to prevent unauthorized disclosure of personal data
    }

    private function updateDataWithKlinks(Data $data, $klinks): void
    {
        // Updating Data with the allowed K-Link to be published on
        $apiUser = $this->getUser();

        if(! $apiUser->isRegistryUser()){
            return;
        }

        try {
            $data->klink_ids = KlinkHelper::ensureKlinkIsValid($klinks, $apiUser->getKlinks());
        } catch (InvalidKlinkException $ex) {
            throw new BadRequestException(['klinks' => $ex->getMessage()]);
        }
    }
}
