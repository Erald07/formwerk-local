<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
  use HasFactory, SoftDeletes;
  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'company_name',
    'company_email',
    'company_phone',
    'company_logo',
    'company_favicon'
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

  /**
   * Relation ship
   */
  public function forms()
  {
    return $this->hasMany(Form::class);
  }

  public function users()
  {
    return $this->hasMany(User::class);
  }

  public function accessToken()
  {
    return $this->hasMany(AccessToken::class);
  }
}
