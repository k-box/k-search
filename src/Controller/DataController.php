<?php

namespace App\Controller;

use App\Model\Data\DeleteRequest;
use App\Model\Error\ErrorResponse;
use App\Model\Status\StatusResponse;
use JMS\Serializer\SerializerInterface;
use Swagger\Annotations as SWG;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DataController extends Controller
{
    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * DataController constructor.
     *
     * @param ValidatorInterface  $validator
     * @param SerializerInterface $serializer
     */
    public function __construct(ValidatorInterface $validator, SerializerInterface $serializer)
    {
        $this->validator = $validator;
        $this->serializer = $serializer;
    }

    /**
     * @SWG\Post(
     *     path="/api/0.0/data.delete",
     *     description="Delete piece of data from the search index.",
     *     tags={"Data"},
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(ref="#/definitions/Data\DeleteRequest")
     *     ),
     *     @SWG\Response(
     *         response="201",
     *         description="Returned when successful",
     *         @SWG\Schema(ref="#/definitions/Status\StatusResponse")
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Returned when the data is not correct",
     *         @SWG\Schema(ref="#/definitions/Error\ErrorResponse"),
     *     ),
     * )
     *
     * @Route(
     *     path="api/{version}/data.delete",
     *     requirements={
     *        "version":"0.0"
     *     }
     * )
     *
     * @param Request $request
     * @param string  $version
     *
     * @return Response
     */
    public function postDataDelete(Request $request, string $version)
    {
        /** @var DeleteRequest $deleteRequest */
        $deleteRequest = $this->serializer->deserialize($request->getContent(), DeleteRequest::class, 'json');

        $errors = $this->validator->validate($deleteRequest);
        if (count($errors) > 0) {
            $errorResponse = ErrorResponse::withErrorMessage(400, 'Wrong data!'.(string) $errors, $deleteRequest->id);

            return new JsonResponse($errorResponse);
        }

        // @todo Implement the logic here

        $statusResponse = StatusResponse::withStatusMessage(200, 'Ok', $deleteRequest->id);

        return new JsonResponse($statusResponse);
    }
}
