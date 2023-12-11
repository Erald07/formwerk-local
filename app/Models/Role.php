<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Zizaco\Entrust\EntrustRole;
use App\Observers\RoleObserver;

class Role extends EntrustRole
{
    use HasFactory;

    public static function boot()
    {
        parent::boot();

        $company = company();

        static::observe(RoleObserver::class);

        static::addGlobalScope('company', function (Builder $builder) use ($company) {
            if ($company) {
                $builder->where('roles.company_id', '=', $company->id);
            }
        });
    }

    public function permissions()
    {
        return $this->hasMany(PermissionRole::class, 'role_id');
    }

    public function roleuser()
    {
        return $this->hasMany(RoleUser::class, 'role_id');
    }
}
