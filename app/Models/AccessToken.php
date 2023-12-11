<?php

namespace App\Models;

use App\Observers\AccessTokenObserver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class AccessToken extends Model
{
    use HasFactory, SoftDeletes;
    public static function boot()
    {
        parent::boot();

        $company = company();

        static::observe(AccessTokenObserver::class);

        static::addGlobalScope('company', function (Builder $builder) use ($company) {
            if ($company) {
                $builder->where('access_tokens.company_id', '=', $company->id);
            }
        });
    }
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "name",
        "token",
        "user_id",
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [];

    public function restrictedDomains()
    {
        return $this->hasMany(RestrictedDomain::class);
    }
}
