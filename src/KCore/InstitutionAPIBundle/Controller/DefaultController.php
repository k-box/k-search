<?php

namespace KCore\InstitutionAPIBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use JMS\Serializer\Serializer;
use KCore\CoreBundle\Entity\InstitutionDescriptor;
use KCore\CoreBundle\Services\InstitutionService;
use KCore\InstitutionAPIBundle\Entity\InstitutionObjectForVoter;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator;

class DefaultController extends FOSRestController
{
    /**
     * Get single InstitutionDescriptor.
     *
     * @ApiDoc(
     *      section="Institutions",
     *      resource = true,
     *      authentication = true,
     *      description = "Gets an Institution Descriptor by the given InstitutionID",
     *      output = "KCore\CoreBundle\Entity\InstitutionDescriptor",
     *      statusCodes = {
     *          200 = "Returned when successful",
     *          401 = "Returned when the invocation is Not Authorized",
     *          403 = "Returned when the invocation is Denied",
     *          404 = "Returned when the InstitutionDescriptor is Not Found"
     *      }
     * )
     *
     * @Rest\View()
     *
     * @param string $id the InstitutionID
     *
     * @throws NotFoundHttpException when institution does not exist
     *
     * @return array
     */
    public function getInstitutionAction($id)
    {

        /** @var InstitutionService $institutionService */
        $institutionService = $this->get('klink.institution.service');

        $institution = $institutionService->getInstitutionDescriptor($id);

        if (!$institution) {
            throw new NotFoundHttpException(sprintf('The resource "%s" was not found.', $id));
        }

        // authorization
        if (false === $this->isGranted('get', new InstitutionObjectForVoter($institution))) {
            throw new AccessDeniedException();
        }

        $view = $this->view($institution);

        return $this->handleView($view);
    }

    /**
     * Delete a single InstitutionDescriptor.
     *
     * @ApiDoc(
     *      section="Institutions",
     *      resource = true,
     *      authentication = true,
     *      description = "Deletes an Institution Descriptor by the given InstitutionID",
     *      statusCodes = {
     *          204 = "Returned when successful",
     *          401 = "Returned when the invocation is Not Authorized",
     *          403 = "Returned when the invocation is Denied",
     *          404 = "Returned when the InstitutionDescriptor is not found"
     *      }
     * )
     *
     * @Rest\View()
     *
     * @param string $id the InstitutionID
     *
     * @throws NotFoundHttpException  when institution does not exist
     * @throws InternalErrorException
     *
     * @return array
     */
    public function deleteInstitutionAction($id)
    {
        /** @var InstitutionService $institutionService */
        $institutionService = $this->get('klink.institution.service');

        $institution = $institutionService->getInstitutionDescriptor($id);
        if (!$institution) {
            throw new NotFoundHttpException();
        }

        // authorization
        if (false === $this->isGranted('delete', new InstitutionObjectForVoter($institution))) {
            throw new AccessDeniedException();
        }

        $result = $institutionService->deleteInstitutionDescriptor($institution);

        if ($result->getStatus() != 0) {
            throw new InternalErrorException($result->getResponse()->getStatusMessage());
        }

        $view = $this->view(null, 204);

        return $this->handleView($view);
    }

    /**
     * Post a new InstitutionDescriptor.
     *
     * @ApiDoc(
     *      section="Institutions",
     *      authentication = true,
     *      description = "Creates a new Institution Descriptor",
     *      input = "KCore\CoreBundle\Entity\InstitutionDescriptor",
     *      statusCodes = {
     *          201 = "Returned when successfully created",
     *          401 = "Returned when the invocation is Not Authorized",
     *          403 = "Returned when the invocation is Denied",
     *      }
     * )
     *
     * @Rest\View()
     *
     * @param Request $request
     *
     * @throws InternalErrorException
     *
     * @return array
     */
    public function postInstitutionAction(Request $request)
    {
        $content = $request->getContent();

        if (empty($content)) {
            throw new BadRequestHttpException('Wrong format');
        }
        /** @var InstitutionService $institutionService */
        $institutionService = $this->get('klink.institution.service');

        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');

        /** @var InstitutionDescriptor $institution */
        $institution = $serializer->deserialize($content, 'KCore\CoreBundle\Entity\InstitutionDescriptor', 'json');

        /** @var Validator\RecursiveValidator $validator */
        $validator = $this->get('validator');
        $errors = $validator->validate($institution);

        if (count($errors) > 0) {
            $message = [];
            /** @var ConstraintViolationInterface $error */
            foreach ($errors as $error) {
                $message[] = $error->getPropertyPath();
            }
            throw new BadRequestHttpException('Wrong format: '.implode('|', $message));
        }

        // authorization
        if (false === $this->isGranted('post', new InstitutionObjectForVoter($institution))) {
            throw new AccessDeniedException();
        }

        $result = $institutionService->indexInstitutionDescriptor($institution);

        if ($result->getStatus() != 0) {
            throw new InternalErrorException($result->getResponse()->getStatusMessage());
        }

        $view = $this->view($institution, 201);
        $view->setLocation($this->generateUrl('institution_api_get', ['id' => $institution->getId()]));

        return $this->handleView($view);
    }

    /**
     * Get all institutions registered in the KLink network.
     *
     * @ApiDoc(
     *      authentication = true,
     *      section="Institutions",
     *      description = "Get all the Institution Descriptors, sorted by the institution ID (ascending)",
     *      output = "array<KCore\CoreBundle\Entity\InstitutionDescriptor>",
     *      statusCodes = {
     *          200 = "Returned when Ok",
     *          401 = "Returned when the invocation is Not Authorized",
     *          403 = "Returned when the invocation is Denied",
     *      },
     *     filters = {
     *         {
     *             "name"="numResults",
     *             "dataType"="integer",
     *             "description"="Number of items to return",
     *             "range"="[0..100]",
     *             "default"=100,
     *         },
     *         {
     *             "name"="startResult",
     *             "dataType"="integer",
     *             "description"="The first item's index to return, used for pagination. 0-based",
     *             "range"="[0..n]",
     *             "default"=0,
     *         }
     *     }
     * )
     *
     * @Rest\View()
     *
     * @param Request $request
     *
     * @throws InternalErrorException
     *
     * @return Response
     */
    public function getAllInstitutionsAction(Request $request)
    {
        // Authorization Check
        if (false === $this->isGranted('get-all', new InstitutionObjectForVoter(null))) {
            throw new AccessDeniedException();
        }

        $institutionService = $this->get('klink.institution.service');

        $numResults = (int) $request->get('numResults', 100);
        if ($numResults < 0 || $numResults > 100) {
            $numResults = 100;
        }

        $startResult = max(0, (int) $request->get('startResult', 0));

        $institutions = $institutionService->getAllInstitutionDescriptors($numResults, $startResult);
        $view = $this->view($institutions, 200);

        return $this->handleView($view);
    }
}
