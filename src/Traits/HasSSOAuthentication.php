<?php

namespace Baliprov\SSOBroker\Traits;

use Baliprov\SSOBroker\Facades\SSOBroker;

trait HasSSOAuthentication
{
    /**
     * Find or create user from SSO payload
     *
     * @param object $payload SSO authentication payload
     * @return static
     */
    public static function findOrCreateFromSSO($payload)
    {
        $ssoIdColumn = static::getSSOIdColumn();
        $ssoUser = $payload->user ?? null;

        if (!$ssoUser || !isset($ssoUser->id)) {
            throw new \InvalidArgumentException('Invalid SSO payload: missing user data');
        }

        $user = static::where($ssoIdColumn, $ssoUser->id)->first();

        if (!$user) {
            $user = static::createFromSSO($payload);
        } else {
            $user->updateFromSSO($payload);
        }

        return $user;
    }

    /**
     * Create new user from SSO payload
     *
     * @param object $payload
     * @return static
     */
    protected static function createFromSSO($payload)
    {
        $ssoUser = $payload->user;
        $mapping = static::getSSOAttributeMapping();

        $attributes = [];
        foreach ($mapping as $localAttr => $ssoAttr) {
            if (is_callable($ssoAttr)) {
                $attributes[$localAttr] = $ssoAttr($payload);
            } elseif (isset($ssoUser->$ssoAttr)) {
                $attributes[$localAttr] = $ssoUser->$ssoAttr;
            }
        }

        return static::create($attributes);
    }

    /**
     * Update user data from SSO payload
     *
     * @param object $payload SSO authentication payload
     * @return bool
     */
    public function updateFromSSO($payload)
    {
        $ssoUser = $payload->user;
        $mapping = static::getSSOUpdateMapping();

        $attributes = [];
        foreach ($mapping as $localAttr => $ssoAttr) {
            if (is_callable($ssoAttr)) {
                $attributes[$localAttr] = $ssoAttr($payload);
            } elseif (isset($ssoUser->$ssoAttr)) {
                $attributes[$localAttr] = $ssoUser->$ssoAttr;
            }
        }

        if (!empty($attributes)) {
            return $this->update($attributes);
        }

        return true;
    }

    /**
     * Get SSO user ID
     *
     * @return string|int|null
     */
    public function getSSOUserId()
    {
        $column = static::getSSOIdColumn();
        return $this->$column;
    }

    /**
     * Get user roles from SSO session
     *
     * @return array
     */
    public function getSSOUserRoles()
    {
        return SSOBroker::getRoles();
    }

    /**
     * Check if user has specific SSO role
     *
     * @param string $role
     * @return bool
     */
    public function hasSSORole($role)
    {
        return SSOBroker::hasRole($role);
    }

    /**
     * Check if user has any of the specified SSO roles
     *
     * @param array $roles
     * @return bool
     */
    public function hasAnySSORole(array $roles)
    {
        return SSOBroker::hasAnyRole($roles);
    }

    /**
     * Get the column name for SSO user ID
     * Override this method in your model to customize
     *
     * @return string
     */
    protected static function getSSOIdColumn()
    {
        return 'sso_user_id';
    }

    /**
     * Get attribute mapping for creating user from SSO
     * Override this method in your model to customize
     *
     * Key: local attribute name
     * Value: SSO user attribute name or callable
     *
     * @return array
     */
    protected static function getSSOAttributeMapping()
    {
        return [
            'sso_user_id' => 'id',
            'name' => 'name',
            'email' => 'email',
        ];
    }

    /**
     * Get attribute mapping for updating user from SSO
     * Override this method in your model to customize
     *
     * @return array
     */
    protected static function getSSOUpdateMapping()
    {
        return [
            'name' => 'name',
            'email' => 'email',
        ];
    }

    /**
     * Scope to find by SSO user ID
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|int $ssoUserId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySSOUserId($query, $ssoUserId)
    {
        return $query->where(static::getSSOIdColumn(), $ssoUserId);
    }

    /**
     * Get the currently authenticated SSO user as model
     *
     * @return static|null
     */
    public static function currentSSOUser()
    {
        if (!SSOBroker::isAuthenticated()) {
            return null;
        }

        $ssoUserId = SSOBroker::getSSOUserId();
        if (!$ssoUserId) {
            return null;
        }

        return static::where(static::getSSOIdColumn(), $ssoUserId)->first();
    }
}
