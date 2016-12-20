<?php

namespace CrowdFusion\Plugin\ActiveEditsPlugin\Controller;

use CrowdFusion\Plugin\ActiveEditsPlugin\Repository\ActiveEditRepository;
use CrowdFusion\Plugin\ActiveEditsPlugin\Entity\User;

/**
 * Store list of active members per slug in cache.
 */
class HeartbeatCmsController extends \AbstractCmsController
{
    /** @var ActiveEditRepository */
    protected $repository;

    /** @var \DateFactory */
    protected $dateFactory;

    /**
     * @param ActiveEditRepository $repository
     */
    public function setActiveEditRepository(ActiveEditRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param \DateFactory $dateFactory
     */
    public function setDateFactory(\DateFactory $dateFactory)
    {
        $this->dateFactory = $dateFactory;
    }

    /**
     * Echoes a list of members for a each given slug in json.
     */
    public function totalMembersAction()
    {
        $slugs = (array) $this->Request->getParameter('slugs');
        $users = $this->repository->getUsers($slugs);
        echo \JSONUtils::encode($users);
    }

    /**
     * Echoes a list of members for a given slug in json.
     */
    public function getMembersAction()
    {
        $slug = $this->Request->getParameter('slug');
        $this->repository->addUser($slug, $this->getUser());
        $users = $this->repository->getUsers([$slug]);
        if (isset($users[$slug])) {
            echo \JSONUtils::encode($users[$slug]);
        } else {
            echo \JSONUtils::encode([]);
        }
    }

    /**
     * Removes the current logged-in user for a given slug.
     * Echoes string with success or error.
     */
    public function removeMemberAction()
    {
        $slug = $this->Request->getParameter('slug');
        $isDeleted = $this->repository->removeUser($slug, $this->getUser()->getSlug());
        echo $isDeleted ? 'success' : 'error';
    }

    /**
     * Sets the current logged-in user for a given slug with "updateMeta=true".
     * Echoes string with success or error.
     */
    public function updateMetaAction()
    {
        $slug = $this->Request->getParameter('slug');
        $isUpdated = $this->repository->updateUserProperties($slug, $this->getUser(), [
            'meta_updated' => 1,
        ]);
        echo $isUpdated ? 'success' : 'error';
    }

    /**
     * @return User
     */
    protected function getUser()
    {
        /** @var $userNode \Node */
        $userNode = $this->RequestContext->getUser();

        if ($userNode instanceof \Node) {
            $userEntity = new User();
            $userEntity->setSlug($userNode->Slug);
            $userEntity->setTitle($userNode->Title);

            $userNode = $userEntity;
        }

        return $userNode;
    }
}
