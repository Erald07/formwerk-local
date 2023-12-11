<?php

namespace App\Models;

use App\Observers\FolderObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Folder extends Model
{
    use HasFactory, SoftDeletes;


    protected static function boot()
    {
        parent::boot();

        static::observe(FolderObserver::class);

        $company = company();

        static::addGlobalScope('company', function (Builder $builder) use ($company) {
            if ($company) {
                $builder->where('folders.company_id', '=', $company->id);
            }
        });
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'folders';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'parent_id',
        'level',
        'company_id'
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


    public function children()
    {
        return $this->hasMany(Folder::class, 'parent_id');
    }

    public function childrenList($list, $level = 0)
    {
        $level += 1;
        $childrens = $this->children;
        foreach($childrens as $child) {
            $name = '';
            for($i =0; $i < $level; $i++) {
                $name .= '- ';
            }
            $name .= "$child->name";
            $list[] = [
                'id' => $child->id,
                'name' => $name,
            ];
            $list = $child->childrenList($list, $level);
        }
        return $list;
    }

    public function forms()
    {
        return $this->hasMany(Form::class, 'folder_id');
    }

    public function totalForms()
    {
        return $this->forms->count();
    }


    public function getChildrenIds()
    {
        $childrenIds = [];
        $all = self::pluck('parent_id', 'id');
        foreach ($all as $id => $parent) {
            while ($parent) {
                if ($parent === $this->id) {
                    $childrenIds[] = $id;
                }
                $parent = $all[$parent] ?? null;
            }
        }
        return $childrenIds;
    }
    
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }
}
