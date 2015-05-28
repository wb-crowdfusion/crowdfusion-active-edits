<?php

namespace CrowdFusion\ActiveEditsPlugin\Controller;

/**
 * Store list of active members per slug in cache.
 */
class HeartbeatCmsController extends \AbstractCmsController
{
    /** @var \DateFactory */
    protected $DateFactory;

    /** @var \CacheStoreInterface */
    protected $PrimaryCacheStore;

    /**
     * The number of seconds a cache value is stored. (default: 1 day)
     *
     * @var int
     */
    protected $ttl = 86400;

    /**
     * @param \DateFactory $DateFactory
     */
    public function setDateFactory(\DateFactory $DateFactory)
    {
        $this->DateFactory = $DateFactory;
    }

    /**
     * @param \CacheStoreInterface $PrimaryCacheStore
     */
    public function setPrimaryCacheStore(\CacheStoreInterface $PrimaryCacheStore)
    {
        $this->PrimaryCacheStore = $PrimaryCacheStore;
    }

    /**
     * Returns list members for a given slug.
     *
     * @return string JSON of members list
     */
    public function getMembersAction()
    {
        $members = $this->loadMembersFromCache($this->Request->getParameter('slug'), true);

        if (count($members) === 1) {
            $members = current($members);
        }

        echo \JSONUtils::encode($members);
    }

    /**
     * Returns list total members for each slug.
     *
     * @return string JSON of slug -> count(members)
     */
    public function totalMembersAction()
    {
        $results = $this->loadMembersFromCache($this->Request->getParameter('slugs'));

        foreach ($results as $slug => $members) {
            $results[$slug] = count($members);
        }

        echo \JSONUtils::encode($results);
    }

    /**
     * Returns list of members for given slug(s).
     *
     * @param string|array $slugs
     * @param boolean      $update
     *
     * @return array List of active members
     */
    protected function loadMembersFromCache($slugs, $update = false)
    {
        $results = array();

        if (is_string($slugs)) {
            $slugs = array($slugs);
        }
        if (empty($slugs)) {
            $slugs = array();
        }

        foreach ($slugs as $slug) {
            $members = array();

            $key = sprintf('active-edits-%s', $slug);

            if (!$members = $this->PrimaryCacheStore->get($key)) {
                $members = array();
            }

            // update logged-in active date
            if ($update) {

                /** @var \Node $user */
                $user = $this->RequestContext->getUser();

                if (!isset($members[$user->ID])) {
                    $members[$user->ID] = array(
                        'Name'       => $user->Title,
                        'ActiveDate' => null,
                    );
                }

                $members[$user->ID]['ActiveDate'] = $this->DateFactory->newStorageDate();

                // save
                $this->PrimaryCacheStore->put($key, $members, $this->ttl);
            }

            $results[$slug] = $members;
        }

        return $results;
    }
}
