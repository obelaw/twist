<?php

namespace Obelaw\Twist\Models;

use Obelaw\Twist\Base\BaseModel;

class Action extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string|null
     */
    protected $table = 'twist_actions';

    protected $fillable = [
        'code',
        'name',
        'class_name',
    ];

    public function logs()
    {
        return $this->hasMany(ActionLog::class, 'action_id');
    }
}
