<?php

namespace App\Models;

use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Zizaco\Entrust\Traits\EntrustUserTrait;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    // workafuckinground
    use SoftDeletes, EntrustUserTrait {
        SoftDeletes::restore as sfRestore;
        EntrustUserTrait::restore as restore;
    }

    public function restore() {
        $this->sfRestore();
        // Cache::tags(Config::get('entrust.role_user_table'))->flush();
    }

    protected static function boot()
    {
        parent::boot();

        static::observe(UserObserver::class);

        static::addGlobalScope('active', function (Builder $builder) {
            $builder->where('users.status', '=', 'active');
        });

        $company = company();

        static::addGlobalScope('company', function (Builder $builder) use ($company) {
            if ($company) {
                $builder->where('users.company_id', '=', $company->id);
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'super_admin',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function forms()
    {
        return $this->hasMany(Form::class);
    }

    public function role()
    {
        return $this->hasMany(RoleUser::class, 'user_id');
    }
}
