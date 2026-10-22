{{--
  Multi-step form indicator (the numbered dots from the prototype's Add Product form).

    <x-ui.stepper :steps="['Details', 'Batch & Pricing']" :current="$step" />
--}}
@props(['steps', 'current' => 1])
<div {{ $attributes->merge(['class' => 'flex items-center']) }} role="list" aria-label="Form steps">
    @foreach ($steps as $i => $label)
        @php $n = $i + 1; $state = $n < $current ? 'done' : ($n === $current ? 'active' : 'upcoming'); @endphp
        <div role="listitem" class="flex flex-col items-center gap-1 text-center">
            <div class="step-dot {{ $state }}" @if ($n === $current) aria-current="step" @endif>
                @if ($state === 'done') ✓ @else {{ $n }} @endif
            </div>
            <span class="text-[10px] {{ $n === $current ? 'font-semibold text-brand-600' : 'font-medium text-gray-400' }}">{{ $label }}</span>
        </div>
        @unless ($loop->last)
            <div class="step-line {{ $n < $current ? 'done' : '' }}" aria-hidden="true"></div>
        @endunless
    @endforeach
</div>
