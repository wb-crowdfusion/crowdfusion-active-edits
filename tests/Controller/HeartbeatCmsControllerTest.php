<?php

namespace CrowdFusion\Tests\ActiveEditsPlugin\Controller;

use CrowdFusion\ActiveEditsPlugin\Controller\HeartbeatCmsController;

class HeartbeatCmsControllerTest extends \PHPUnit_Framework_TestCase
{
    /** @var HeartbeatCmsController */
    protected $controller;

    protected function setUp()
    {
        $Logger             = $this->getMock('LoggerInterface');

        $Request = $this
            ->getMockBuilder('Request')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $RequestContext     = $this->getMock('RequestContext');
        $TransactionManager = $this->getMock('TransactionManagerInterface');

        $Nonces = $this
            ->getMockBuilder('Nonces')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $Permissions        = $this->getMock('Permissions');
        $DateFactory = $this
            ->getMockBuilder('DateFactory')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $ModelMapper        = $this->getMock('ModelMapper');

        $Session = $this
            ->getMockBuilder('Session')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $Response = $this
            ->getMockBuilder('Response')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $isCommandLine      = false;

        $this->controller = new HeartbeatCmsController(
            $Logger,
            $Request,
            $RequestContext,
            $TransactionManager,
            $Nonces,
            $Permissions,
            $DateFactory,
            $ModelMapper,
            $Session,
            $Response,
            $isCommandLine
        );
    }

    protected function tearDown()
    {
        unset($this->controller);
    }

    public function testCreateController()
    {
        $this->assertInstanceOf('\AbstractCmsController', $this->controller);
    }

/*
    public function testTotalMembersAction()
    {
        $results = $this->controller->totalMembersAction();

        $this->assertNull($results);
    }

    public function testTotalMembersActionSlugDoesNotExist()
    {
        return $this->controller->totalMembersAction();
    }

    public function testTotalMembersActionZeroSlug()
    {
        return $this->controller->totalMembersAction();
    }

    public function testTotalMembersActionOneSlug()
    {
        return $this->controller->totalMembersAction();
    }

    public function testTotalMembersActionTwoSlug()
    {
        return $this->controller->totalMembersAction();
    }

    public function testTotalMembersActionTwentySlug()
    {
        return $this->controller->totalMembersAction();
    }

    public function testGetMembersAction()
    {
        return $this->controller->getMembersAction();
    }

    public function testGetMembersActionSlugDoesNotExist()
    {
        return $this->controller->getMembersAction();
    }

    public function testGetMembersActionUserExist()
    {
        return $this->controller->getMembersAction();
    }

    public function testGetMembersActionUserDoesNotExist()
    {
        return $this->controller->getMembersAction();
    }

    public function testRemoveMemberAction()
    {
        return $this->controller->removeMemberAction();
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
        return $this->controller->updateMetaAction();
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
*/
}
