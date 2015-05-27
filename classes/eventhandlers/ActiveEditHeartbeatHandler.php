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

 /**
 * No Summary
 *
 * @package     CrowdFusion
 */
class ActiveEditHeartbeatHandler
{
    protected $DateFactory;
    protected $Request;
    protected $PrimaryCacheStore;
    protected $ttl = 1200;

    /**
     * @param DateFactory $DateFactory
     */
    public function setDateFactory(DateFactory $DateFactory)
    {
        $this->DateFactory = $DateFactory;
    }

    /**
     * @param Request $Request
     */
    public function setRequest(Request $Request)
    {
        $this->Request = $Request;
    }

    /**
     * @param CacheStoreInterface $PrimaryCacheStore
     */
    public function setPrimaryCacheStore(CacheStoreInterface $PrimaryCacheStore)
    {
        $this->PrimaryCacheStore = $PrimaryCacheStore;
    }

    /**
     * Bound to "Dispatcher.preAction" for updating ActiveDate (heartbeat) of active edit record.
     */
    public function processActiveEditHeartbeat()
    {
        $slug = preg_replace('/[^[:alnum:][:space:]]/ui', '_',$this->Request->getParameter('Heartbeat'));

        if ($slug != null) {
            $key = sprintf('active-edits-%s', $slug);

            if ($activeEdit = $this->PrimaryCacheStore->get($key)) {
                $activeEdit->ActiveDate = $this->DateFactory->newStorageDate();

                $this->PrimaryCacheStore->put($key, $activeEdit, $this->ttl);
            }
        }
    }
}
