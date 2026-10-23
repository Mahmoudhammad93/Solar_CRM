<x-filament-panels::page>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-300">
        API must be enabled under Settings → API &amp; system. Full secret is shown <strong>once</strong> after creation; only a prefix is stored for display. Rotate = revoke + create new.
    </div>

    @if(filled($this->revealedPlainToken))
        <div class="mb-4 rounded-xl border border-amber-400 bg-amber-50 px-4 py-3 text-sm dark:bg-amber-950/40">
            <div class="font-medium">Copy this token now — it will not be shown again:</div>
            <code class="mt-2 block break-all select-all">{{ $this->revealedPlainToken }}</code>
        </div>
    @endif

    {{ $this->table }}
</x-filament-panels::page>
