<ul class="mt-3 divide-y divide-hairline">
    @foreach ($tasks as $task)
        <li>
            <a href="{{ $task->phase->url() }}" wire:navigate class="flex items-start justify-between gap-3 py-2.5 hover:text-primary">
                <span class="min-w-0">
                    <span class="block text-sm font-medium text-pretty text-foreground">{{ $task->title }}</span>
                    <span class="block truncate text-xs text-muted-foreground">
                        {{ $task->phase->collection ? $task->phase->collection->label().' · ' : '' }}{{ $task->phase->name() }}
                    </span>
                </span>
                <span class="shrink-0 text-xs font-semibold {{ $late ? 'text-danger' : 'text-muted-foreground' }}">
                    {{ $task->due_on->isToday() ? __('common.today') : $task->due_on->translatedFormat('j M') }}
                </span>
            </a>
        </li>
    @endforeach
</ul>
