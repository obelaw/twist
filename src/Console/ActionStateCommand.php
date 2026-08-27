<?php

declare(strict_types=1);

namespace Obelaw\Twist\Console;

use Illuminate\Console\Command;
use Obelaw\Twist\Models\Action;
use Obelaw\Twist\Models\ActionLog;

final class ActionStateCommand extends Command
{
    protected $signature = 'twist:action:state
                            {--action= : Show logs for a specific action by code}
                            {--status= : Filter logs by status (success/failed)}
                            {--limit=10 : Limit number of logs shown}';

    protected $description = 'List actions with their execution state';

    public function handle(): void
    {
        if ($actionCode = $this->option('action')) {
            $this->showActionLogs($actionCode);
            return;
        }

        $this->listActions();
    }

    private function listActions(): void
    {
        $actions = Action::withCount([
            'logs as total_runs',
            'logs as success_count' => fn($q) => $q->where('status', 'success'),
            'logs as failed_count' => fn($q) => $q->where('status', 'failed'),
        ])->get();

        if ($actions->isEmpty()) {
            $this->warn('No actions registered yet.');
            return;
        }

        $rows = $actions->map(fn(Action $action) => [
            $action->code,
            $action->name,
            $action->total_runs,
            $action->success_count,
            $action->failed_count,
            $action->total_runs > 0
                ? round(($action->success_count / $action->total_runs) * 100, 1) . '%'
                : '-',
        ]);

        $this->table(
            ['Code', 'Name', 'Total', 'Success', 'Failed', 'Success Rate'],
            $rows
        );
    }

    private function showActionLogs(string $actionCode): void
    {
        $action = Action::where('code', $actionCode)->first();

        if (!$action) {
            $this->error("Action not found: {$actionCode}");
            return;
        }

        $limit = (int) $this->option('limit');

        $query = ActionLog::where('action_id', $action->id);

        if ($status = $this->option('status')) {
            $query->where('status', $status);
        }

        $logs = $query->latest('created_at')->limit($limit)->get();

        if ($logs->isEmpty()) {
            $this->warn("No logs found for action: {$actionCode}");
            return;
        }

        $this->info("Action: {$action->code} ({$action->name})");
        $this->line("Class: {$action->class_name}");
        $this->newLine();

        $rows = $logs->map(fn(ActionLog $log) => [
            $log->id,
            $log->created_at,
            $log->status,
            $log->subject_type ? class_basename($log->subject_type) . '#' . $log->subject_id : '-',
            $log->exception ? substr($log->exception, 0, 60) : '-',
        ]);

        $this->table(
            ['ID', 'Date', 'Status', 'Subject', 'Exception'],
            $rows
        );
    }
}
