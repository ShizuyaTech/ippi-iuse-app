<x-app-layout>
    <x-slot name="title">Business Event Logs</x-slot>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-700">Business Event Logs</h2>
            <a href="{{ route('mm.business-event-logs.export', request()->query()) }}"
               class="bg-emerald-600 text-white px-4 py-2 rounded text-sm hover:bg-emerald-700">
                Export Excel
            </a>
        </div>

        <form method="GET" class="flex flex-wrap gap-2 mb-4">
            <input type="text" name="event_type" value="{{ request('event_type') }}" placeholder="event_type..."
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <input type="text" name="entity_type" value="{{ request('entity_type') }}" placeholder="entity_type..."
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <input type="number" name="entity_id" value="{{ request('entity_id') }}" placeholder="entity_id"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-32">
            <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded text-sm">Filter</button>
            <a href="{{ route('mm.business-event-logs.index') }}" class="bg-gray-100 text-gray-600 px-4 py-2 rounded text-sm border">Reset</a>
        </form>

        <div class="mobile-cards overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead class="bg-blue-800 text-white">
                    <tr>
                        <th class="px-4 py-2 text-left">Waktu</th>
                        <th class="px-4 py-2 text-left">Event</th>
                        <th class="px-4 py-2 text-left">Entity</th>
                        <th class="px-4 py-2 text-left">Entity ID</th>
                        <th class="px-4 py-2 text-left">User</th>
                        <th class="px-4 py-2 text-left">Payload</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr class="border-b align-top hover:bg-gray-50">
                        <td class="px-4 py-2 whitespace-nowrap" data-label="Waktu">{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                        <td class="px-4 py-2 font-mono text-xs text-blue-700" data-label="Event">{{ $log->event_type }}</td>
                        <td class="px-4 py-2 font-mono text-xs" data-label="Entity">{{ $log->entity_type }}</td>
                        <td class="px-4 py-2" data-label="Entity ID">{{ $log->entity_id ?? '-' }}</td>
                        <td class="px-4 py-2" data-label="User">{{ $log->user?->name ?? '-' }}</td>
                        <td class="px-4 py-2" data-label="Payload">
                            <pre class="text-[11px] whitespace-pre-wrap break-all text-gray-600">{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-4 text-center text-gray-400">Belum ada event log.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $logs->links() }}</div>
    </div>
</x-app-layout>
