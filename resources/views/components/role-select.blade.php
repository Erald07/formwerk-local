@props([
    "roles" => [],
    "value" => "",
])

<select id="role" name="role" class="w-full" {{ $attributes }}>
    @foreach ($roles as $role)
        <option value="{{ $role["name"] }}" @if ($role["name"] == $value) selected @endif>
            {{ __($role["display_name"]) }}
        </option>
    @endforeach
</select>
