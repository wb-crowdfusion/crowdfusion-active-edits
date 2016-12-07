<?php

namespace CrowdFusion\Plugin\ActiveEditsPlugin\Repository;

use CrowdFusion\Plugin\ActiveEditsPlugin\Entity\User;

class InMemoryActiveEditRepository implements ActiveEditRepository
{
    /** @var int */
    protected $expiry = 0;

    /** @var \DateFactory */
    protected $dateFactory;

    /** @var array */
    protected $data = [];

    /**
     * @param int $expiry
     * @param \DateFactory $dateFactory
     */
    public function __construct($expiry, \DateFactory $dateFactory)
    {
        $this->expiry = (int)$expiry;
        $this->dateFactory = $dateFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getUsers(array $slugs)
    {
        if (empty($slugs) || empty($this->data)) {
            return [];
        }

        $users = [];
        $now = $this->dateFactory->newStorageDate();

        foreach ($slugs as $slug) {
            if (isset($this->data[$slug]) && !empty($this->data[$slug])) {
                $users[$slug] = [];

                foreach ($this->data[$slug] as $user) {
                    $date = $this->dateFactory->newStorageDate($user['modified_at'])->add(
                        new \DateInterval(sprintf('PT%dS', $this->expiry))
                    );

                    if ($date >= $now) {
                        $users[$slug][] = $user;
                    }
                }
            }
        }

        return $users;
    }

    /**
     * {@inheritdoc}
     */
    public function hasUser($slug, $userSlug)
    {
        if (empty($slug) || empty($userSlug) || empty($this->data)) {
            return false;
        }

        return isset($this->data[$slug][$userSlug]);
    }

    /**
     * {@inheritdoc}
     */
    public function addUser($slug, User $user)
    {
        if (empty($slug) || empty($user)) {
            return false;
        }

        if (!isset($this->data[$slug])) {
            $this->data[$slug] = [];
        }

        if (!isset($this->data[$slug][$user->Slug])) {
            $this->data[$slug][$user->Slug] = [
                'slug' => $slug,
                'user_slug' => $user->Slug,
                'user_name' => $user->Title,
                'meta_updated' => false,
                'added_at' => $this->dateFactory->newStorageDate(),
                'modified_at' => $this->dateFactory->newStorageDate()
            ];

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function updateUserProperties($slug, User $user, array $properies)
    {
        if (empty($slug) || empty($user) || empty($this->data)) {
            return false;
        }

        if (!$this->hasUser($slug, $user->Slug)) {
            $this->addUser($slug, $user);
        }


        if (isset($this->data[$slug][$user->Slug])) {
            foreach ($properies as $key => $value) {
                $this->data[$slug][$user->Slug][$key] = $value;
            }

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function removeUser($slug, $userSlug)
    {
        if (empty($slug) || empty($userSlug) || empty($this->data)) {
            return false;
        }

        if (isset($this->data[$slug][$userSlug])) {
            unset($this->data[$slug][$userSlug]);

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function purgeStale()
    {
        $timestamp = time() - $this->expiry;

        foreach ($this->data as $slug => $users) {
            foreach ($users as $userSlug => $properties) {
                if (isset($properties['modified_at']) && $userSlug['modified_at'] < $timestamp) {
                    unset($this->data[$slug][$userSlug]);
                }
            }
        }
        return true;
    }
}
