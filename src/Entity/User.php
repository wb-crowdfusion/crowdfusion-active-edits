<?php

namespace CrowdFusion\Plugin\ActiveEditsPlugin\Entity;

/**
 * Quick little entity for defining user properties.
 */
class User
{
    /** @var string */
    protected $slug;

    /** @var string */
    protected $title;

    /**
     * @param string $slug
     *
     * @return self
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param string $title
     *
     * @return self
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
}
