@props([
    'label',
    'amount',
    'icon' => null,
    'tone' => 'muted-foreground',
    'change' => null,
    'comparedTo' => null,
    'higherIsBetter' => true,
    'hint' => null,
])

{{--
| The headline-figure card, kept as its own name because four screens ask for
| it by that name — but there is only one implementation of the thing, so a
| figure looks the same whichever page it is read on.
|
| The old default tone was a grey that no longer has a soft surface to sit on;
| anything not given a tone now takes the theme's own colour.
--}}
<x-metric-card
    :label="$label"
    :amount="$amount"
    :icon="$icon"
    :tone="$tone === 'muted-foreground' ? 'primary' : $tone"
    :change="$change"
    :compared-to="$comparedTo"
    :higher-is-better="$higherIsBetter"
    :hint="$hint"
    :attributes="$attributes"
>
    {{ $slot }}
</x-metric-card>
