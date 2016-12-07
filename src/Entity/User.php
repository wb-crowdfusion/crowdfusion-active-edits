<?php

namespace CrowdFusion\Plugin\ActiveEditsPlugin\Entity;

/**
 * Class user
 * Quick little entity for defining user properies.
 * @package CrowdFusion\Plugin\ActiveEditsPlugin\Entity
 *
 * @author Onjefu Efada <onjefu.efada@tmz.com>
 *
 */

class User
{
    public $Slug;
    public $Title;

    public function setSlug($slug = null)
    {
        $this->Slug = $slug;
    }

    public function setTitle($title = null)
    {
        $this->Title = $title;
    }
}
