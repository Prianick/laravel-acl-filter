<?php

declare(strict_types=1);

namespace App\Services\Alc;

use App\Models\User;
use App\Models\UserAclSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AclService
{
    const PROJECT_FILTER = 'projectFilter';

    public static $instance = null;

    protected $config = [];

    protected Collection $currentRestrictions;
    protected Request $request;
    protected bool $isActive = false;

    private function __construct()
    {
        $this->config = config('user_acl_settings');
    }

    private function __clone()
    {
    }

    private function __wakeup(): void
    {
    }

    public function switchStatus(bool $status = true): self
    {
        $this->isActive = $status;

        return $this;
    }

    public function setRequest(Request $request): self
    {
        $this->request = $request;

        return $this;
    }

    public function addRestrictionsToRequest(Request $request): self
    {
        if (!$this->isActive) {
            return $this;
        }

        if ($request->method() !== 'GET' || Auth::user()->hasRole(User::ROLE_SUPERADMIN)) {
            return $this;
        }
        $additionParams = [];
        $this->currentRestrictions->map(function ($item) use (&$additionParams, $request): void {
            foreach ($this->config['filterFields'][$item->filter_name] as $paramsName) {
                $getParameter = $request->get($paramsName, false);
                if ($getParameter === false) {
                    $additionParams[$paramsName] = $item->value;
                    continue;
                }
                if (is_array($getParameter)) {
                    $intersections = array_values(array_intersect($item->value, $getParameter));
                    $additionParams[$paramsName] = empty($intersections) ? $item->value : $intersections;
                } else {
                    if (!in_array($getParameter, $item->value, true)) {
                        abort(403);
                    }
                }
            }
        });
        $request->merge($additionParams);

        return $this;
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        if (empty($instance->currentRestrictions)) {
            self::$instance->initCurrentRestrictions();
        }
        return self::$instance;
    }


    public function upsert(User $user, array $settingsParams): Collection
    {
        foreach ($settingsParams as $filterName => $values) {
            $settings = UserAclSetting::query()->where([
                'user_id' => $user->id,
                'filter_name' => $filterName,
            ])->first();
            if (empty($settings)) {
                $settings = new UserAclSetting();
            }
            $settings->user_id = $user->id;
            $settings->filter_name = $filterName;
            $settings->value = $values;
            if (!Gate::inspect('update', $settings)->allowed()) {
                abort(403);
            }
            $settings->save();
        }
        $this->initCurrentRestrictions();

        return $this->currentRestrictions;
    }

    public function delete(User $user)
    {
        return UserAclSetting::query()->where('user_id', $user->id)->delete();
    }

    public function addRestrictionToModel(Builder $builder): self
    {
        if (!$this->isActive) {
            return $this;
        }

        foreach ($builder->getModel()->aclLimits as $filterName => $column) {
            if (!isset($this->currentRestrictions[$filterName])) {
                continue;
            }
            $restriction = $this->currentRestrictions[$filterName];
            $builder->whereIn($column, $restriction->value);
        }

        return $this;
    }

    public function addRestrictionToQuery($builder, $column, $values = null, $exclude = false): self
    {
        if (!$this->isActive) {
            return $this;
        }

        foreach ($this->getFilterNamesForColumn($column) as $filterName) {
            if (!isset($this->currentRestrictions[$filterName])) {
                continue;
            }
            $restriction = $this->currentRestrictions[$filterName];
            if (!empty($values) && $exclude === false) {
                $restriction->value = array_intersect($restriction->value, $values);
            }
            $builder->whereIn($column, $restriction->value);
        }

        return $this;
    }

    public static function refresh()
    {
        return self::getInstance()->initCurrentRestrictions();
    }

    protected function getFilterNamesForColumn($column)
    {
        foreach ($this->config['filterFields'] as $filterName => $columns) {
            if (in_array($column, $columns, true)) {
                yield $filterName;
            }
        }
        return null;
    }

    protected function initCurrentRestrictions(): self
    {
        $this->currentRestrictions = new Collection();
        if (!Auth::guest()) {
            $this->currentRestrictions = UserAclSetting::query()->where('user_id', Auth::user()->id)
                ->get()->keyBy('filter_name');
        }
        return $this;
    }
}
