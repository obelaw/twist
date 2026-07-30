<?php

namespace Obelaw\Twist\Models;

use Illuminate\Database\Eloquent\Model;
use Obelaw\Twist\Models\ActionCauser;

class ActionLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string|null
     */
    protected $table = 'twist_action_logs';

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
