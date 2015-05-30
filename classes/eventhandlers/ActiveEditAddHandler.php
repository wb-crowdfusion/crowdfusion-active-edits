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
 * @package CrowdFusion
 */
class ActiveEditAddHandler
{
    protected $RequestContext;
    protected $Request;
    protected $NodeRefService;
    protected $RegulatedNodeService;

    public function setRegulatedNodeService(RegulatedNodeService $RegulatedNodeService)
    {
        $this->RegulatedNodeService = $RegulatedNodeService;
    }

    public function setRequestContext(RequestContext $RequestContext)
    {
        $this->RequestContext = $RequestContext;
    }

    public function setRequest(Request $Request)
    {
        $this->Request = $Request;
    }


    public function setNodeRefService(NodeRefService $NodeRefService)
    {
        $this->NodeRefService = $NodeRefService;
    }

    /////////////////////
    // HANDLER ACTIONS //
    /////////////////////

    /* Bound to "Node.@active-edits.bind" for generating a unique title and slug. */
    public function processActiveEditBind($action, Node &$newNode)
    {
        $user = $this->RequestContext->getUser();
        $newNode->Title = $user->Title . ' on ' . $this->Request->getParameter('RecordElementSlug') . ':' . $this->Request->getParameter('RecordSlug');
        $newNode->Slug = 'active-edit-' . $this->Request->getParameter('RecordSlug') . '-' . (floor(microtime(true) * 100));
    }

    /* Bound to "Node.@active-edits.add.pre" for adding member and record out tags. */
    public function processActiveEditAdd(NodeRef $nodeRef, Node &$newNode)
    {
        $user = $this->RequestContext->getUserRef();
        $member = $this->NodeRefService->oneFromAspect('members');

        $newNode->addOutTag(new Tag($member->getElement()->getSlug(), $user->getSlug(), '#active-edit-member'));
        $newNode->addOutTag(new Tag($this->Request->getParameter('RecordElementSlug'), $this->Request->getParameter('RecordSlug'), '#active-edit-record'));

        $nodeRef = $this->RegulatedNodeService->generateUniqueNodeRef($nodeRef);
    }
}
