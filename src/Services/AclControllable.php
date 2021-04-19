<?php

declare(strict_types=1);

namespace App\Services\Alc;

use Illuminate\Database\Eloquent\Builder;

trait AclControllable
{
    public static function query(): Builder
    {
        $query = parent::query();
        AclService::getInstance()->addRestrictionToModel($query);

        return $query;
    }

    public static function getAclLimitingColumn()
    {
        return (new self())->aclLimits;
    }
}
