<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;


class HelperRepository
{
    public static function dumpSql($model, bool $exit = true): void
    {
        $sql = Str::replaceArray('?', $model->getBindings(), $model->toSql());
        if ($exit) {
            dd($sql);
        } else {
            echo $sql . "\n\n";
        }
    }

    public static function getSql($query)
    {
        $bindings = [];
        foreach ($query->getBindings() as $b) {
            $bindings[] = "'".$b."'";
        }
        return Str::replaceArray('?', $bindings, $query->toSql());
    }

    public static function hash(array $data): string
    {
        $algo = env('HASH_ALGO');
        $salt = env('HASH_SALT');
        $data = strtolower($data);
        return base64_encode(hash($algo, $data . $salt, true));
    }
}
