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



class ActiveEditHeartbeatHandler {


    protected $Request;
    protected $NodeRefService;
    protected $NodeService;
    protected $DateFactory;

    public function setDateFactory(DateFactory $DateFactory)
    {
        $this->DateFactory = $DateFactory;
    }

    public function setRequest(Request $Request)
    {
        $this->Request = $Request;
    }

    public function setNodeRefService(NodeRefService $NodeRefService)
    {
        $this->NodeRefService = $NodeRefService;
    }

    public function setNodeService(NodeService $NodeService)
    {
        $this->NodeService = $NodeService;
    }

    /////////////////////
    // HANDLER ACTIONS //
    /////////////////////

    /* Bound to "Dispatcher.preAction" for updating ActiveDate (heartbeat) of active edit record. */
    public function processActiveEditHeartbeat()
    {
        $slug = $this->Request->getParameter('Heartbeat');

        if($slug != null) {
            $e = $this->NodeRefService->oneFromAspect('active-edits');
            $activeEdit = $this->NodeService->getByNodeRef(new NodeRef($e->getElement(),$slug));
            if(!empty($activeEdit)) {
                $activeEdit->ActiveDate = $this->DateFactory->newStorageDate();
                $this->NodeService->edit($activeEdit);
            }
        }
    }

}
