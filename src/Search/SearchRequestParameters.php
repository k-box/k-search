<?php

namespace App\Search;

use App\Services\DocumentService;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchRequestParameters
{
    const FILTER_PREFIX = 'filter_';
    const FACET_PREFIX = 'facet_';
    public $options = [];

    /** @var DocumentService $documentService */
    protected $documentService;

    /** @var \App\Search\Facets\FacetInterface[] $searchFacets */
    protected $searchFacets;

    /** @var \App\Search\Filters\FilterInterface[] $searchFilterParams */
    protected $searchFilters;

    /**
     * @param array           $options
     * @param DocumentService $documentService
     */
    public function __construct(array $options, DocumentService $documentService)
    {
        $resolver = new OptionsResolver();

        /* @var DocumentService $documentService */
        $this->documentService = $documentService;
        $this->configureResolverOptions($resolver);
        $this->options = $resolver->resolve($options);

        $facets = explode(',', $this->options['facets']);
        if (count($facets)) {
            foreach ($facets as $facetKey) {
                $searchFacet = $this->documentService->createFacet($facetKey);
                if ($searchFacet) {
                    $searchFacet->setLimit($this->options[self::FACET_PREFIX.$facetKey.'_count']);
                    $searchFacet->setMinCount($this->options[self::FACET_PREFIX.$facetKey.'_mincount']);
                    $prefix = $this->options[self::FACET_PREFIX.$facetKey.'_prefix'];
                    if ($prefix !== '') {
                        $searchFacet->setPrefix($prefix);
                    }
                    $this->searchFacets[] = $searchFacet;
                }
            }
        }

        foreach ($this->options as $optionName => $optionValue) {
            if (strpos($optionName, self::FILTER_PREFIX) === 0) {
                $filterKey = substr($optionName, strlen(self::FILTER_PREFIX));
                $searchFilter = $this->documentService->createFilter($filterKey);
                if ($searchFilter) {
                    $searchFilter->setDefaultQueryWithValue($optionValue);
                    $this->searchFilters[] = $searchFilter;
                }
            }
        }
    }

    /**
     * @return int
     */
    public function getNumResults()
    {
        return $this->getOption('numResults');
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->getOption('query');
    }

    /**
     * @return int
     */
    public function getStartResult()
    {
        return $this->getOption('startResult');
    }

    /**
     * @return array
     */
    public function getSearchFilters()
    {
        return $this->searchFilters;
    }

    /**
     * @return array
     */
    public function getSearchFacets()
    {
        return $this->searchFacets;
    }

    protected function configureResolverOptions(OptionsResolver $resolver)
    {
        $defaults = [
            'query' => null,
            'facets' => '',
        ];
        $allowedTypes = ['facets' => 'string'];
        $allowedValues = [];

        $definedFilters = [];
        $filtersKeys = array_keys($this->documentService->getSearchAllowedFilters());
        $notEmptyClosure = function ($value) { return !empty($value); };
        foreach ($filtersKeys as $filterKey) {
            $filterName = self::FILTER_PREFIX.$filterKey;
            $definedFilters[] = $filterName;
            $allowedTypes[$filterName] = ['string', 'integer'];
            $allowedValues[$filterName] = $notEmptyClosure;
        }

        $definedFacets = [];
        $facetsKeys = array_keys($this->documentService->getSearchAllowedFacets());
        foreach ($facetsKeys as $facetKey) {
            $facetName = self::FACET_PREFIX.$facetKey;
            $definedFacets[] = $facetName.'_count';
            $allowedTypes[$facetName.'_count'] = 'numeric';
            $defaults[$facetName.'_count'] = 10;

            $definedFacets[] = $facetName.'_mincount';
            $allowedTypes[$facetName.'_mincount'] = 'numeric';
            $defaults[$facetName.'_mincount'] = 2;

            $definedFacets[] = $facetName.'_prefix';
            $allowedTypes[$facetName.'_prefix'] = 'string';
            $defaults[$facetName.'_prefix'] = '';
        }

        $resolver->setDefaults($defaults);

        if (count($definedFilters)) {
            $resolver->setDefined($definedFilters);
        }

        if (count($definedFacets)) {
            $resolver->setDefined($definedFacets);
        }

        foreach ($allowedTypes as $name => $type) {
            $resolver->setAllowedTypes($name, $type);
        }
        foreach ($allowedValues as $name => $values) {
            $resolver->setAllowedValues($name, $values);
        }

        $resolver->setDefault('numResults', 10);
        $resolver->setAllowedTypes('numResults', 'numeric');
        $resolver->setAllowedValues('numResults', function ($value) {
            return $value >= 0 && $value <= 50;
        });

        $resolver->setDefault('startResult', 0);
        $resolver->setAllowedTypes('startResult', 'numeric');
        $resolver->setAllowedValues('startResult', function ($value) {
            return $value >= 0;
        });
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    protected function getOption($name)
    {
        return isset($this->options[$name]) ? $this->options[$name] : null;
    }
}
