<?php

namespace CrowdFusion\Tests\Plugin\ActiveEditsPlugin\Repository;

use CrowdFusion\Plugin\ActiveEditsPlugin\Repository\InMemoryActiveEditRepository;
use CrowdFusion\Plugin\ActiveEditsPlugin\Entity\User;

class InMemoryActiveEditRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var InMemoryActiveEditRepository */
    protected $repository;

    /** @var User */
    protected $user;

    /** @var DateFactory */
    protected $dateFactory;

    protected function setUp()
    {
        $this->user = new User();
        $this->user->setSlug('jonnytest');
        $this->user->setTitle('Jonny Test');

        $this->dateFactory = new \DateFactory('America/Los_Angeles', 'America/Los_Angeles');

        $this->repository = new InMemoryActiveEditRepository(45, $this->dateFactory);
    }

    protected function tearDown()
    {
        unset($this->repository);
    }

    public function testCheckExpireMember()
    {
        $slug = 'testme';
        $this->repository->addUser($slug, $this->user);

        // set new user
        $user = new User();
        $user->setSlug('bobdoll');
        $user->setTitle('Bob Doll');
        $this->repository->addUser($slug, $user);

        // expire user
        $date = $this->dateFactory->newStorageDate()->sub(
            new \DateInterval(sprintf('PT%dS', 10000))
        );
        $this->repository->updateUserProperties($slug, $user, array(
            'modified_at' => $date,
        ));

        $users = $this->repository->getUsers(array($slug));
        $users = $this->removeOutputDatetime($users, true);

        $this->assertCount(1, $users[$slug]);
        $this->assertEquals($users[$slug], array(array(
            'slug' => $slug,
            'user_slug' => $this->user->getSlug(),
            'user_name' => $this->user->getTitle(),
            'meta_updated' => false,
        )));
    }

    public function testUpdateUserProperties()
    {
        $slug = 'testme';
        $this->repository->addUser($slug, $this->user);

        $users = $this->repository->getUsers(array($slug));
        $this->assertCount(1, $users[$slug]);

        $date = $this->dateFactory->newStorageDate()->add(
            new \DateInterval(sprintf('PT%dS', 10000))
        );
        $this->repository->updateUserProperties($slug, $this->user, array(
            'modified_at' => $date,
        ));

        $users = $this->repository->getUsers(array($slug));

        $modifiedAt = is_string($users[$slug][0]['modified_at'])
            ? $users[$slug][0]['modified_at']
            : $users[$slug][0]['modified_at']->toMySQLDate();

        $this->assertEquals($date->toMySQLDate(), $modifiedAt);
    }

    public function testRemoveUser()
    {
        $slug = 'testme';
        $this->repository->addUser($slug, $this->user);

        $users = $this->repository->getUsers(array($slug));
        $this->assertCount(1, $users[$slug]);

        $this->repository->removeUser($slug, $this->user->getSlug());
        $this->assertFalse(!isset($users[$slug]));
    }

    public function testPurgeStale()
    {
        $slug = 'testme';
        $this->repository->addUser($slug, $this->user);

        $users = $this->repository->getUsers(array($slug));
        $this->assertCount(1, $users[$slug]);

        // expire user
        $date = $this->dateFactory->newStorageDate()->sub(
            new \DateInterval(sprintf('PT%dS', 10000))
        );
        $this->repository->updateUserProperties($slug, $this->user, array(
            'modified_at' => $date,
        ));

        $this->repository->purgeStale();
        $this->assertFalse(!isset($users[$slug]));
    }

    /**
     * @param array $items
     * @papram bool $multiSlugs
     *
     * @return array
     */
    protected function removeOutputDatetime(array $items, $multiSlugs = false)
    {
        foreach ($items as &$item) {
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

        return $items;
    }
}
