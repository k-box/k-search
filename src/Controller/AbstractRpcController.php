<?php

namespace App\Controller;

use App\Exception\BadRequestException;
use App\Model\RPCRequest;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractRpcController extends Controller
{
    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(ValidatorInterface $validator, SerializerInterface $serializer)
    {
        $this->validator = $validator;
        $this->serializer = $serializer;
    }

    /**
     * Returns the given RequestModel from the request.
     *
     * @param Request $request
     * @param string  $class
     *
     * @throws BadRequestException
     *
     * @return mixed
     */
    protected function getRequestModelFromJson(Request $request, string $class)
    {
        $requestModel = $this->serializer->deserialize((string) $request->getContent(), $class, 'json');

        // We handle the request-id as a HTTP header, it will be used in the KSearchExceptionListener
        // to correctly set the "response->id" if available.
        if ($requestModel instanceof  RPCRequest && $requestModel->id) {
            $request->headers->set(RPCRequest::REQUEST_ID_HEADER, $requestModel->id);
        }

        $validationErrors = $this->validator->validate($requestModel);
        if (count($validationErrors) > 0) {
            $errors = [];
            /** @var ConstraintViolationInterface $error */
            foreach ($validationErrors as $error) {
                $errors[$error->getPropertyPath()] = $error->getMessage();
            }
            throw new BadRequestException($errors);
        }

        return $requestModel;
    }

    /**
     * @param mixed $model
     *
     * @return JsonResponse
     */
    protected function getJsonResponse($model): JsonResponse
    {
        return new JsonResponse($this->serializer->serialize($model, 'json'), 200, [], true);
    }
}
