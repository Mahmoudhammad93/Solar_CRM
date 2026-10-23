<x-filament-panels::page>
    <div class="grid gap-6 md:grid-cols-2 mb-6">
        <x-filament::section>
            <x-slot name="heading">Overdue tasks</x-slot>
            <ul class="text-sm space-y-2">
                @forelse($this->overdueTasks as $task)
                    <li class="text-danger-600">
                        <strong>{{ $task->title }}</strong>
                        — due {{ optional($task->due_at)->toDateTimeString() }}
                        ({{ $task->owner?->name }})
                    </li>
                @empty
                    <li class="text-gray-500">No overdue tasks in scope.</li>
                @endforelse
            </ul>
        </x-filament::section>
        <x-filament::section>
            <x-slot name="heading">Action history (audit)</x-slot>
            <ul class="text-sm space-y-2 max-h-64 overflow-auto">
                @forelse($this->recentAudit as $log)
                    <li>
                        <span class="text-gray-500">{{ optional($log->created_at)->toDateTimeString() }}</span>
                        · {{ $log->action }}
                        · {{ $log->entity_reference }}
                        · {{ $log->user?->name }}
                    </li>
                @empty
                    <li class="text-gray-500">No recent audit events.</li>
                @endforelse
            </ul>
        </x-filament::section>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
