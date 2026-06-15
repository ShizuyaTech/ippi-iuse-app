<?php

namespace App\Http\Controllers\MM;

use App\Http\Controllers\Controller;
use App\Models\LabelCheck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LabelCheckController extends Controller
{
    public function index(Request $request)
    {
        $query = LabelCheck::with('checkedBy')->latest();

        if ($request->result) {
            $query->where('result', $request->result);
        }
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('part_label',     'like', "%{$request->search}%")
                  ->orWhere('customer_label', 'like', "%{$request->search}%")
                  ->orWhere('reference_doc',  'like', "%{$request->search}%");
            });
        }
        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $checks = $query->paginate(30)->withQueryString();

        return view('mm.label-checks.index', compact('checks'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'part_label'      => 'required|string|max:500',
            'customer_label'  => 'required|string|max:500',
            'reference_doc'   => 'nullable|string|max:100',
            'notes'           => 'nullable|string|max:255',
        ]);

        $partLabel     = trim(strtoupper($request->part_label));
        $customerLabel = trim(strtoupper($request->customer_label));
        $result        = ($partLabel === $customerLabel) ? 'ok' : 'ng';

        $check = LabelCheck::create([
            'part_label'     => $partLabel,
            'customer_label' => $customerLabel,
            'result'         => $result,
            'reference_doc'  => $request->reference_doc ?: null,
            'notes'          => $request->notes ?: null,
            'checked_by'     => Auth::id(),
        ]);

        return response()->json([
            'result'     => $result,
            'id'         => $check->id,
            'checked_at' => $check->created_at->format('d/m/Y H:i:s'),
            'checked_by' => Auth::user()->name,
        ]);
    }

    public function destroy(LabelCheck $labelCheck)
    {
        $labelCheck->delete();
        return back()->with('success', 'Record berhasil dihapus.');
    }
}
