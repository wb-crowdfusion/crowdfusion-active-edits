<?php

namespace CrowdFusion\Tests\Plugin\ActiveEditsPlugin\Controller;

use CrowdFusion\Plugin\ActiveEditsPlugin\Repository\MySQLActiveEditRepository;
use CrowdFusion\Plugin\ActiveEditsPlugin\Entity\User;

class MySingleDBDataSource extends \SingleDBDataSource
{
    /** @var \DatabaseInterface */
    protected $MySQLDatabase;

    /**
     * Creates the object
     *
     * @param \TransactionManagerInterface $TransactionManager
     * @param \DatabaseInterface           $MySQLDatabase
     * @param array                        $connectionInfo
     */
    public function __construct(
        \TransactionManagerInterface $TransactionManager,
        \DatabaseInterface $MySQLDatabase,
        array $connectionInfo
    ) {
        $this->TransactionManager = $TransactionManager;
        $this->MySQLDatabase = $MySQLDatabase;
        $this->connectionInfo = $connectionInfo;
    }

    /**
     * {@inheritdoc}
     */
    protected function getNewConnection($connections)
    {
        $this->MySQLDatabase->setConnectionInfo($connections);
        return $this->MySQLDatabase;
    }
}

class HeartbeatCmsControllerMySqlTest extends HeartbeatCmsControllerInMemoryTest
{
    protected function setUp()
    {
        parent::setUp();

        $Logger = new \NullLogger();

        $TransactionManager = $this->getMock('TransactionManagerInterface');

        $DateFactory = new \DateFactory('America/Los_Angeles', 'America/Los_Angeles');

        $connectionInfo = array(
            'host' => 'localhost',
            'post' => 3306,
            'database' => 'test',
            'username' => 'root',
            'password' => 'root'
        );

        $mySQLDatabase = new \MySQLDatabase(
            $Logger,
            $this->getMock('Benchmark'),
            $DateFactory,
            2
        );

        $mySQLDatabase->setConnectionInfo(array($connectionInfo));

        $DataSource = new MySingleDBDataSource(
            $TransactionManager,
            $mySQLDatabase,
            $connectionInfo
        );

        try {
            $DataSource
                ->getConnectionsForReadWrite()
                ->offsetGet(0) // \ConnectionCouplet
                ->getConnection() // \MySQLDatabase
                ->getConnection();

        } catch (\Exception $e) {
            $this->markTestSkipped('All tests in this file are inactive. No database was found!');

            return;
        }
        /** @var $this->repository MySQLActiveEditRepository */
        $this->repository = new MySQLActiveEditRepository(45, 'active_edit', $DateFactory, $DataSource);

        $this->controller->setActiveEditRepository($this->repository);
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
        $this->controller->getMembersAction($this instanceof HeartbeatCmsControllerMySqlTest);
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
}
