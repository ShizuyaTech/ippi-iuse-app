<x-app-layout>
    <x-slot name="title">Edit Routing</x-slot>
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Edit Routing: {{ $routing->routing_number }}</h2>
        <form method="POST" action="{{ route('pp.routings.update', $routing) }}" class="space-y-6">
            @csrf @method('PATCH')
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Material *</label>
                    <select name="material_id" class="w-full border rounded px-3 py-2 text-sm" required>
                        @foreach($materials as $m)
                        <option value="{{ $m->id }}" {{ old('material_id', $routing->material_id)==$m->id?'selected':'' }}>{{ $m->code }} - {{ $m->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
                    <input type="text" name="description" value="{{ old('description', $routing->description) }}" class="w-full border rounded px-3 py-2 text-sm">
                </div>
            </div>
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700 mr-2">Status:</label>
                <select name="status" class="border rounded px-3 py-2 text-sm">
                    <option value="active" {{ old('status', $routing->status)==='active'?'selected':'' }}>Aktif</option>
                    <option value="inactive" {{ old('status', $routing->status)==='inactive'?'selected':'' }}>Nonaktif</option>
                </select>
            </div>
            <div>
                <div class="flex justify-between items-center mb-2">
                    <h3 class="font-semibold text-gray-700">Operasi Routing</h3>
                    <button type="button" onclick="addOp()" class="bg-green-600 text-white px-3 py-1 rounded text-sm">+ Tambah</button>
                </div>
                <table class="w-full text-sm border-collapse">
                    <thead class="bg-gray-100"><tr>
                        <th class="px-3 py-2 text-left w-16">No. Op</th>
                        <th class="px-3 py-2 text-left">Nama Operasi</th>
                        <th class="px-3 py-2 text-left">Work Center</th>
                        <th class="px-3 py-2 text-right w-28">Std. Time (jam)</th>
                        <th class="px-3 py-2 w-12"></th>
                    </tr></thead>
                    <tbody id="ops-body"></tbody>
                </table>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="bg-blue-700 text-white px-6 py-2 rounded text-sm">Perbarui Routing</button>
                <a href="{{ route('pp.routings.show', $routing) }}" class="bg-gray-200 text-gray-700 px-6 py-2 rounded text-sm">Batal</a>
            </div>
        </form>
    </div>
    <script>
        @php
            $wcJson = $workCenters->map(fn($w) => ['id'=>$w->id,'code'=>$w->code,'name'=>$w->name]);
            $existingJson = $routing->operations->map(fn($o) => ['op_no'=>$o->operation_number,'name'=>$o->description,'wc'=>$o->work_center_id,'time'=>$o->standard_time]);
        @endphp
        const workCenters = @json($wcJson);
        const existing = @json($existingJson);
        let r = 0;
        function addOp(opNo=null, name='', wcId=null, time=1){
            const wcOpts = workCenters.map(w=>`<option value="${w.id}" ${wcId==w.id?'selected':''}>${w.code} - ${w.name}</option>`).join('');
            const tr = document.createElement('tr');
            tr.className='border-b';
            tr.innerHTML=`
                <td class="px-2 py-1"><input type="number" name="operations[${r}][operation_number]" value="${opNo ?? (r+1)*10}" class="w-full border rounded px-2 py-1 text-sm" required></td>
                <td class="px-2 py-1"><input type="text" name="operations[${r}][description]" value="${name}" class="w-full border rounded px-2 py-1 text-sm" required></td>
                <td class="px-2 py-1"><select name="operations[${r}][work_center_id]" class="w-full border rounded px-2 py-1 text-sm" required><option value="">-- Pilih --</option>${wcOpts}</select></td>
                <td class="px-2 py-1"><input type="number" name="operations[${r}][standard_time]" value="${time}" class="w-full border rounded px-2 py-1 text-sm text-right" min="0.001" step="0.001" required></td>
                <td class="px-2 py-1 text-center"><button type="button" onclick="this.closest('tr').remove()" class="text-red-500">&#10005;</button></td>
            `;
            document.getElementById('ops-body').appendChild(tr); r++;
        }
        existing.forEach(o=>addOp(o.op_no, o.name, o.wc, o.time));
        if(!existing.length) addOp();
    </script>
</x-app-layout>
