<?php

namespace Solarium\Component\Facet;

use Solarium\Component\FacetSetInterface;

/**
 * Facet range.
 *
 * @see http://wiki.apache.org/solr/SimpleFacetParameters#Facet_by_Range
 */
class Range extends AbstractRange implements ExcludeTagsInterface
{
    use ExcludeTagsTrait {
        init as excludeTagsInit;
    }

    /**
     * Get the facet type.
     *
     * @return string
     */
    public function getType()
    {
        return FacetSetInterface::FACET_RANGE;
    }

    /**
     * Set the facet mincount.
     *
     * @param int $minCount
     *
     * @return self Provides fluent interface
     */
    public function setMinCount($minCount)
    {
        return $this->setOption('mincount', $minCount);
    }

    /**
     * Get the facet mincount.
     *
     * @return int
     */
    public function getMinCount()
    {
        return $this->getOption('mincount');
    }

    /**
     * Initialize options.
     *
     * Several options need some extra checks or setup work, for these options
     * the setters are called.
     */
    protected function init()
    {
        parent::init();
        $this->excludeTagsInit();
    }
}
