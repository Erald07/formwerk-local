<?php

namespace App\Models;

use App\Observers\FormObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Form extends Model
{
    use HasFactory, SoftDeletes;


    protected static function boot()
    {
        parent::boot();

        static::observe(FormObserver::class);

        static::addGlobalScope('deleted', function (Builder $builder) {
            $builder->where('laravel_uap_leform_forms.deleted', '=', 0);
        });

        $company = company();

        static::addGlobalScope('company', function (Builder $builder) use ($company) {
            if ($company) {
                $builder->where('laravel_uap_leform_forms.company_id', '=', $company->id);
            }
        });
    }
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'laravel_uap_leform_forms';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'options',
        'pages',
        'elements',
        'cache_style',
        'cache_html',
        'cache_uids',
        'cache_time',
        'active',
        'created',
        'modified',
        'deleted',
        'user_id',
        'short_link',
        'dynamic_name_values',
        'shareable',
        'share_date',
        'share_form_id',
        'share_user_id',
        'share_company_id',
        'folder_id'
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function preview()
    {
        return $this->hasOne(Preview::class);
    }

    public function records()
    {
        return $this->hasMany(Record::class);
    }

    public function totalRecords($courseIds = [])
    {
        if(!empty($courseIds) && count($courseIds) > 0) {
            return $this->records()->whereIn('moodle_course_id', $courseIds)->count();
        }
        return $this->records->count();
    }

    public function fieldValues()
    {
        return $this->hasMany(FieldValue::class);
    }
}
