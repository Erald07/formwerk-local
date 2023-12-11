@props(['rows'])

@if ($rows->hasPages())
    <tfoot class="border-t-2">
        <tr>
            <td colspan="8">
                {{ $rows->links() }}
            </td>
        </tr>
    </tfoot>
@endif
