<?php

namespace CrowdFusion\Tests\ActiveEditsPlugin\Controller;

use CrowdFusion\ActiveEditsPlugin\Controller\HeartbeatCmsController;

class HeartbeatCmsControllerTest extends \PHPUnit_Framework_TestCase
{
    /** @var HeartbeatCmsController */
    protected $heartbeatCmsController;

    /**
     * @return HeartbeatCmsController
     */
    protected function getHeartbeatCmsController()
    {
        if (null === $this->heartbeatCmsController) {
            $this->heartbeatCmsController = new HeartbeatCmsController();
        }
        return $this->heartbeatCmsController;
    }

    public function testGetController()
    {
        $controller = $this->getHeartbeatCmsController();
        $this->assertInstanceOf('HeartbeatCmsController', $controller);
    }

    public function testTotalMembersAction()
    {
        return $this->getHeartbeatCmsController()->totalMembersAction();
    }

    public function testTotalMembersActionSlugDoesNotExist()
    {
        return $this->getHeartbeatCmsController()->totalMembersAction();
    }

    public function testTotalMembersActionZeroSlug()
    {
        return $this->getHeartbeatCmsController()->totalMembersAction();
    }

    public function testTotalMembersActionOneSlug()
    {
        return $this->getHeartbeatCmsController()->totalMembersAction();
    }

    public function testTotalMembersActionTwoSlug()
    {
        return $this->getHeartbeatCmsController()->totalMembersAction();
    }

    public function testTotalMembersActionTwentySlug()
    {
        return $this->getHeartbeatCmsController()->totalMembersAction();
    }

    public function testGetMembersAction()
    {
        return $this->getHeartbeatCmsController()->getMembersAction();
    }

    public function testGetMembersActionSlugDoesNotExist()
    {
        return $this->getHeartbeatCmsController()->getMembersAction();
    }

    public function testGetMembersActionUserExist()
    {
        return $this->getHeartbeatCmsController()->getMembersAction();
    }

    public function testGetMembersActionUserDoesNotExist()
    {
        return $this->getHeartbeatCmsController()->getMembersAction();
    }


    public function testRemoveMemberAction()
    {
        return $this->getHeartbeatCmsController()->removeMemberAction();
    }

    public function testRemoveMemberActionSlugExistUserDoesExist()
    {

    }

    public function testRemoveMemberActionSlugExistUserDoesNotExist()
    {

    }

    public function testRemoveMemberActionSlugDoesNotExist()
    {

    }

    public function testUpdateMetaAction()
    {
        return $this->getHeartbeatCmsController()->updateMetaAction();
    }

    public function testUpdateMetaActionSlugDoesNotExist()
    {

    }

    public function testUpdateMetaActionSlugDoesExistUserExist()
    {

    }

    public function testUpdateMetaActionSlugDoesExistUserExistMetaIsTrue()
    {

    }

    public function testUpdateMetaActionSlugDoesExistUserExistMetaIsFalse()
    {

    }

    public function testUpdateMetaActionSlugDoesExistUserDoesNotExist()
    {

    }
}
