<?php

namespace CrowdFusion\Tests\ActiveEditsPlugin\Controller;

use CrowdFusion\ActiveEditsPlugin\Controller\HeartbeatCmsController;

class HeartbeatCmsControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $controller = new HeartbeatCmsController();
        $this->assertInstanceOf('HeartbeatCmsController', $controller);
    }
}
