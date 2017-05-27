<?php

namespace Chrisbjr\ApiGuard\Repositories;

use App;
use Cache;
use Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;
use Chrisbjr\ApiGuard\Models\ApiKey;

/**
 * Class ApiKeyRepository
 *
 * @package Chrisbjr\ApiGuard\Repositories
 */
abstract class ApiKeyRepository extends Eloquent
{

    use SoftDeletes;

    protected $table = 'api_keys';

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'user_id',
        'key',
        'level',
        'ignore_limits',
    ];

    /**
     * @param $key
     * @param int $rememberApiKeyDuration
     * @return ApiKeyRepository
     */
    public function getByKey($key, $rememberApiKeyDuration = 0)
    {
        $apiKey = $rememberApiKeyDuration > 0 ?
            Cache::remember('api_keys:' . $key, $rememberApiKeyDuration, function () use ($key) {
                return self::where('key', '=', $key)->first();
            }) :
            self::where('key', '=', $key)->first();
        return !empty($apiKey) && $apiKey->exists === true ? $apiKey : null;
    }

    /**
     * A sure method to generate a unique API key
     *
     * @return string
     */
    public static function generateKey()
    {
        do {
            $salt = sha1(time() . mt_rand());
            $newKey = substr($salt, 0, 40);
        } // Already in the DB? Fail. Try again
        while (self::keyExists($newKey));

        return $newKey;
    }

    /**
     * Make an ApiKey
     *
     * @param null $userId
     * @param int $level
     * @param bool $ignoreLimits
     * @return static
     */
    public static function make($userId = null, $level = 10, $ignoreLimits = false)
    {
        return self::create([
            'user_id'       => $userId,
            'key'           => self::generateKey(),
            'level'         => $level,
            'ignore_limits' => $ignoreLimits,
        ]);
    }

    /**
     * Returns a key by user_id and id fields
     *
     * @param int $id
     * @param int $userId
     * @return ApiKey
     */
    public static function getByIdAndUserId($id, $userId)
    {
        return self::where([
            'id'        =>  $id,
            'user_id'   =>  $userId
        ])->first();
    }

    /**
     * Checks whether a key exists in the database or not
     *
     * @param $key
     * @return bool
     */
    private static function keyExists($key)
    {
        $apiKeyCount = self::where('key', '=', $key)->limit(1)->count();

        if ($apiKeyCount > 0) {
            return true;
        }

        return false;
    }
}

