<?php

namespace App\BudgetTracker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Services\UserService;

class Labels extends Model
{
    use HasFactory;

    public $hidden = [
        "created_at",
        "updated_at",
        "deleted_at"
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (empty($this->attributes['user_id'])) {
            $this->attributes['user_id'] = UserService::getCacheUserID();
        }

        foreach ($attributes as $k => $v) {
            $this->$k = $v;
        }
    }

    /**
     * The users that belong to the role.
     */
    public function entries()
    {
        return $this->belongsToMany(Entry::class, 'entry_labels');
    }


    /**
     * The users that belong to the role.
     */
    public function models()
    {
        return $this->belongsToMany(Models::class, 'model_labels');
    }

    /**
     * override get method for not return trashed elements
     * 
     * @return \Illuminate\Database\Eloquent\Collection<int, static>
     */
    public function get($columns = ['*'])
    {
        $results = parent::get($columns);
        return $results->withoutTrashed();
    }

    /**
     * scope user
     */
    public function scopeUser($query): void
    {
        $query->where('user_id', UserService::getCacheUserID());
    }
}
