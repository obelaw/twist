<?php

namespace Obelaw\Twist\Models;

use Obelaw\Twist\Base\BaseModel;
use Obelaw\Twist\Models\ActionCauser;

class ActionLog extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string|null
     */
    protected $table = 'twist_action_logs';

    protected $fillable = [
        'action_id',
        'causer_id',
        'subject_type',
        'subject_id',
        'payload',
        'status',
        'exception',
    ];

    public $timestamps = false; // نكتفي بـ created_at فقط
    protected $guarded = [];
    protected $casts = [
        'payload' => 'array',
    ];

    public function action()
    {
        return $this->belongsTo(Action::class);
    }

    public function causer()
    {
        return $this->belongsTo(ActionCauser::class);
    }
}
