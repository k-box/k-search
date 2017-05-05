<?php

namespace App\Controller;

use App\Entity\DocumentDescriptor;
use App\Entity\Facet;
use App\Entity\FacetItem;
use App\Entity\ResultItem;
use App\Entity\ResultSet;
use App\Entity\SolrDocumentDescriptor;
use App\Search\SearchObjectForVoter;
use App\Search\SearchRequestParameters;
use App\Service\DocumentService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SearchController extends Controller
{
    /**
     * This method allows to search for a set of matching DocumentDescriptors, by the
     * given keywords (or set of keywords) in the "query" filter.
     *
     * The `filter_*` parameters can be specified as: a single value, or a comma (`,`) separated list of values
     * (condition: return results that contain any of the provided values), or as a pipe (`|`)  separated list of
     * values (condition: return results that contains ALL the specified values).
     *
     *
     * @param Request $request    The actual request
     * @param string  $visibility The KSearch to use for the search
     *
     * @ ApiDoc(
     *      resource=true,
     *      authentication=true,
     *      section="Document Search",
     *      description="Search DocumentDescriptors",
     *      output = "App\Entity\ResultSet",
     *      filters={
     *          {
     *              "name"="query",
     *              "dataType"="string"
     *          },
     *          {
     *              "name"="startResult",
     *              "dataType"="integer",
     *              "description"="The first item's index to return, used for pagination. 0-based",
     *              "default"=0,
     *          },
     *          {
     *              "name"="numResults",
     *              "dataType"="integer",
     *              "description"="Number of items to return in the search",
     *              "range"="[0..50]",
     *              "default"=10,
     *          },
     *          {
     *              "name"="facets",
     *              "dataType"="string",
     *              "description"="The list of enabled facets. Facets must be enabled before they can be configured.",
     *          },
     *          {
     *              "name"="filter_language",
     *              "dataType"="string",
     *              "description"="Filter the returned results by language",
     *          },
     *          {
     *              "name"="filter_documentType",
     *              "dataType"="string",
     *              "description"="Filter the returned results by document-type",
     *          },
     *          {
     *              "name"="filter_institutionId",
     *              "dataType"="string",
     *              "description"="Filter the returned results by the InstitutionID",
     *          },
     *          {
     *              "name"="filter_documentId",
     *              "dataType"="string",
     *              "description"="Filter the returned results by the DocumentID",
     *          },
     *          {
     *              "name"="filter_documentHash",
     *              "dataType"="string",
     *              "description"="Filter the returned results by the DocumentHash",
     *              "since"="2.2",
     *          },
     *          {
     *              "name"="filter_localDocumentId",
     *              "dataType"="string",
     *              "description"="Filter the returned results by the LocalDocumentId",
     *          },
     *          {
     *              "name"="filter_projectId",
     *              "dataType"="string",
     *              "description"="Filter the returned results by the ProjectID",
     *              "since"="2.2",
     *          },
     *      },
     *      statusCodes = {
     *          200 = "Returned when the search has been successfully executed",
     *          400 = "Returned when the invocation parameters are not correct",
     *          401 = "Returned when the invocation is Not Authorized",
     *      }
     * )
     *
     * @return Response
     *
     *
     * TODO: implement ApiDoc for facets_*
     */
    public function searchDocumentDescriptorAction(Request $request, $visibility)
    {
        /** @var DocumentService $documentService */
        $documentService = $this->get('ksearch.document.service');

        if ($documentService->getEnabledCoreVisibility() !== $visibility) {
            throw new BadRequestHttpException(sprintf('This instance does not support the given "%s" visibility', $visibility));
        }

        $searchObjectForVoter = new SearchObjectForVoter($visibility);

        // authorization
        if (false === $this->isGranted('search', $searchObjectForVoter)) {
            throw new AccessDeniedException();
        }

        if (!in_array($visibility, [DocumentDescriptor::DOCUMENT_VISIBILITY_PUBLIC, DocumentDescriptor::DOCUMENT_VISIBILITY_PRIVATE], true)) {
            throw new NotFoundHttpException();
        }

        try {
            $searchRequestParameters = new SearchRequestParameters($request->query->all(), $documentService);
        } catch (\Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        $results = new ResultSet(
            $searchRequestParameters->getQuery(),
            $visibility,
            $searchRequestParameters->getSearchFilters(),
            $searchRequestParameters->getStartResult(),
            $searchRequestParameters->getNumResults()
        );

        $solrResult = $documentService->searchDocumentDescriptor(
            $searchRequestParameters->getQuery(),
            $visibility,
            $searchRequestParameters->getSearchFilters(),
            $searchRequestParameters->getSearchFacets(),
            $searchRequestParameters->getStartResult(),
            $searchRequestParameters->getNumResults()
        );

        $faceset = $solrResult->getFacetSet();
        if (count($faceset) > 0) {
            foreach ($faceset as $facetName => $facetItems) {
                $facet = new Facet($facetName);
                foreach ($facetItems as $facetItemTerm => $facetItemCount) {
                    $facetItem = new FacetItem($facetItemCount, $facetItemTerm);
                    $facet->addItem($facetItem);
                }
                $results->addFacet($facet);
            }
        }

        $results->setNumFound($solrResult->getNumFound());
        $results->setQueryTime($solrResult->getQueryTime());

        foreach ($solrResult->getDocuments() as $result) {
            /* @var SolrDocumentDescriptor $result */
            $score = $result->getField('score');
            $resultItem = new ResultItem($score, $result->getDocumentDescriptor());
            $results->addItem($resultItem);
        }

        $view = $this->view($results);

        return $this->handleView($view);
    }
}
