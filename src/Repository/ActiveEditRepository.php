<?php

namespace CrowdFusion\Plugin\ActiveEditsPlugin\Repository;

use CrowdFusion\Plugin\ActiveEditsPlugin\Entity\User;

interface ActiveEditRepository
{
    /**
     * Returns a list of active users for a given slug.
     *
     * @param array $slugs
     *
     * @return array
     */
    public function getUsers(array $slugs);

    /**
     * Check if user exists for a specific slug.
     *
     * @param string $slug
     * @param string $userSlug
     *
     * @return bool
     */
    public function hasUser($slug, $userSlug);

    /**
     * Adds a user to a given slug.
     *
     * @param string $slug
     * @param User   $user
     *
     * @return int
     */
    public function addUser($slug, User $user);

    /**
     * Updates specific user properties of a given slug.
     *
     * @TODO, rename method. We are updating the usersActiveEdit and not the user
     *
     * @param string $slug
     * @param User   $user
     * @param array  $properties
     *
     * @return int
     */
    public function updateUserProperties($slug, User $user, array $properties);

    /**
     * Removes a user from a given slug.
     *
     * @param string $slug
     * @param string $userSlug
     *
     * @return int
     */
    public function removeUser($slug, $userSlug);

    /**
     * Removes a users after a given number of seconds.
     *
     * @return bool|int
     */
    public function purgeStale();
}
