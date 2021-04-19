<?php

declare(strict_types=1);

namespace App\Models;

use App\Audit\LinkResolvers\UserAclSettingLinkResolver;

class UserAclSetting extends Model
{
    protected $fillable = [
        'user_id',
        'value',
        'filter_name',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    protected string $auditLinkResolver = UserAclSettingLinkResolver::class;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
