{{-- One dynamic spec input (category_attributes row $attr), named attributes[key] as the API expects. $value = current/old value. --}}
@php
    $name = "attributes[{$attr->key}]";
    $value = old("attributes.{$attr->key}", $value ?? null);
    $required = $attr->is_required && ($enforceRequired ?? true);
@endphp
<label for="attr-{{ $attr->key }}">{{ $attr->localized('label') }} @if ($required)<span style="color:var(--danger)">*</span>@endif</label>
@switch($attr->type)
    @case('select')
        <select id="attr-{{ $attr->key }}" name="{{ $name }}" @required($required)>
            <option value="">{{ __('— اختر —') }}</option>
            @foreach ($attr->options ?? [] as $option)
                <option value="{{ $option }}" @selected((string) $value === (string) $option)>{{ $option }}</option>
            @endforeach
        </select>
        @break
    @case('multiselect')
        <div class="row" id="attr-{{ $attr->key }}">
            @foreach ($attr->options ?? [] as $option)
                <label class="inline"><input type="checkbox" name="{{ $name }}[]" value="{{ $option }}" @checked(in_array($option, (array) $value, true))> {{ $option }}</label>
            @endforeach
        </div>
        @break
    @case('boolean')
        <select id="attr-{{ $attr->key }}" name="{{ $name }}" @required($required)>
            <option value="">{{ __('— اختر —') }}</option>
            <option value="1" @selected((string) $value === '1')>{{ __('نعم') }}</option>
            <option value="0" @selected((string) $value === '0')>{{ __('لا') }}</option>
        </select>
        @break
    @case('number')
        <input type="number" step="any" id="attr-{{ $attr->key }}" name="{{ $name }}" value="{{ $value }}" @required($required)>
        @break
    @default
        <input type="text" id="attr-{{ $attr->key }}" name="{{ $name }}" value="{{ $value }}" @required($required)>
@endswitch
