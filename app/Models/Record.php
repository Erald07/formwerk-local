<?php

namespace App\Models;

use App\Observers\RecordObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Record extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'laravel_uap_leform_records';


    protected static function boot()
    {
        parent::boot();

        // static::observe(RecordObserver::class);

        static::addGlobalScope('deleted', function (Builder $builder) {
            $builder->where('laravel_uap_leform_records.deleted', '=', 0);
        });

        $company = company();

        static::addGlobalScope('company', function (Builder $builder) use ($company) {
            if ($company) {
                $builder->where('laravel_uap_leform_records.company_id', '=', $company->id);
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'personal_data_keys',
        'unique_keys',
        'fields',
        'info',
        'status',
        'str_id',
        'gateway_id',
        'amount',
        'currency',
        'created',
        'deleted',
        'predefined_values',
        'system_variables',
        'dynamic_form_name',
        'dynamic_form_name_values',
        'dynamic_form_name_with_values',
        'xml_file_name',
        'csv_file_name',
        'custom_report_file_name',
        'primary_field_id',
        'primary_field_value',
        'secondary_field_id',
        'secondary_field_value'
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

    public function form()
    {
        return $this->belongsTo(Form::class);
    }
}
