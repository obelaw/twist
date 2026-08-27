<?php

namespace Obelaw\Twist\Base;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Obelaw\Trail\Facades\Trail;
use ReflectionClass;
use Throwable;

/**
 * An abstract base for creating executable action classes.
 *
 * This class provides a standardized way to create self-contained, single-purpose
 * service classes. It includes a static `make()` method for easy execution and
 * built-in, automatic event tracking & structured action logs.
 */
abstract class BaseAction
{
    /**
     * A flag to enable or disable event tracking for this action.
     *
     * @var bool
     */
    protected bool $trackable = true;

    /**
     * Manually override the event name for tracking.
     * If null, the name is generated automatically from the class name.
     *
     * @var string|null
     */
    protected ?string $trackableEvent = null;

    /**
     * The suffix to be removed from the class name when generating an event name.
     *
     * @var string
     */
    protected string $eventSuffix = 'Action';

    /**
     * Creates, resolves, and executes the action.
     *
     * @param mixed ...$arguments The arguments to be passed to the action's handle method.
     * @return mixed The result of the action's handle method.
     */
    public static function make(mixed ...$arguments): mixed
    {
        $static = app(static::class);

        try {
            // Execute the main handle logic
            $result = $static->handle(...$arguments);

            // Track successful execution
            $static->track($arguments, $result, 'success');

            return $result;
        } catch (Throwable $e) {
            // Track failed execution
            $static->track($arguments, null, 'failed', $e);

            throw $e;
        }
    }

    /**
     * Support Invokable syntax: $action(...$arguments)
     */
    public function __invoke(mixed ...$arguments): mixed
    {
        return static::make(...$arguments);
    }

    /**
     * Tracks the execution of the action as an event and saves action log.
     *
     * @param array $arguments
     * @param mixed $result
     * @param string $status
     * @param Throwable|null $exception
     * @return void
     */
    private function track(array $arguments, mixed $result = null, string $status = 'success', ?Throwable $exception = null): void
    {
        $eventName = $this->trackableEvent ?? $this->generateEventName();

        if (empty($eventName)) {
            return;
        }

        // Extract payload/changes
        $changes = $arguments ?? [];
        if (!is_array($changes)) {
            $changes = [];
        }

        $user = auth()->user();

        // 1. Existing Trail Facade Integration
        if ($this->trackable) {
            Trail::for()
                ->by($user)
                ->event($eventName)
                ->changes($changes)
                ->snapshot([])
                ->save();
        }

        // 2. Structural DB Logging (Actions + Causers + ActionLogs)
        $this->logToDatabase($user, $eventName, $changes, $result, $status, $exception);
    }

    /**
     * Save the structured log entry to the database.
     */
    protected function logToDatabase(?Model $user, string $eventName, array $payload, mixed $result, string $status, ?Throwable $exception): void
    {
        $actionId = $this->resolveActionId($eventName);
        $causerUniqueId = $this->resolveCauserUniqueId($user);

        // Subject resolution (If the result is an Eloquent Model)
        $subject = ($result instanceof Model) ? $result : null;

        // Create log record
        \Obelaw\Twist\Models\ActionLog::create([
            'action_id'    => $actionId,
            'causer_id'    => $causerUniqueId,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id'   => $subject ? $subject->getKey() : null,
            'payload'      => $this->sanitizeData($payload),
            'status'       => $status,
            'exception'    => $exception?->getMessage(),
        ]);
    }

    /**
     * Resolve Action ID from `actions` registry table with caching.
     */
    protected function resolveActionId(string $code): int
    {
        $className = static::class;

        return Cache::rememberForever("twist_action_id_" . md5($className), function () use ($className, $code) {
            return \Obelaw\Twist\Models\Action::firstOrCreate(
                ['class_name' => $className],
                [
                    'code' => $code,
                    'name' => Str::of(class_basename($className))->beforeLast($this->eventSuffix)->headline()->toString(),
                ]
            )->id;
        });
    }

    /**
     * Resolve unique Causer ID from `causers` proxy table with caching.
     */
    protected function resolveCauserUniqueId(?Model $user): ?int
    {
        if (!$user) {
            return null;
        }

        $cacheKey = "twist_causer_id_" . get_class($user) . "_" . $user->getKey();

        return Cache::rememberForever($cacheKey, function () use ($user) {
            return \Obelaw\Twist\Models\ActionCauser::firstOrCreate([
                'causerable_type' => get_class($user),
                'causerable_id'   => $user->getKey(),
            ])->id;
        });
    }

    /**
     * Sanitize sensitive inputs from being logged into the database.
     */
    protected function sanitizeData(array $data): array
    {
        $hidden = ['password', 'password_confirmation', 'secret', 'token', 'credit_card'];

        return collect($data)->mapWithKeys(function ($value, $key) use ($hidden) {
            return [
                $key => in_array(strtolower($key), $hidden) ? '********' : $value
            ];
        })->toArray();
    }

    /**
     * Generates a dot-separated event name from the action's class name.
     *
     * Example: `UpdateOrderStatusAction` becomes `update.order.status`.
     *
     * @return string
     */
    private function generateEventName(): string
    {
        $className = (new ReflectionClass($this))->getShortName();

        $className = Str::beforeLast($className, $this->eventSuffix);

        return Str::of($className)->snake('.')->toString();
    }
}
