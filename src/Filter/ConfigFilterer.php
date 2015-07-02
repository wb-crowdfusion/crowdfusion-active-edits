<?php

namespace CrowdFusion\Plugin\ActiveEditsPlugin\Filter;

class ConfigFilterer extends \AbstractFilterer
{
    /** @var int */
    protected $heartbeatFrequency;

    /** @var int */
    protected $listCheckFrequency;

    /** @var int */
    protected $staleThreshold;

    /** @var int */
    protected $max;

    /**
     * @param int $val
     */
    public function setActiveEditHeartbeatFrequency($val)
    {
        $this->heartbeatFrequency = $val;
    }

    /**
     * @param int $val
     */
    public function setActiveEditListCheckFrequency($val)
    {
        $this->listCheckFrequency = $val;
    }

    /**
     * @param int $val
     */
    public function setActiveEditStaleThreshold($val)
    {
        $this->staleThreshold = $val;
    }

    /**
     * @param int $val
     */
    public function setActiveEditMax($val)
    {
        $this->max = $val;
    }

    /**
     * @return string
     */
    protected function getDefaultMethod()
    {
        return 'config';
    }

    /**
     * Render the active edit config parameters
     *
     * @return string
     */
    protected function config()
    {
        return <<<EOD
HeartbeatFrequency : {$this->heartbeatFrequency},
ListCheckFrequency : {$this->listCheckFrequency},
StaleActiveEditThreshold : {$this->staleThreshold},
MaxActiveEdits : {$this->max}
EOD;
    }
}
