<?php

declare(strict_types=1);

namespace POData\ObjectModel;

/**
 * Class ODataCategory.
 * @package POData\ObjectModel
 */
class ODataCategory implements IOData
{
    /**
     * Term.
     *
     * @var string
     */
    private $term;

    /**
     * Scheme.
     *
     * @var string
     */
    private $scheme;

    /**
     * ODataCategory constructor.
     *
     * @param        $term
     * @param string $scheme
     */
    public function __construct($term, $scheme = 'http://schemas.microsoft.com/ado/2007/08/dataservices/scheme')
    {
        $this
            ->setTerm($term)
            ->setScheme($scheme);
    }

    /**
     * @return string
     */
    public function getTerm(): string
    {
        return $this->term;
    }

    /**
     * @param  string        $term
     * @return ODataCategory
     */
    public function setTerm(string $term): ODataCategory
    {
        $this->term = $term;
        return $this;
    }

    /**
     * @return string
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * @param  string        $scheme
     * @return ODataCategory
     */
    public function setScheme(string $scheme): ODataCategory
    {
        $this->scheme = $scheme;
        return $this;
    }
}
