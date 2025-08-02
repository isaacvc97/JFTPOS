<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\MedicationUnit;

class MedicationSearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->get('q', '');

        return MedicationUnit::where('medicine_name', 'like', "%$query%")
            ->orWhere('generic_name', 'like', "%$query%")
            ->orderBy('medicine_name')
            ->limit(20)
            ->get();
    }
}