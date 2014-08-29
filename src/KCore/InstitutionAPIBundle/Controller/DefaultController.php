<?php

namespace KCore\InstitutionAPIBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use JMS\Serializer\Serializer;
use KCore\CoreBundle\Entity\InstitutionDescriptor;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use KCore\CoreBundle\Services\InstitutionService;
use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Intl\Exception\NotImplementedException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator;

class DefaultController extends FOSRestController {

    /**
     * Get single InstitutionDescriptor.
     *
     * @ApiDoc(
     *      resource = true,
     *      description = "Gets an Institution Descriptor by the given InstitutionID",
     *      output = "KCore\CoreBundle\Entity\InstitutionDescriptor",
     *      statusCodes = {
     *          200 = "Returned when successful",
     *          401 = "Returned when the invocation is Not Authorized",
     *          404 = "Returned when the InstitutionDescriptor is Not Found"
     *      }
     * )
     *
     * @Rest\View()
     *
     * @param string $id the InstitutionID
     *
     * @return array
     *
     * @throws NotFoundHttpException when institution does not exist
     *
     * @todo: implement the "401 - Not Athorized" check and response
     */
    public function getInstitutionAction($id) {

        /** @var InstitutionService $institutionService */
        $institutionService = $this->get('klink.institution.service');

        $institution = $institutionService->getInstitutionDescriptor($id);

        if (!$institution) {
            throw new NotFoundHttpException(sprintf('The resource "%s" was not found.', $id));
        }

        $view = $this->view($institution);
        return $this->handleView($view);
    }

    /**
     * Delete a single InstitutionDescriptor.
     *
     * @ApiDoc(
     *      resource = true,
     *      description = "Deletes an Institution Descriptor by the given InstitutionID",
     *      statusCodes = {
     *          204 = "Returned when successful",
     *          401 = "Returned when the invocation is Not Authorized",
     *          404 = "Returned when the InstitutionDescriptor is not found"
     *      }
     * )
     *
     * @Rest\View()
     *
     * @param string $id the InstitutionID
     *
     * @return array
     *
     * @throws NotFoundHttpException when institution does not exist
     * @throws InternalErrorException
     *
     * @todo: implement the "401 - Not Athorized" check and response
     */
    public function deleteInstitutionAction($id) {
        /** @var InstitutionService $institutionService */
        $institutionService = $this->get('klink.institution.service');

        $institution = $institutionService->getInstitutionDescriptor($id);
        if (!$institution) {
            throw new NotFoundHttpException();
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
     *      description = "Creates a new Institution Descriptor",
     *      input = "KCore\CoreBundle\Entity\InstitutionDescriptor",
     *      statusCodes = {
     *          201 = "Returned when successfully created",
     *          401 = "Returned when the invocation is Not Authorized",
     *      }
     * )
     *
     * @Rest\View()
     *
     * @param Request $request
     *
     * @throws InternalErrorException
     * @return array
     *
     * @todo: implement the "401 - Not Athorized" check and response
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

        if (count($errors) >0) {
            $message = array();
            /** @var ConstraintViolationInterface $error */
            foreach($errors as $error) {
                $message[] = $error->getPropertyPath();
            }
            throw new BadRequestHttpException('Wrong format: ' . implode('|' , $message));
        }

        $result = $institutionService->indexInstitutionDescriptor($institution);

        if ($result->getStatus() != 0) {
            throw new InternalErrorException($result->getResponse()->getStatusMessage());
        }

        $view = $this->view($institution, 201);
        $view->setLocation($this->generateUrl('institution_api_get', array('id' => $institution->getId())));

        return $this->handleView($view);
    }
}
