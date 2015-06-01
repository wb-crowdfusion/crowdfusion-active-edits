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
     * @param int
     */
    public function getTtl()
    {
        return $this->ttl;
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
        $results = $this->loadMembersFromCache($this->Request->getParameter('slugs'));

        echo \JSONUtils::encode($results);
    }

    /**
     * Returns list members for a given slug.
     *
     * @return string JSON of members list
     */
    public function getMembersAction()
    {
        $results = $this->loadMembersFromCache($this->Request->getParameter('slug'), true);

        $members = count($results) === 1 ? current($results) : array();

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

        $results = $this->loadMembersFromCache($this->Request->getParameter('slug'));

        if (count($results) === 1) {
            $slug = current(array_keys($results));
            $members = current($results);

            foreach ($members as $key => $member) {
                if ($member['slug'] == $this->RequestContext->getUser()->Slug) {
                    unset($members[$key]);
                    break;
                }
            }

            $isDeleted = $this->PrimaryCacheStore->put(sprintf('active-edits-%s', $slug), $members, $this->ttl);
        }

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

        $results = $this->loadMembersFromCache($this->Request->getParameter('slug'));

        if (count($results) === 1) {
            $slug = current(array_keys($results));
            $members = current($results);

            foreach ($members as $key => $member) {
                if ($member['slug'] == $this->RequestContext->getUser()->Slug) {
                    $members[$key]['updateMeta'] = true;

                    $isUpdated = $this->PrimaryCacheStore->put(sprintf('active-edits-%s', $slug), $members, $this->ttl);

                    break;
                }
            }
        }

        echo $isUpdated ? 'success' : 'error';
    }

    /**
     * Returns list of members for given slug(s).
     *
     * @param string|array $slugs
     * @param boolean $update
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
            $slug = preg_replace('/[^[:alnum:][:space:]]/ui', '-', $slug);

            $key = sprintf('active-edits-%s', $slug);

            if (!$members = $this->PrimaryCacheStore->get($key)) {
                $members = array();
            }

            // update logged-in active date
            if ($update) {
                /** @var \Node $user */
                $user = $this->RequestContext->getUser();

                $found = false;

                foreach ($members as $key => $member) {
                    if ($member['slug'] == $user->Slug) {
                        $found = $key;
                        break;
                    }
                }

                if ($found !== false) {
                    $member = $members[$found];
                } else {
                    $member = array(
                        'slug' => $user->Slug,
                        'name' => $user->Title,
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
                $this->PrimaryCacheStore->put($key, $members, $this->getTtl());
            }

            $results[$slug] = array_values($members);
        }

        return $results;
    }
}
