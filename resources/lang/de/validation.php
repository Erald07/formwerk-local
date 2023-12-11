<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'Das :attribute muss akzeptiert werden.',
    'active_url' => ' :attribute ist keine gültige URL.',
    'after' => 'Das :attribute muss ein Datum nach :date sein.',
    'after_or_equal' => 'Das :attribute muss ein Datum nach oder gleich :date sein.',
    'alpha' => 'Das :attribute darf nur Buchstaben enthalten.',
    'alpha_dash' => 'Das :attribute darf nur Buchstaben, Zahlen, Bindestriche und Unterstriche enthalten.',
    'alpha_num' => 'Das :attribute darf nur Buchstaben und Zahlen enthalten.',
    'array' => 'Das :Attribut muss ein Feld sein.',
    'before' => 'Das :attribute muss ein Datum vor :date sein.',
    'before_or_equal' => 'Das :attribute muss ein Datum vor oder gleich :date sein.',
    'between' => [
        'numeric' => 'Das :attribute muss zwischen :min und :max liegen.',
        'file' => 'Das :attribute muss zwischen :min und :max Kilobytes liegen.',
        'string' => 'Das :attribute muss zwischen den Zeichen :min und :max liegen.',
        'array' => 'Das :attribute muss zwischen :min und :max liegen.',
    ],
    'boolean' => 'Das Feld :attribute muss wahr oder falsch sein.',
    'confirmed' => 'Das :attribute Bestätigung stimmt nicht überein.',
    'date' => 'Das :attribute ist kein gültiges Datum.',
    'date_equals' => 'Das :attribute muss ein Datum sein, das dem :Datum entspricht.',
    'date_format' => 'Das :attribute entspricht nicht dem Format :format.',
    'different' => 'Das :attribute und das :other müssen unterschiedlich sein.',
    'digits' => 'Das :Attribut muss :digits Ziffern beinhalten.',
    'digits_between' => 'Das :attribute muss zwischen den Ziffern :min und :max liegen.',
    'dimensions' => 'Das :attribute hat ungültige Bildabmessungen.',
    'distinct' => 'Das Feld :attribute hat einen doppelten Wert.',
    'email' => 'Das :attribute muss eine gültige E-Mail-Adresse sein..',
    'ends_with' => 'Das :attribute muss mit einer der folgenden Angaben enden: :values.',
    'exists' => 'Das ausgewählte :attribute ist ungültig.',
    'file' => 'Das :attribute muss eine Datei sein.',
    'filled' => 'Das Feld :attribute muss einen Wert haben.',
    'gt' => [
        'numeric' => 'Das :attribute muss größer sein als :value.',
        'file' => 'Das :attribute muss größer sein als :value Kilobytes.',
        'string' => 'Das :attribute muss größer sein als :value Zeichen.',
        'array' => 'Das :attribute muss mehr als :value haben.',
    ],
    'gte' => [
        'numeric' => 'Das :attribute muss größer oder gleich dem :value sein.',
        'file' => 'Das :attribute muss größer oder gleich :value Kilobytes sein.',
        'string' => 'Das :attribute muss größer oder gleich :value sein.',
        'array' => 'Das :attribute muss mindestens :value Elemente haben.',
    ],
    'image' => 'Das :attribute muss ein Bild sein.',
    'in' => 'Das ausgewählte :attribute ist ungültig.',
    'in_array' => 'Das Feld :attribute ist in :other nicht vorhanden.',
    'integer' => 'Das :attribute muss eine ganze Zahl sein.',
    'ip' => 'Das :attribute muss eine gültige IP-Adresse sein.',
    'ipv4' => 'Das :attribute muss eine gültige IPv4-Adresse sein.',
    'ipv6' => 'Das :attribute muss eine gültige IPv6-Adresse sein..',
    'json' => 'Das :attribute muss eine gültige JSON-Zeichenkette sein.',
    'lt' => [
        'numeric' => 'Das :attribute muss kleiner sein als :value.',
        'file' => 'Das :attribute muss kleiner sein als :value kilobytes.',
        'string' => 'Das :attribut muss kleiner sein als :value Zeichen.',
        'array' => 'Das :attribute muss weniger als :value Elemente haben.',
    ],
    'lte' => [
        'numeric' => 'Das :attribute muss kleiner oder gleich :value sein.',
        'file' => 'Das :attribute muss kleiner oder gleich dem :value Kilobytes sein.',
        'string' => 'Das :attribute muss kleiner oder gleich dem :value Kilobytes sein.',
        'array' => 'Das :attribute darf nicht mehr als :value Elemente haben.',
    ],
    'max' => [
        'numeric' => 'Das :attribute darf nicht größer sein als :max.',
        'file' => 'Das :attribute darf nicht größer sein als :max kilobytes.Das :attribute darf nicht größer sein als :max Zeichen.',
        'string' => 'Das :attribute darf nicht größer sein als :max Zeichen.',
        'array' => 'Das :attribute darf nicht mehr als :max Elemente haben.',
    ],
    'mimes' => 'Das :attribute muss eine Datei vom Typ: :values sein.',
    'mimetypes' => 'Das :attribute muss eine Datei vom Typ: :values sein.',
    'min' => [
        'numeric' => 'Das :attribute muss mindestens :min sein.',
        'file' => 'Das :attribute muss mindestens :min Kilobytes betragen.',
        'string' => 'Das :attribute muss mindestens :min Zeichen sein.',
        'array' => 'Das :attribute muss mindestens :min Elemente haben.',
    ],
    'multiple_of' => 'Das :attribute muss ein Vielfaches von :value sein.',
    'not_in' => 'Das ausgewählte :attribute ist ungültig.',
    'not_regex' => 'Das Format :attribute ist ungültig.',
    'numeric' => 'Das :attribute muss eine Zahl sein.',
    'password' => 'Das Passwort ist falsch.',
    'present' => 'Das Feld :attribute muss vorhanden sein.',
    'regex' => 'Das Format :attribute ist ungültig.',
    'required' => 'Das Feld :attribute ist erforderlich.',
    'required_if' => 'Das Feld :attribute ist erforderlich, wenn :other :value ist.',
    'required_unless' => 'Das Feld :attribute ist erforderlich, es sei denn, :other steht in :values.',
    'required_with' => 'Das Feld :attribute ist erforderlich, wenn :values vorhanden ist.',
    'required_with_all' => 'Das Feld :attribute ist erforderlich, wenn :values vorhanden sind.',
    'required_without' => 'Das Feld :attribute ist erforderlich, wenn :values nicht vorhanden ist.',
    'required_without_all' => 'Das Feld :attribute ist erforderlich, wenn keines der :values vorhanden ist.',
    'prohibited' => 'Das Feld :attribute ist nicht zulässig.',
    'prohibited_if' => 'Das Feld :attribute ist verboten, wenn :other :value ist.',
    'prohibited_unless' => 'Das Feld :attribute ist verboten, wenn :other nicht in :values enthalten ist.',
    'same' => 'Die Angaben :attribute und :other müssen übereinstimmen.',
    'size' => [
        'numeric' => 'Das :attribute muss :size sein.',
        'file' => 'Das :attribute muss :size kilobytes lauten.',
        'string' => 'Das :attribute muss :size Zeichen sein.',
        'array' => 'Das :attribute muss die Elemente :size enthalten.',
    ],
    'starts_with' => 'Das :attribute muss mit einer der folgenden Angaben beginnen: :values.',
    'string' => 'Das :attribute muss eine Zeichenkette sein',
    'timezone' => 'Das :attribute muss ein gültiges Feld sein.',
    'unique' => 'Das :attribute ist bereits vergeben.',
    'uploaded' => 'Das :attribute wurde nicht hochgeladen.',
    'url' => 'Das Format :attribute ist ungültig.',
    'uuid' => 'Das :attribute muss eine gültige UUID sein..',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'benutzerdefinierte Nachricht',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],
    'matrix' => [
        'select_each_row' =>  'Es muss mindestens ein Wert pro Zeile ausgewählt werden.'
    ],
    'Whoops! Something went wrong.' => 'Whoops! Something went wrong.',
    'access-denied'=> 'Zugriff verweigert'
];
