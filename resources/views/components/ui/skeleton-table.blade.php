@props([
    'rows' => 6,
    'cols' => 6,
])

<div class="overflow-x-auto" aria-hidden="true">
    <table class="w-full text-sm">
        <thead class="bg-gray-50">
            <tr>
                @for($c = 0; $c < $cols; $c++)
                    <th class="px-4 py-3">
                        <div class="ux-skeleton h-4 w-20"></div>
                    </th>
                @endfor
            </tr>
        </thead>
        <tbody class="divide-y">
            @for($r = 0; $r < $rows; $r++)
                <tr>
                    @for($c = 0; $c < $cols; $c++)
                        <td class="px-4 py-3">
                            <div class="ux-skeleton h-4 {{ $c === 0 ? 'w-40' : 'w-24' }}"></div>
                        </td>
                    @endfor
                </tr>
            @endfor
        </tbody>
    </table>
</div>
