<?php

namespace CrowdFusion\Plugin\ActiveEditsPlugin\Controller;

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
     * The number of seconds a cache value is stored. (default: 10 seconds)
     *
     * @var int
     */
    protected $ttl = 600;

    /**
     * @return int
     */
    public function getTtl()
    {
        return $this->ttl;
    }

    /**
     * @return \Node
     */
    public function getUser()
    {
        return $this->RequestContext->getUser();
    }

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
     * Returns list total members for each slug.
     *
     * @return string JSON of slug -> count(members)
     */
    public function totalMembersAction()
    {
        $members = array();

        $slugs = $this->Request->getParameter('slugs');

        foreach ($slugs as $slug) {
            $members[$slug] = $this->loadMembersFromCache($slug);
        }

        echo \JSONUtils::encode($members);
    }

    /**
     * Returns list members for a given slug.
     *
     * @return string JSON of members list
     */
    public function getMembersAction()
    {
        $this->getLock($slug = $this->Request->getParameter('slug'));

        $members = $this->updateMembersToCache($slug, $this->loadMembersFromCache($slug));

        $this->releaseLock($slug);

        echo \JSONUtils::encode($members);
    }

    /**
     * Removed the current logged-in user for a given slug.
     *
     * @return string JSON, success or error
     */
    public function removeMemberAction()
    {
        $isDeleted = false;

        $this->getLock($slug = $this->Request->getParameter('slug'));

        $members = $this->loadMembersFromCache($slug);

        foreach ($members as $index => $member) {
            if ($member['slug'] == $this->getUser()->Slug) {
                unset($members[$index]);

                $isDeleted = $this->PrimaryCacheStore->put($this->generateKey($slug), $members, $this->getTtl());

                break;
            }
        }

        $this->releaseLock($slug);

        echo $isDeleted ? 'success' : 'error';
    }

    /**
     * Sets the current logged-in user for a given slug with "updateMeta=true".
     *
     * @return string JSON, success or error
     */
    public function updateMetaAction()
    {
        $isUpdated = false;

        $this->getLock($slug = $this->Request->getParameter('slug'));

        $members = $this->loadMembersFromCache($slug);

        foreach ($members as $index => $member) {
            if ($member['slug'] == $this->getUser()->Slug) {
                $members[$index]['updateMeta'] = true;

                $isUpdated = $this->PrimaryCacheStore->put($this->generateKey($slug), $members, $this->getTtl());

                break;
            }
        }

        $this->releaseLock($slug);

        echo $isUpdated ? 'success' : 'error';
    }

    /**
     * Returns list of members for given slug(s).
     *
     * @param string $slug
     *
     * @return array List of active members
     */
    protected function loadMembersFromCache($slug)
    {
        $cacheKey = $this->generateKey($slug);

        if ($members = $this->PrimaryCacheStore->get($cacheKey)) {
            $foundExpiredMembers = false;

            foreach ($members as $index => $member) {

                // ActiveDate - TTL (seconds)
                $date = $this->DateFactory->newStorageDate(strtotime($member['activeDate']))->sub(
                    new \DateInterval(sprintf('PT%dS', round($this->getTtl()/60)))
                );

                if ($date > $this->DateFactory->newStorageDate()) {
                    $foundExpiredMembers = true;
                    unset($members[$index]);
                }
            }

            if ($foundExpiredMembers) {
                $this->PrimaryCacheStore->put($cacheKey, $members, $this->getTtl());
            }
        } else {
            $members = array();
        }

        return array_values($members);
    }

    /**
     * Updates slug members.
     *
     * @param string $slug
     * @param array  $members
     *
     * @return array List of active members
     */
    protected function updateMembersToCache($slug, array $members = array())
    {
        $cacheKey = $this->generateKey($slug);

        // update logged-in active date
        $found = false;

        foreach ($members as $index => $member) {
            if ($member['slug'] == $this->getUser()->Slug) {
                $found = $index;
                break;
            }
        }

        if ($found !== false) {
            $member = $members[$found];
        } else {
            $member = array(
                'slug' => $this->getUser()->Slug,
                'name' => $this->getUser()->Title,
                'activeDate' => null,
                'updateMeta' => false,
            );
        }

        // set to now
        $member['activeDate'] = $this->DateFactory->newStorageDate();

        if ($found !== false) {
            $members[$found] = $member;
        } else {
            $members[] = $member;
        }

        // update
        $this->PrimaryCacheStore->put($cacheKey, $members, $this->getTtl());

        return array_values($members);
    }

    /**
     * create lock.
     *
     * @param string $slug
     */
    protected function getLock($slug)
    {
        // loop until key is released
        $i = 0;
        do {
            if ($lock = $this->PrimaryCacheStore->get($this->getLockKey($slug))) {
                usleep($i += 200000); // 200 milliseconds
            }
        } while ($lock && $i < 3000);

        // failed after 3 seconds
        if ($lock) {
            throw new \Exception(sprintf('Failed to process data with slug "%s".', $slug));
        }

        $this->PrimaryCacheStore->put($this->getLockKey($slug), $this->getUser()->Slug, $this->getTtl());
    }

    /**
     * Releases lock.
     *
     * @param string $slug
     */
    protected function releaseLock($slug)
    {
        $this->PrimaryCacheStore->delete($this->getLockKey($slug));
    }

    /**
     * Generates lock key.
     *
     * @param string $slug
     *
     * @return string
     */
    protected function getLockKey($slug)
    {
        return $this->generateKey($slug, 'active-edits-transaction');
    }

    /**
     * Generates key.
     *
     * @param string $slug
     *
     * @return string
     */
    protected function generateKey($slug, $prefix = 'active-edits')
    {
        $slug = preg_replace('/[^[:alnum:][:space:]]/ui', '-', $slug);

        return sprintf('%s-%s', $prefix, $slug);
    }
}
