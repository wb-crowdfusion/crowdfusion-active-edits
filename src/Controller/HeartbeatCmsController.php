<?php

namespace CrowdFusion\Plugin\ActiveEditsPlugin\Controller;

/**
 * Store list of active members per slug in cache.
 */
class HeartbeatCmsController extends \AbstractCmsController
{
    /** @var \DateFactory */
    protected $dateFactory;

    /** @var \CacheStoreInterface */
    protected $cacheStore;

    /**
     * The number of seconds a cache value is stored. (default: 60 seconds)
     *
     * @var int
     */
    protected $ttl = 3600;

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
     * @param \DateFactory $dateFactory
     */
    public function setDateFactory(\DateFactory $dateFactory)
    {
        $this->dateFactory = $dateFactory;
    }

    /**
     * @param \CacheStoreInterface $cacheStore
     */
    public function setPrimaryCacheStore(\CacheStoreInterface $cacheStore)
    {
        $this->cacheStore = $cacheStore;
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

            foreach ($members[$slug] as $index => $member) {
                if ($this->isMemberExpired($member['pingedAt'])) {
                    unset($members[$slug][$index]);
                }
            }
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

                $isDeleted = $this->cacheStore->put($this->generateKey($slug), $members, $this->getTtl());

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

                $isUpdated = $this->cacheStore->put($this->generateKey($slug), $members, $this->getTtl());

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
        if (!$members = $this->cacheStore->get($this->generateKey($slug))) {
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
        $found = false;

        foreach ($members as $index => $member) {
            if ($member['slug'] == $this->getUser()->Slug) {
                $found = $index;
                continue;
            }

            if ($this->isMemberExpired($member['pingedAt'])) {
                unset($members[$index]);
            }
        }

        if ($found !== false) {
            $member = $members[$found];
        } else {
            $member = array(
                'slug' => $this->getUser()->Slug,
                'name' => $this->getUser()->Title,
                'pingedAt' => null,
                'updateMeta' => false,
            );
        }

        // set to now
        $member['pingedAt'] = $this->dateFactory->newStorageDate();

        if ($found !== false) {
            $members[$found] = $member;
        } else {
            $members[] = $member;
        }

        // update
        $this->cacheStore->put($this->generateKey($slug), $members, $this->getTtl());

        return array_values($members);
    }

    /**
     * Checks if member edit session expired.
     *
     * @param string $pingedAt
     *
     * @return Boolean
     */
    protected function isMemberExpired($pingedAt)
    {
        $date = $this->dateFactory->newStorageDate(strtotime($pingedAt))->add(
            new \DateInterval(sprintf('PT%dS', round($this->getTtl()/60)))
        );

        return $date < $this->dateFactory->newStorageDate();
    }

    /**
     * Creates lock.
     *
     * @param string $slug
     */
    protected function getLock($slug)
    {
        // loop until key is released
        $i = 0;
        do {
            if ($existingLock = $this->cacheStore->get($this->getLockKey($slug))) {
                usleep(200000); // 200 milliseconds

                $i += 0.2;
            }
        } while ($existingLock && $i < 5); // 5 seconds

        // failed after 3 seconds
        if ($existingLock) {
            throw new \Exception(sprintf('Failed to acquire lock for slug "%s".', $slug));
        }

        $this->cacheStore->put($this->getLockKey($slug), $this->getUser()->Slug, $this->getTtl());
    }

    /**
     * Releases lock.
     *
     * @param string $slug
     */
    protected function releaseLock($slug)
    {
        $this->cacheStore->delete($this->getLockKey($slug));
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
        return $this->generateKey($slug, 'active-edits-lock');
    }

    /**
     * Generates key.
     *
     * @param string $slug
     * @param string $prefix
     *
     * @return string
     */
    protected function generateKey($slug, $prefix = 'active-edits')
    {
        $slug = preg_replace('/[^[:alnum:][:space:]]/ui', '-', $slug);

        return sprintf('%s-%s', $prefix, $slug);
    }
}
