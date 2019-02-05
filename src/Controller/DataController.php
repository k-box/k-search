<?php

namespace App\Controller;

use App\Entity\ApiUser;
use App\Exception\BadRequestException;
use App\Exception\DataDownloadErrorException;
use App\Exception\InvalidKlinkException;
use App\Exception\OutdatedDataRequestException;
use App\Exception\SolrEntityNotFoundException;
use App\Model\Data\AddRequest;
use App\Model\Data\AddResponse;
use App\Model\Data\Data;
use App\Model\Data\Klink;
use App\Model\Data\Uploader;
use App\Security\Authorization\Voter\DataVoter;
use App\Service\DataService;
use App\Service\KlinkService;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Swagger\Annotations as SWG;
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

    /**
     * @var KlinkService
     */
    private $klinks;

    public function __construct(
        DataService $searchService,
        ValidatorInterface $validator,
        SerializerInterface $serializer,
        KlinkService $klinks,
        LoggerInterface $logger
    ) {
        parent::__construct($validator, $serializer);
        $this->dataService = $searchService;
        $this->logger = $logger;
        $this->klinks = $klinks;
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

        if (!empty($data->klink_ids)) {
            $data->klinks = $this->buildKlinks($data->klink_ids);
        }
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
        // Updating the Data object to include
        // the klinks if the request is
        // valid.
        // In this new version at least 1 K-Link identifier
        // must be attached to the data. For compatibility
        // reason we use the first K-Link defined by the
        // application, but only if the application has
        // the possibility to publish on a single K-Link

        $apiUser = $this->getUser();

        if (!$apiUser->isRegistryUser()) {
            return;
        }

        try {
            $data->klink_ids = $this->klinks->ensureValidKlinks($klinks, $this->klinks->getDefaultKlinkIdentifier());
        } catch (InvalidKlinkException $ex) {
            $this->logger->error('Invalid K-Links found in request', ['error' => $ex->getMessage(), 'klinks' => $klinks]);
            throw new BadRequestException(['klinks' => $ex->getMessage()]);
        }
    }

    private function buildKlinks($klink_ids): array
    {
        if (!$klink_ids) {
            return [];
        }

        $klinks = [];
        foreach ($klink_ids as $id) {
            $klinkData = $this->klinks->getKlink($id);
            if ($klinkData) {
                $klink = new Klink();
                $klink->id = $klinkData->getId();
                $klink->name = $klinkData->getName();
                $klinks[] = $klink;
            }
        }

        return $klinks;
    }
}
