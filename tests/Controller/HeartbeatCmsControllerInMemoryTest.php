<?php

namespace CrowdFusion\Tests\Plugin\ActiveEditsPlugin\Controller;

use CrowdFusion\Plugin\ActiveEditsPlugin\Repository\InMemoryActiveEditRepository;
use CrowdFusion\Plugin\ActiveEditsPlugin\Controller\HeartbeatCmsController;
use CrowdFusion\Plugin\ActiveEditsPlugin\Entity\User;

class HeartbeatCmsControllerInMemoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var HeartbeatCmsController */
    protected $controller;

    /** @var InMemoryActiveEditRepository */
    protected $repository;

    /** @var \Request */
    protected $request;

    /** @var \RequestContext */
    protected $requestContext;

    /** @var User */
    protected $user;

    protected function setUp()
    {
        $Logger = new \NullLogger();

        $InputClean = $this
            ->getMockBuilder('InputCleanInterface')
            ->setConstructorArgs(array('/tmp', 'utf-8'))
            ->getMock();
        $this->request = new \Request($InputClean, '/');

        $this->user = new User();
        $this->user->Slug = 'jonnytest';
        $this->user->Title = 'Jonny Test';

        $this->requestContext = new \RequestContext();
        $this->requestContext->setUser($this->user);

        $TransactionManager = $this->getMock('TransactionManagerInterface');

        $Nonces = $this
            ->getMockBuilder('Nonces')
            ->disableOriginalConstructor()
            ->getMock();

        $Permissions = $this->getMock('Permissions');
        $DateFactory = new \DateFactory('America/Los_Angeles', 'America/Los_Angeles');

        $ModelMapper = $this->getMock('ModelMapper');

        $Session = $this
            ->getMockBuilder('Session')
            ->disableOriginalConstructor()
            ->getMock();

        $Response = $this
            ->getMockBuilder('Response')
            ->disableOriginalConstructor()
            ->getMock();

        $this->controller = new HeartbeatCmsController(
            $Logger,
            $this->request,
            $this->requestContext,
            $TransactionManager,
            $Nonces,
            $Permissions,
            $DateFactory,
            $ModelMapper,
            $Session,
            $Response,
            false
        );

        $this->repository = new InMemoryActiveEditRepository(45, $DateFactory);

        $this->controller->setActiveEditRepository($this->repository);
        $this->controller->setDateFactory($DateFactory);
    }

    protected function tearDown()
    {
        unset($this->repository);
        unset($this->controller);
    }

    public function testCreate()
    {
        $this->assertInstanceOf('\AbstractCmsController', $this->controller);
    }

    /**
     * @dataProvider getSlugs
     */
    public function testGetMembersAction($slug)
    {
        $this->request->addRouteParameters(array('slug' => $slug));

        ob_start();
        $this->controller->getMembersAction();
        $output = ob_get_contents();
        ob_end_clean();

        $output = $this->removeOutputDatetime($output);

        $this->assertEquals($output, array(array(
            'slug' => $slug,
            'user_slug' => $this->user->Slug,
            'user_name' => $this->user->Title,
            'meta_updated' => false
        )));
    }

    /**
     * @dataProvider getSlugs
     */
    public function testGetMembersActionSlugInDb($slug)
    {
        $this->repository->addUser($slug, $this->user);

        $this->request->addRouteParameters(array('slug' => $slug));

        ob_start();
        $this->controller->getMembersAction();
        $output = ob_get_contents();
        ob_end_clean();

        $output = $this->removeOutputDatetime($output);

        $this->assertEquals($output, array(array(
            'slug' => $slug,
            'user_slug' => $this->user->Slug,
            'user_name' => $this->user->Title,
            'meta_updated' => false
        )));
    }

    /**
     * @dataProvider getSlugs
     */
    public function testGetMembersActionWithMultiUsers($slug)
    {
        $this->repository->addUser($slug, $this->user);

        // set new user
        $user = new User();
        $user->Slug = 'bobdoll';
        $user->Title = 'Bob Doll';
        // update logged-in user
        $this->requestContext->setUser($user);

        $this->request->addRouteParameters(array('slug' => $slug));

        ob_start();
        $this->controller->getMembersAction($this instanceof HeartbeatCmsControllerInMemoryTest);
        $output = ob_get_contents();
        ob_end_clean();

        $output = $this->removeOutputDatetime($output);

        // sort alphabeticly
        if ($output[0]['user_slug'] === $user->Slug) {
            $output = array_reverse($output);
        }

        $this->assertEquals($output, array(
            array(
                'slug' => $slug,
                'user_slug' => $this->user->Slug,
                'user_name' => $this->user->Title,
                'meta_updated' => false
            ),
            array(
                'slug' => $slug,
                'user_slug' => $user->Slug,
                'user_name' => $user->Title,
                'meta_updated' => false
            )
        ));

        // reload logged-in user
        $this->requestContext->setUser($this->user);
    }

    public function testTotalMembersAction()
    {
        $slugs = array();
        foreach ($this->getSlugs() as $slug) {
            if (!isset($slugs[$slug[0]])) {
                $slugs[$slug[0]] = [];
            }

            $slugs[$slug[0]][] = array(
                'slug' => $slug[0],
                'user_slug' => $this->user->Slug,
                'user_name' => $this->user->Title,
                'meta_updated' => false,
            );

            $this->repository->addUser($slug[0], $this->user);
        }

        $this->request->addRouteParameters(array('slugs' => array_keys($slugs)));

        ob_start();
        $this->controller->totalMembersAction();
        $output = ob_get_contents();
        ob_end_clean();

        $output = $this->removeOutputDatetime($output, true);

        $this->assertEquals($output, $slugs);
    }

    public function testTotalMembersActionWithNonBadSlug()
    {
        $this->request->addRouteParameters(array('slugs' => array($slug = $this->uuidV4())));

        ob_start();
        $this->controller->totalMembersAction();
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals(json_decode($output, true), array());
    }

    /**
     * @dataProvider getSlugs
     */
    public function testUpdateMetaAction($slug)
    {
        $this->repository->addUser($slug, $this->user);

        $this->request->addRouteParameters(array('slug' => $slug));

        ob_start();
        $this->controller->updateMetaAction();
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals($output, 'success');
    }


    /**
     * @dataProvider getSlugs
     */
    public function testRemoveMemberAction($slug)
    {
        $this->repository->addUser($slug, $this->user);

        $this->request->addRouteParameters(array('slug' => $slug));

        ob_start();
        $this->controller->removeMemberAction();
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals($output, 'success');
    }

    /**
     * @dataProvider getSlugs
     */
    public function testRemoveMemberActionWithNonBadSlug($slug)
    {
        $this->request->addRouteParameters(array('slug' => $slug));

        ob_start();
        $this->controller->removeMemberAction();
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals($output, 'error');
    }

    /**
     * Returns a list of slugs.
     */
    public function getSlugs()
    {
        $slugs = array();

        for ($i = 0; $i < 10; $i++) {
            $slugs[] = array($this->uuidV4());
        }

        return $slugs;
    }

    /**
     * Generates a UUID v4.
     */
    protected function uuidV4()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * @param string $json
     * @papram bool $multiSlugs
     * @return array
     */
    protected function removeOutputDatetime($json, $multiSlugs = false)
    {
        $array = json_decode($json, true);

        foreach ($array as &$item) {
            if ($multiSlugs) {
                foreach ($item as &$v) {
                    unset($v['added_at']);
                    unset($v['modified_at']);
                }
            } else {
                unset($item['added_at']);
                unset($item['modified_at']);
            }
        }

        return $array;
    }
}
