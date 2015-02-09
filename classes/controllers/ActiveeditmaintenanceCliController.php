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
 * Maintenance Scripts CLI for Active-Edits
 *
 * @package     CrowdFusion
 */
class ActiveeditmaintenanceCliController extends AbstractCliController
{

    /**
     * @var NodeService
     */
    protected $NodeService;
    public function setNodeService($NodeService)
    {
        $this->NodeService = $NodeService;
    }

    /**
     * Delete Inactive Active Edit Records
     *
     * Currently due to javascript errors and other errors
     * active edit nodes can stick around in the 'draft' status.
     * This is mean to run in a cron, and will clean them up.
     *
     * Parameters:
     *  hourOffset - How many hours to leave active-edit node before considering it inactive
     *               and removing it.  Defaults to 6 hours.
     *
     * @return void
     */
    public function deleteInactiveRecords()
    {
        $hourOffset = $this->Request->getParameter('hourOffset');

        if (empty($hourOffset)) {
            $hourOffset = 6;
        } else {
            $hourOffset = intval($hourOffset);
        }

        $nq = new NodeQuery();
        $nq->setParameter('Elements.in', '@active-edits');
        $nq->setParameter('Status.eq', 'draft');
        $nq->setParameter('ActiveDate.before', "-{$hourOffset} hours");
        $nq->setParameter('NodeRefs.only', true);
        $editNodeRefs = $this->NodeService->findAll($nq)->getResults();

        $count = 1;

        if ($editNodeRefs) {
            foreach ($editNodeRefs as $editNodeRef) {
                $this->NodeService->delete($editNodeRef);
                echo "{$count} - Deleting [{$editNodeRef->getSlug()}]" . PHP_EOL;

                if (($count) % 10 == 0) {
                    $this->TransactionManager->commit()->begin();
                    echo "commit\n";
                }

                $count++;
            }
        }
    }
}