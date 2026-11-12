<div class="space-y-6">
    <x-page-header :title="__('products.title')" :subtitle="__('products.subtitle')" />

    @if ($collections->isEmpty())
        <div class="panel">
            <x-empty-state icon="shirt" :title="__('collections.empty')" :body="__('collections.empty_hint')">
                <a href="{{ route('collections.index') }}" wire:navigate class="btn-primary btn-sm">{{ __('collections.add') }}</a>
            </x-empty-state>
        </div>
    @else
        <div class="space-y-6">
            @foreach ($collections as $collection)
                <section class="space-y-3" wire:key="set-{{ $collection->id }}">
                    <a href="{{ route('collections.show', $collection) }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm font-semibold text-foreground hover:text-primary">
                        <span>{{ $collection->label() }}</span>
                        <x-icon name="chevron-right" class="size-4" />
                    </a>
                    <x-couple-set :collection="$collection" />
                </section>
            @endforeach
        </div>
    @endif
</div>
