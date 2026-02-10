<?php

namespace Baliprov\SSOBroker\Contracts;

interface SSOUserInterface
{
    /**
     * Find or create user from SSO payload
     *
     * @param object $payload SSO authentication payload
     * @return static
     */
    public static function findOrCreateFromSSO($payload);

    /**
     * Update user data from SSO payload
     *
     * @param object $payload SSO authentication payload
     * @return bool
     */
    public function updateFromSSO($payload);

    /**
     * Get SSO user ID
     *
     * @return string|int|null
     */
    public function getSSOUserId();

    /**
     * Get user roles from SSO
     *
     * @return array
     */
    public function getSSOUserRoles();
}
