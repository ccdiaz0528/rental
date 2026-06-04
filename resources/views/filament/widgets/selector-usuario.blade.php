<div wire:poll.5s="refreshIfContextChanged">
    @if($this->isAdmin())
    <div class="rounded-[16px] border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div class="flex flex-wrap items-center gap-2 sm:gap-4 px-3 sm:px-5 py-2 sm:py-3">
            <span class="text-sm font-medium text-slate-600 dark:text-slate-300">Ver datos de:</span>
            <select wire:model.live="selectedUserId" class="rounded-xl border border-gray-300 bg-white px-3.5 py-2 text-sm shadow-sm focus:border-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-400/20 dark:border-white/10 dark:bg-gray-950 dark:text-white min-w-[180px] sm:min-w-[220px]">
                <option value="0">Todos los usuarios</option>
                @foreach($this->getUsersForSelector() as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
            @if($selectedUserId > 0)
                <span class="max-w-[160px] truncate rounded-full bg-primary-50 px-2.5 py-0.5 text-[11px] font-semibold text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                    {{ $this->getSelectedUserName() }}
                </span>
            @endif
        </div>
    </div>
    @endif
</div>
