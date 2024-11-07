<?php

namespace App\Traits;

trait FindTrait
{
    public static function findById($id)
    {
        return !empty($id) ? self::where('id', $id)->first() : null;
    }

    public static function findByUuid($uuid)
    {
        return !empty($uuid) ? self::where('uuid', $uuid)->first() : null;
    }

    public static function findByName($name)
    {
        return !empty($name) ? self::where('name', $name)->first() : null;
    }
}
