<?php
/**
 * Created by PhpStorm.
 * User: Ema
 * Date: 04/11/2014
 * Time: 14:37
 */

namespace KCore\SearchAPIBundle\SearchRequest;


use KCore\CoreBundle\Entity\DocumentDescriptor;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SearchRequestParameters  {

    protected $options = array();

    function __construct(array $options = array()) {
        $resolver = new OptionsResolver();
        $this->configureResolverOptions($resolver);

        $this->options = $resolver->resolve($options);
    }

    protected function configureResolverOptions(OptionsResolverInterface $resolver) {

        $resolver->setDefaults(array(
            'query' => null,
            'visibility' => DocumentDescriptor::DOCUMENT_VISIBILITY_PUBLIC,
            'numResults' => 10,
            'startResult' => 0,
            '_format'     => 'json',
        ));

        $resolver->setAllowedValues(array(
            'visibility' => array(
                DocumentDescriptor::DOCUMENT_VISIBILITY_PRIVATE,
                DocumentDescriptor::DOCUMENT_VISIBILITY_PUBLIC
            ),
        ));

        $resolver->setAllowedTypes(array(
            'numResults'  => 'numeric',
            'startResult' => 'numeric',
        ));
    }

    /**
     * @return integer
     */
    public function getNumResults() {
        return $this->getOption('numResults');
    }

    /**
     * @return string
     */
    public function getQuery() {
        return $this->getOption('query');
    }

    /**
     * @return integer
     */
    public function getStartResult() {
        return $this->getOption('startResult');
    }

    /**
     * @return string
     */
    public function getVisibility() {
        return $this->getOption('visibility');
    }

    /**
     * @return bool
     */
    public function isPublicSearch() {
        return $this->getVisibility() !== DocumentDescriptor::DOCUMENT_VISIBILITY_PRIVATE;
    }

    /**
     * @param $name
     * @return mixed
     */
    protected function getOption($name) {
        return $this->options[$name];
    }
}