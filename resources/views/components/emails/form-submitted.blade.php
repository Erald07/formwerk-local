<html>

<head>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        td,
        th {
            padding: 5px 10px;
        }

        div {
            width: 100%;
            padding: 10px;
        }

        span {
            padding: 0px 10px;
        }

    </style>
</head>

<body>
    @if ($isTableLayout)
        <table border="1">
            <thead>
                <tr>
                    <th>Id</th>
                    <th>Name</th>
                    {{-- <th>Type</th> --}}
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($elements as $element)
                    @if (array_key_exists($element['id'], $values) && $element['type'] !== 'file')
                        <tr>
                            <td>{{ $element['id'] }}</td>
                            <td>{!! $replaceWithPredefinedValues(isset($element['label']) ? $element['label'] : $element['name'], []) !!}</td>
                            {{-- <td>{{ $element['type'] }}</td> --}}
                            <td> {!! $renderValue($element, $values[$element['id']]) !!} </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    @else
        @foreach ($elements as $element)
            @if (array_key_exists($element['id'], $values) && $element['type'] !== 'file')
                <div>
                    {{-- <span>
                        <strong>Id: </strong> {{ $element['id'] }}
                    </span> --}}
                    <span style="width: 300px">
                        <strong>Name: </strong> {!! $replaceWithPredefinedValues(isset($element['label']) ? $element['label'] : $element['name'], []) !!}
                    </span>
                    {{-- <span>
                        <strong>Type: </strong> {{ $element['type'] }}
                    </span> --}}
                    <span>
                        <strong>Value: </strong>
                        <span>{!! $renderValue($element, $values[$element['id']]) !!}</span>
                    </span>
                </div>
            @endif
        @endforeach
    @endif
    @if (isset($hasFile) && $hasFile)
        <p>
            <b>
                Der Absender hat dem Formular Datei(en) hinzugefügt. Um den vollständigen Eintrag, inkl. der Dateien, einzusehen, klicken Sie auf den folgenden Link:
                <a target="_blank" href="{{ route('entries', ['record_id' => $recordId]) }}" class="font-bold">
                    {{ route('entries', ['record_id' => $recordId]) }}
                </a>
            </b>
        </p>
        <p>
            <b>
                Sie können die hinzugefügten Datei(en) auch direkt aufrufen:
            </b>
            <ul>
                @foreach ($files as $file)
                    <li>
                        <a target="_blank" href="{{route('download-attachment-from-email', ['hash' => \Crypt::encrypt($file->id)])}}">{{$file->filename_original}}</a>
                    </li>
                @endforeach
            </ul>
        </p>
    @endif
</body>

</html>
