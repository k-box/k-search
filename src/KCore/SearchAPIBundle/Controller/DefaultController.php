<?php

namespace KCore\SearchAPIBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use KCore\CoreBundle\Entity\SolrDocumentDescriptor;
use KCore\CoreBundle\Services\DocumentService;
use KCore\SearchAPIBundle\Entity\SearchResults;
use KCore\SearchAPIBundle\SearchRequest\SearchRequestParameters;
use Symfony\Component\HttpFoundation\Request;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DefaultController extends FOSRestController
{
    /**
     * This method allows to search for a set of matching DocumentDescriptors, by the
     * given keywords (or set of keywords) in the "query" filter
     *
     * @ApiDoc(
     *      resource=true,
     *      description="DocmentDescriptor SearchAPI",
     *      output = "KCore\SearchAPIBundle\Entity\SearchResults",
     *      filters={
     *          {"name"="query",       "dataType"="string"},
     *          {"name"="visibility",  "dataType"="string",  "pattern"="(public|private)"},
     *          {"name"="startResult", "dataType"="integer"},
     *          {"name"="numResults",  "dataType"="integer"}
     *      },
     *      statusCodes = {
     *          200 = "Returned when the search has been successfully executed",
     *          400 = "Returned when the invocation parameters are not correct",
     *          401 = "Returned when the invocation is Not Authorized",
     *      }
     * )
     *
     * @Rest\View()
     * @param Request $request
     * @return array
     */
    public function searchDocumentDescriptorAction(Request $request)
    {
        try {
            $searchRequestParameters = new SearchRequestParameters($request->query->all());
        } catch (\Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        $results = new SearchResults(
            $searchRequestParameters->getQuery(),
            $searchRequestParameters->getVisibility(),
            $searchRequestParameters->getStartResult(),
            $searchRequestParameters->getNumResults()
        );

        /** @var DocumentService $documentService */
        $documentService = $this->get('klink.document.service');

        $solrResult = $documentService->searchDocumentDescriptor(
            $searchRequestParameters->getQuery(),
            $searchRequestParameters->isPublicSearch(),
            array(),
            $searchRequestParameters->getStartResult(),
            $searchRequestParameters->getNumResults()
        );


        $results->setNumFound($solrResult->getNumFound());
        $results->setQueryTime($solrResult->getQueryTime());

        foreach($solrResult->getDocuments() as $resultItem) {
            /** @var SolrDocumentDescriptor $resultItem */
            $results->addItem($resultItem->getDocumentDescriptor());
        }

        $view = $this->view($results);
        return $this->handleView($view);
    }
}
