<?php

class ActiveeditconfigFilterer extends AbstractFilterer
{
    protected $heartbeatFrequency;
    protected $listCheckFrequency;
    protected $staleThreshold;
    protected $max;

    public function setActiveEditHeartbeatFrequency($val)
    {
        $this->heartbeatFrequency = $val;
    }

    public function setActiveEditListCheckFrequency($val)
    {
        $this->listCheckFrequency = $val;
    }

    public function setActiveEditStaleThreshold($val)
    {
        $this->staleThreshold = $val;
    }

    public function setActiveEditMax($val)
    {
        $this->max = $val;
    }

    protected function getDefaultMethod()
    {
        return "config";
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