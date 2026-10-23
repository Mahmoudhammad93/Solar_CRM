<x-filament-panels::page>
    {{ $this->form }}

    <div class="mt-4 mb-2 text-sm text-gray-600 dark:text-gray-300 flex flex-wrap gap-4">
        <span>Filtered rows: <strong>{{ $this->filteredRowCount }}</strong></span>
        @foreach($this->totals as $k => $v)
            <span><strong>{{ $k }}</strong> = {{ $v }}</span>
        @endforeach
        <span class="text-xs text-gray-400">Same ReportQuery feeds table, CSV, and PDF.</span>
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-white/10">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-white/5">
                <tr>
                    @foreach($this->columns as $col)
                        <th class="px-3 py-2 text-left font-medium cursor-pointer" wire:click="sortBy('{{ $col['key'] }}')">
                            {{ $col['label'] }}
                            @if($this->sortColumn === $col['key'])
                                {{ $this->sortDirection === 'asc' ? '↑' : '↓' }}
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($this->pagedRows as $row)
                    <tr class="border-t border-gray-100 dark:border-white/5">
                        @foreach($this->columns as $col)
                            <td class="px-3 py-2">{{ $row[$col['key']] ?? '' }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td class="px-3 py-6 text-gray-500" colspan="{{ count($this->columns) }}">No rows for current filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3 flex items-center gap-3 text-sm">
        <x-filament::button size="sm" color="gray" wire:click="previousPage" :disabled="$this->page <= 1">Previous</x-filament::button>
        <span>Page {{ $this->page }} / {{ $this->lastPage }}</span>
        <x-filament::button size="sm" color="gray" wire:click="nextPage" :disabled="$this->page >= $this->lastPage">Next</x-filament::button>
    </div>
</x-filament-panels::page>
