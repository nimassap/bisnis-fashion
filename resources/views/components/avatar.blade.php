@props([
    'name',
    'size' => 'md',
    // A halo of page colour between the avatar and whatever sits behind it, so
    // it still reads as a distinct object on a busy toolbar.
    'ring' => true,
])

@php
    use Illuminate\Support\Str;

    // First letters of the first two words: "Budi Santoso" becomes BS.
    $initials = Str::of($name)
        ->squish()
        ->explode(' ')
        ->take(2)
        ->map(fn (string $word) => Str::substr($word, 0, 1))
        ->implode('');

    $sizes = [
        'sm' => 'size-8 text-[11px]',
        'md' => 'size-9 text-xs',
        'lg' => 'size-11 text-sm',
    ];
@endphp

{{--
| Initials on the user's own palette.
|
| The gradient is built from the two theme colours rather than a fixed brand
| purple, so the avatar belongs to whatever palette the account is wearing.
--}}
<span
    {{ $attributes->merge(['class' => 'avatar '.($sizes[$size] ?? $sizes['md']).($ring ? ' ring-2 ring-card' : '')]) }}
    style="background-image: linear-gradient(140deg, var(--color-primary) 0%, var(--color-accent) 100%)"
    aria-hidden="true"
>{{ $initials }}</span>
