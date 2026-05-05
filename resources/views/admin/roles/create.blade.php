<x-app-layout>
    <x-slot name="title">Buat Role Baru</x-slot>
    <div class="bg-white rounded-lg shadow p-6 max-w-4xl">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('admin.roles.index') }}" class="text-blue-600 hover:underline text-sm">← Kembali</a>
            <h2 class="text-lg font-semibold text-gray-700">Buat Role Baru</h2>
        </div>

        @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-2 rounded text-sm">
                <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.roles.store') }}">
            @csrf
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Slug <span class="text-red-500">*</span>
                        <span class="text-xs text-gray-400 font-normal">(huruf kecil, angka, underscore)</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}"
                        placeholder="misal: spv, manager, qa_team"
                        class="w-full border rounded px-3 py-2 text-sm @error('name') border-red-400 @enderror">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Tampilan <span class="text-red-500">*</span></label>
                    <input type="text" name="display_name" value="{{ old('display_name') }}"
                        placeholder="misal: Supervisor, Manager, Tim QA"
                        class="w-full border rounded px-3 py-2 text-sm @error('display_name') border-red-400 @enderror">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                    <input type="text" name="description" value="{{ old('description') }}"
                        class="w-full border rounded px-3 py-2 text-sm">
                </div>
            </div>

            {{-- Permission Assignment --}}
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
                <h3 class="font-medium text-gray-700">Assign Permission ke Role ini</h3>
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
                                <label class="flex items-center gap-1.5 text-sm cursor-pointer hover:text-blue-700">
                                    <input type="checkbox" name="permissions[]" value="{{ $perm->id }}"
                                        class="module-{{ Str::slug($module) }} feat-{{ Str::slug($module) }}-{{ Str::slug($feature) }}"
                                        {{ in_array($perm->id, old('permissions', [])) ? 'checked' : '' }}>
                                    {{ $perm->display_name }}
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>

            <div class="mt-6 flex gap-3">
                <button type="submit" class="bg-blue-700 text-white px-5 py-2 rounded hover:bg-blue-800 text-sm">Simpan Role</button>
                <a href="{{ route('admin.roles.index') }}" class="bg-gray-100 text-gray-600 px-5 py-2 rounded border hover:bg-gray-200 text-sm">Batal</a>
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
