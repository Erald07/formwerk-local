<?php

namespace App\Models;

use App\Observers\WebFontObserver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Webfont extends Model
{
    use HasFactory, SoftDeletes;

    public static function boot()
    {
        parent::boot();

        // $company = company();

        static::observe(WebFontObserver::class);

        // static::addGlobalScope('company', function (Builder $builder) use ($company) {
        //     if ($company) {
        //         $builder->where('laravel_uap_leform_webfonts.company_id', '=', $company->id);
        //     }
        // });
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'laravel_uap_leform_webfonts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'family',
        'variants',
        'subsets',
        'source',
        'deleted',
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
}
