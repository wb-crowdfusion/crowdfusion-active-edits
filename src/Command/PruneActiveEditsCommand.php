<?php

namespace CrowdFusion\Plugin\ActiveEditsPlugin\Command;

use CrowdFusion\Plugin\ActiveEditsPlugin\Repository\ActiveEditRepository;

/**
 * @author @author Onjefu Efada <onjefu.efada@tmz.com>
 *
 * Polls active edits table to expired users.
 * To be added to chron or Job Cheduler
 */
class PruneActiveEditsCommand extends \AbstractCliController
{
    /** @var ActiveEditRepository */
    protected $repository;

    /**
     * @param \ApplicationContext $appContext
     */
    public function setApplicationContext(\ApplicationContext $appContext)
    {
        $this->repository = $appContext->object('ActiveEditRepository');
    }

    /**
     * CLI Runner.
     */
    public function run()
    {
        $startTime = time();
        $this->Logger->info('Running Active Edits Pruner');

        try {
            $this->repository->purgeStale();
        } catch (\Exception $e) {
            $this->Logger->info(sprintf('Purge Failed: [%s]', $e->getMessage()));
        }

        $runTime = time() - $startTime;
        $this->Logger->info(sprintf('Execution time of [%d] seconds.', $runTime));
    }
}
