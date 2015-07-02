<?php

namespace CrowdFusion\Tests\Plugin\ActiveEditsPlugin\Controller;

use CrowdFusion\Tests\Caching\Stores\Mock\MemcachedMock;
use CrowdFusion\Plugin\ActiveEditsPlugin\Controller\HeartbeatCmsController;

class HeartbeatCmsControllerTest extends \PHPUnit_Framework_TestCase
{
    /** @var HeartbeatCmsController */
    protected $controller;

    /** @var \MemcachedCacheStore */
    protected $cache;

    /** @var \Request */
    protected $request;

    /** @var \RequestContext */
    protected $requestContext;

    /** @var \stdClass */
    protected $user;

    protected function setUp()
    {
        $Logger = $this->getMock('LoggerInterface');

        $InputClean = $this
            ->getMockBuilder('InputCleanInterface')
            ->setConstructorArgs(array('/tmp', 'utf-8'))
            ->getMock();
        $this->request = new \Request($InputClean, '/');

        $this->user = new \stdClass();
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
        $DateFactory = $this
            ->getMockBuilder('DateFactory')
            ->disableOriginalConstructor()
            ->getMock();

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

        if (!class_exists('Memcached')) {
            MemcachedMock::create();
        }

        $this->cache = new \MemcachedCacheStore(
            new \NullLogger(),
            array(array(
                'host' => '127.0.0.1',
                'port' => 11211
            )),
            sprintf('test_%s_', md5(rand())),
            true,
            'cf_memcached_tests',
            false,
            false,
            false,
            false
        );

        $this->controller->setPrimaryCacheStore($this->cache);
    }

    protected function tearDown()
    {
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
        ob_end_flush();

        $output = ob_get_contents();

        $this->assertJsonStringEqualsJsonString($output, json_encode(array(array(
            'slug' => $this->user->Slug,
            'name' => $this->user->Title,
            'pingedAt' => null,
            'updateMeta' => false
        ))));
    }

    /**
     * @dataProvider getSlugs
     */
    public function testGetMembersActionSlugInCache($slug)
    {
        $this->addSlugToCache($slug, array(
            array(
                'slug' => $this->user->Slug,
                'name' => $this->user->Title,
                'pingedAt' => null,
                'updateMeta' => false,
            )
        ));

        $this->request->addRouteParameters(array('slug' => $slug));

        ob_start();
        $this->controller->getMembersAction();
        ob_end_flush();

        $output = ob_get_contents();

        $this->assertJsonStringEqualsJsonString($output, json_encode(array(array(
            'slug' => $this->user->Slug,
            'name' => $this->user->Title,
            'pingedAt' => null,
            'updateMeta' => false
        ))));
    }

    /**
     * @dataProvider getSlugs
     */
    public function testGetMembersActionWithMultiUsers($slug)
    {
        $this->addSlugToCache($slug, array(
            array(
                'slug' => $this->user->Slug,
                'name' => $this->user->Title,
                'pingedAt' => null,
                'updateMeta' => false,
            )
        ));

        // set new user
        $user = new \stdClass();
        $user->Slug = 'bobdoll';
        $user->Title = 'Bob Doll';
        // update logged-in user
        $this->requestContext->setUser($user);

        $this->request->addRouteParameters(array('slug' => $slug));

        ob_start();
        $this->controller->getMembersAction();
        ob_end_flush();

        $output = ob_get_contents();

        $this->assertJsonStringEqualsJsonString($output, json_encode(array(
            array(
                'slug' => $this->user->Slug,
                'name' => $this->user->Title,
                'pingedAt' => null,
                'updateMeta' => false
            ),
            array(
                'slug' => $user->Slug,
                'name' => $user->Title,
                'pingedAt' => null,
                'updateMeta' => false
            )
        )));
    }

    public function testTotalMembersAction()
    {
        $members = array(
            array(
                'slug' => $this->user->Slug,
                'name' => $this->user->Title,
                'pingedAt' => null,
                'updateMeta' => false,
            )
        );

        $slugs = array();
        foreach ($this->getSlugs() as $slug) {
            $slugs[$slug[0]] = $members;

            $this->addSlugToCache($slug[0], $members);

        }
        $this->request->addRouteParameters(array('slugs' => array_keys($slugs)));

        ob_start();
        $this->controller->totalMembersAction();
        ob_end_flush();

        $output = ob_get_contents();

        $this->assertJsonStringEqualsJsonString($output, json_encode($slugs));
    }

    public function testTotalMembersActionWithNonBadSlug()
    {
        $this->request->addRouteParameters(array('slugs' => array($slug = $this->uuidV4())));

        ob_start();
        $this->controller->totalMembersAction();
        ob_end_flush();

        $output = ob_get_contents();

        $this->assertJsonStringEqualsJsonString($output, json_encode(array($slug => array())));
    }

    /**
     * @dataProvider getSlugs
     */
    public function testUpdateMetaAction($slug)
    {
        $this->addSlugToCache($slug, array(
            array(
                'slug' => $this->user->Slug,
                'name' => $this->user->Title,
                'pingedAt' => null,
                'updateMeta' => false,
            )
        ));

        $this->request->addRouteParameters(array('slug' => $slug));

        ob_start();
        $this->controller->updateMetaAction();
        ob_end_flush();

        $output = ob_get_contents();

        $this->assertEquals($output, 'success');
    }

    /**
     * @dataProvider getSlugs
     */
    public function testUpdateMetaActionWithNonBadSlug($slug)
    {
        $this->request->addRouteParameters(array('slug' => $slug));

        ob_start();
        $this->controller->updateMetaAction();
        ob_end_flush();

        $output = ob_get_contents();

        $this->assertEquals($output, 'error');
    }

    /**
     * @dataProvider getSlugs
     */
    public function testRemoveMemberAction($slug)
    {
        $this->addSlugToCache($slug, array(
            array(
                'slug' => $this->user->Slug,
                'name' => $this->user->Title,
                'pingedAt' => null,
                'updateMeta' => false,
            )
        ));

        $this->request->addRouteParameters(array('slug' => $slug));

        ob_start();
        $this->controller->removeMemberAction();
        ob_end_flush();

        $output = ob_get_contents();

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
        ob_end_flush();

        $output = ob_get_contents();

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
    private function uuidV4()
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
     * Stores the slug with members to cache.
     *
     * @param string $slug
     * @param array $members
     */
    private function addSlugToCache($slug, array $members = array())
    {
        $this->cache->put(sprintf('active-edits-%s', $slug), $members, $this->controller->getTtl());
    }
}
