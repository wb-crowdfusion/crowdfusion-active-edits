<?php

namespace CrowdFusion\Tests\Plugin\ActiveEditsPlugin\Controller;

use CrowdFusion\Tests\Plugin\ActiveEditsPlugin\MySingleDBDataSource;
use CrowdFusion\Plugin\ActiveEditsPlugin\Repository\MySQLActiveEditRepository;
use CrowdFusion\Plugin\ActiveEditsPlugin\Entity\User;

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
            'password' => 'root',
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

        $this->repository = new MySQLActiveEditRepository(45, 'active_edit', $DateFactory, $DataSource);

        $this->controller->setActiveEditRepository($this->repository);
    }
}
