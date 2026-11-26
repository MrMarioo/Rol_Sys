@if (is_array($value))
    <table>
        @foreach ($value as $key => $item)
            <tr>
                <th>{{ \Illuminate\Support\Str::headline((string) $key) }}</th>
                <td>@include('reports.pdf-value', ['value' => $item])</td>
            </tr>
        @endforeach
    </table>
@elseif (is_bool($value))
    {{ $value ? 'Yes' : 'No' }}
@elseif ($value === null)
    —
@else
    {{ $value }}
@endif
