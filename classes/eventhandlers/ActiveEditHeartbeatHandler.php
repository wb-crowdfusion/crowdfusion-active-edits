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
    protected $NodeRefService;
    protected $NodeService;
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
     * @param NodeRefService $NodeRefService
     */
    public function setNodeRefService(NodeRefService $NodeRefService)
    {
        $this->NodeRefService = $NodeRefService;
    }

    /**
     * @param NodeService $NodeService
     */
    public function setNodeService(NodeService $NodeService)
    {
        $this->NodeService = $NodeService;
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
        $slug = $this->Request->getParameter('Heartbeat');

        if ($slug != null) {
            $key = sprintf('active-edits-%s', $slug);

            // if not in cache, load Node object
            if (!$activeEdit = $this->PrimaryCacheStore->get($key)) {
                $e = $this->NodeRefService->oneFromAspect('active-edits');

                $activeEdit = $this->NodeService->getByNodeRef(new NodeRef($e->getElement(), $slug));
            }

            // if exists, update ActiveDate to now
            if ($activeEdit) {
                $activeEdit->ActiveDate = $this->DateFactory->newStorageDate();

                $this->PrimaryCacheStore->put($key, $activeEdit, $this->ttl);
            }
        }
    }
}
