<?php

namespace Obelaw\Twist\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Cache;
use Obelaw\Twist\Base\BaseModel;

class ActionCauser extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string|null
     */
    protected $table = 'twist_action_causers';

    public function causerable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * تحويل الموديل الحالي لـ Causer ID موحد مع استخدام Cache للأداء
     */
    public static function resolveId(?Model $user): ?int
    {
        if (!$user) {
            return null; // Guest or System Action without user
        }

        $cacheKey = "causer_id_" . get_class($user) . "_" . $user->getKey();

        return Cache::rememberForever($cacheKey, function () use ($user) {
            return static::firstOrCreate([
                'causerable_type' => get_class($user),
                'causerable_id'   => $user->getKey(),
            ])->id;
        });
    }
}
