<?php

namespace CrowdFusion\Tests\Plugin\ActiveEditsPlugin;

class MySingleDBDataSource extends \SingleDBDataSource
{
    /** @var \DatabaseInterface */
    protected $MySQLDatabase;

    /**
     * Creates the object.
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
