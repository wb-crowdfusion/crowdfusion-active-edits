<?php

namespace CrowdFusion\Plugin\ActiveEditsPlugin\Repository;

use CrowdFusion\Plugin\ActiveEditsPlugin\Entity\User;

class MySQLActiveEditRepository implements ActiveEditRepository
{
    /** @var int */
    protected $expiry = 0;

    /** @var string */
    protected $tableName;

    /* @var \DataSourceInterface $dsn */
    protected $dsn;

    /** @var \DateFactory */
    protected $dateFactory;

    /**
     * @param int                  $expiry
     * @param string               $tableName
     * @param \DateFactory         $dateFactory
     * @param \DataSourceInterface $dsn
     */
    public function __construct($expiry, $tableName, \DateFactory $dateFactory, \DataSourceInterface $dsn)
    {
        $this->expiry = (int) $expiry;
        $this->tableName = $tableName;
        $this->dateFactory = $dateFactory;
        $this->dsn = $dsn;
    }

    /**
     * {@inheritdoc}
     */
    public function getUsers(array $slugs)
    {
        if (empty($slugs)) {
            return [];
        }

        $inQuery = implode(',', array_fill(0, count($slugs), '?'));

        $datetime = $this->dateFactory->newStorageDate();
        $datetime->modify(sprintf('-%d seconds', $this->expiry));

        $statement = $this->getConnection()->prepare(
            sprintf('select * from %s where slug in (%s) and modified_at >= \'%s\'', $this->tableName, $inQuery, $datetime->format('Y-m-d H:i:s'))
        );

        $this->execute($statement, $slugs);

        $users = [];
        foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $user) {
            if (!isset($users[$user['slug']])) {
                $users[$user['slug']] = [];
            }

            $user['meta_updated'] = (bool) $user['meta_updated'];

            $users[$user['slug']][] = $user;
        }

        return $users;
    }

    /**
     * {@inheritdoc}
     */
    public function hasUser($slug, $userSlug)
    {
        if (empty($slug) || empty($userSlug)) {
            return false;
        }

        $statement = $this->getConnection()->prepare(
            sprintf('select count(1) from %s where slug = :slug and user_slug = :user_slug', $this->tableName)
        );
        $statement->bindParam(':slug', $slug);
        $statement->bindParam(':user_slug', $userSlug);

        $this->execute($statement);

        return 1 === (int) $statement->fetchColumn();
    }

    /**
     * {@inheritdoc}
     */
    public function addUser($slug, User $user)
    {
        if (empty($slug) || empty($user)) {
            return false;
        }

        if ($this->hasUser($slug, $user->getSlug())) {
            return $this->updateUserProperties($slug, $user);
        }

        $statement = $this->getConnection()->prepare(
            sprintf('insert ignore into %s (slug, user_slug, user_name, meta_updated, added_at, modified_at) values (?, ?, ?, ?, ?, ?)', $this->tableName)
        );
        $statement->bindValue(1, $slug);
        $statement->bindValue(2, $user->getSlug());
        $statement->bindValue(3, $user->getTitle());
        $statement->bindValue(4, false, \PDO::PARAM_BOOL);
        $statement->bindValue(5, $this->dateFactory->newStorageDate());
        $statement->bindValue(6, $this->dateFactory->newStorageDate());

        return $this->execute($statement);
    }

    /**
     * {@inheritdoc}
     */
    public function updateUserProperties($slug, User $user, array $properties = [])
    {
        if (empty($slug) || empty($user)) {
            return false;
        }

        if (!$this->hasUser($slug, $user->getSlug())) {
            $this->addUser($slug, $user);
        }

        if (!isset($properties['modified_at'])) {
            $properties['modified_at'] = $this->dateFactory->newStorageDate();
        }

        $setters = [];
        foreach ($properties as $key => $value) {
            $setters[] = sprintf('%s = :%s', $key, $key);
        }

        $statement = $this->getConnection()->prepare(
            sprintf('update %s set %s where slug = :slug and user_slug = :user_slug', $this->tableName, implode(', ', $setters))
        );
        $statement->bindValue(':slug', $slug);
        $statement->bindValue(':user_slug', $user->getSlug());

        foreach ($properties as $key => $value) {
            $statement->bindValue(sprintf(':%s', $key), $value);
        }

        return $this->execute($statement);
    }

    /**
     * {@inheritdoc}
     */
    public function removeUser($slug, $userSlug)
    {
        if (empty($slug) || empty($userSlug)) {
            return false;
        }

        $statement = $this->getConnection()->prepare(
            sprintf('delete from %s where slug = :slug and user_slug = :user_slug', $this->tableName)
        );
        $statement->bindParam(':slug', $slug);
        $statement->bindParam(':user_slug', $userSlug);

        return $this->execute($statement);
    }

    /**
     * {@inheritdoc}
     */
    public function purgeStale()
    {
        if (empty($this->data)) {
            return false;
        }

        $datetime = $this->dateFactory->newStorageDate();
        $datetime->modify(sprintf('-%d seconds', $this->expiry));

        $statement = $this->getConnection()->prepare(
            sprintf('select count(1) from %s where modified_at >= \'%s\'', $this->tableName, $datetime->format('Y-m-d H:i:s'))
        );

        $this->execute($statement);

        $totalExpired = (int) $statement->fetchColumn();

        if (0 === $totalExpired) {
            $statement = $this->getConnection()->prepare(
                sprintf('truncate table %s', $this->tableName)
            );

            return $this->execute($statement);
        }

        $statement = $this->getConnection()->prepare(
            sprintf('delete from %s where modified_at < \'%s\'', $this->tableName, $datetime->format('Y-m-d H:i:s'))
        );

        return $this->execute($statement);
    }

    /**
     * @return \PDO
     */
    private function getConnection()
    {
        return $this->dsn
            ->getConnectionsForReadWrite()
            ->offsetGet(0) // \ConnectionCouplet
            ->getConnection() // \MySQLDatabase
            ->getConnection();
    }

    /**
     * @param \PDOStatement $statement
     * @param array|null    $params
     *
     * @return int
     *
     * @throws \PDOException
     * @throws \Exception
     */
    private function execute(\PDOStatement $statement, array $params = null)
    {
        static $tries = 0;

        try {
            $statement->execute($params);

            return $statement->rowCount();
        } catch (\PDOException $e) {
            ++$tries;

            if ($tries > 1) {
                throw new \Exception($e->getMessage());
            }

            // ERROR 1146 (42S02): Table 'test.no_such_table' doesn't exist
            if ($e->getCode() == '42S02') {
                $this->getConnection()->exec($this->createSchema());
            }

            return $this->execute($statement, $params);
        }
    }

    /**
     * @return string
     */
    private function createSchema()
    {
        return <<<EOF
drop table if exists {$this->tableName};
create table {$this->tableName} (
  slug varchar(255) not null,
  user_slug varchar(255) not null,
  user_name varchar(255) not null,
  meta_updated tinyint(1) default 0,
  added_at datetime,
  modified_at datetime,
  primary key (slug, user_slug)
);
EOF;
    }
}
