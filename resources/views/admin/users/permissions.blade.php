<x-app-layout>
    <x-slot name="title">Permission User: {{ $user->name }}</x-slot>
    <div class="bg-white rounded-lg shadow p-6 max-w-4xl">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('admin.users.index') }}" class="text-blue-600 hover:underline text-sm">← Kembali</a>
            <h2 class="text-lg font-semibold text-gray-700">
                Permission Individual: <span class="text-blue-700">{{ $user->name }}</span>
            </h2>
        </div>

        @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded text-sm">{{ session('success') }}</div>
        @endif

        {{-- Info role --}}
        <div class="mb-5 bg-blue-50 border border-blue-200 rounded px-4 py-3 text-sm text-blue-800">
            <span class="font-medium">Role saat ini:</span>
            {{ $user->roleModel?->display_name ?? '– Tanpa Role –' }}
            <span class="text-blue-600 ml-2 text-xs">
                Permission yang dicentang di bawah adalah tambahan di luar hak akses role.
            </span>
        </div>

        <form method="POST" action="{{ route('admin.users.permissions.update', $user) }}">
            @csrf @method('PUT')

            @php
            $featureLabels = [
                'materials'           => 'Material Master',
                'vendors'             => 'Vendor',
                'customers'           => 'Customer',
                'storage_locations'   => 'Storage Location',
                'purchase_orders'     => 'Purchase Order',
                'goods_receipts'      => 'Goods Receipt',
                'goods_issues'        => 'Goods Issue',
                'stocks'              => 'Stock',
                'skm'                 => 'Summary Kanban (SKM)',
                'delivery_notes'      => 'Delivery Note',
                'vendor_deliveries'   => 'Vendor Delivery',
                'business_event_logs' => 'Business Event Log',
                'work_centers'        => 'Work Center',
                'boms'                => 'BOM (Bill of Material)',
                'routings'            => 'Routing',
                'production_orders'   => 'Production Order',
                'mrp'                 => 'MRP',
            ];
            @endphp

            <div class="space-y-4">
                @foreach($permissions as $module => $features)
                <div class="border rounded-lg overflow-hidden">
                    {{-- Module Header --}}
                    <div class="bg-blue-700 text-white px-4 py-2 flex items-center justify-between">
                        <span class="text-sm font-bold uppercase tracking-wider">Modul {{ $module }}</span>
                        <button type="button" onclick="toggleModule('{{ Str::slug($module) }}')"
                            class="text-xs bg-white/20 hover:bg-white/30 px-2 py-0.5 rounded">
                            Pilih Semua
                        </button>
                    </div>
                    {{-- Feature Rows --}}
                    <div class="divide-y divide-gray-100">
                        @foreach($features as $feature => $perms)
                        @php $label = $featureLabels[$feature] ?? Str::title(str_replace('_', ' ', $feature)); @endphp
                        <div class="px-4 py-2 hover:bg-gray-50">
                            <div class="flex items-center gap-2 mb-1">
                                <button type="button" onclick="toggleFeature('{{ Str::slug($module) }}-{{ Str::slug($feature) }}')"
                                    class="text-xs bg-gray-200 hover:bg-gray-300 text-gray-600 px-1.5 py-0.5 rounded shrink-0">
                                    Pilih
                                </button>
                                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ $label }}</span>
                            </div>
                            <div class="flex flex-wrap gap-x-5 gap-y-0.5 pl-1">
                                @foreach($perms as $perm)
                                @php
                                    $fromRole = in_array($perm->id, $rolePerms);
                                    $fromUser = in_array($perm->id, old('permissions', $userPerms));
                                @endphp
                                @if($fromRole)
                                <label class="flex items-center gap-1.5 text-sm text-gray-400 cursor-default"
                                    title="Dari role: {{ $user->roleModel?->display_name }}">
                                    <input type="checkbox" disabled checked class="opacity-40">
                                    {{ $perm->display_name }}
                                    <span class="text-xs bg-gray-200 text-gray-400 px-1 rounded leading-tight">role</span>
                                </label>
                                @else
                                <label class="flex items-center gap-1.5 text-sm cursor-pointer hover:text-blue-700">
                                    <input type="checkbox" name="permissions[]" value="{{ $perm->id }}"
                                        class="module-{{ Str::slug($module) }} feat-{{ Str::slug($module) }}-{{ Str::slug($feature) }}"
                                        {{ $fromUser ? 'checked' : '' }}>
                                    {{ $perm->display_name }}
                                </label>
                                @endif
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>

            <div class="mt-6 flex gap-3">
                <button type="submit" class="bg-blue-700 text-white px-5 py-2 rounded hover:bg-blue-800 text-sm">Simpan Permission</button>
                <a href="{{ route('admin.users.index') }}" class="bg-gray-100 text-gray-600 px-5 py-2 rounded border hover:bg-gray-200 text-sm">Batal</a>
            </div>
        </form>
    </div>

    <script>
        function toggleModule(moduleSlug) {
            const boxes = document.querySelectorAll('.module-' + moduleSlug);
            const allChecked = [...boxes].every(b => b.checked);
            boxes.forEach(b => b.checked = !allChecked);
        }
        function toggleFeature(featSlug) {
            const boxes = document.querySelectorAll('.feat-' + featSlug);
            const allChecked = [...boxes].every(b => b.checked);
            boxes.forEach(b => b.checked = !allChecked);
        }
    </script>
</x-app-layout>
