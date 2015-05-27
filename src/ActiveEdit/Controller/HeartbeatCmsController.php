<?php
 /**
 * No Summary
 *
 * PHP version 5
 *
 * Crowd Fusion
 * Copyright (C) 2009 Crowd Fusion, Inc.
 * http://www.crowdfusion.com/
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted under the terms of the BSD License.
 *
 * @package     CrowdFusion
 * @copyright   2009 Crowd Fusion Inc.
 * @license     http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version     $Id$
 */

namespace ActiveEdit\Controller;

 /**
 * Store list of active members per slug in cache
 *
 * @package     CrowdFusion
 */
class HeartbeatCmsController extends \AbstractCmsController
{
    protected $DateFactory;
    protected $PrimaryCacheStore;

    protected $ttl = 1200;

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
     *
     */
    public function getMembersAction()
    {
        $members = $this->loadMembersFromCache($this->Request->get('slug'), true);

        echo json_encode(current($members));
    }

    /**
     *
     */
    public function totalMembersAction()
    {
        $results = $this->loadMembersFromCache($this->Request->get('slugs'));

        foreach ($results as $slug => $members) {
            $results[$slug] = count($members);
        }

        echo json_encode($results);
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

        foreach ($slugs as $slug) {
            $members = array();

            $key = sprintf('active-edits-%s', $slug);

            if (!$members = $this->PrimaryCacheStore->get($key)) {
                $members = array();
            }

            // update logged-in active date
            if ($update) {
                $members[$this->authUser->getID()] = array(
                     'Name' => $this->authUser->getName(),
                     'ActiveDate' => $this->DateFactory->newStorageDate()
                );
            }

            // save
            $this->PrimaryCacheStore->put($key, $members, $this->ttl);

            $results[$slug] = $members;
        }

        return $results;
    }
}
