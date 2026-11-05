<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Governorate;

/**
 * See docs/API_CONTRACT.md § Locations.
 * Read-only list of governorates + cities for location pickers and the
 * governorate_id / city_id filters on GET /ads.
 */
class GovernorateController extends Controller
{
    // GET /api/governorates
    public function index()
    {
        $governorates = Governorate::query()
            ->where('is_active', true)
            ->with(['cities' => fn ($q) => $q->where('is_active', true)->orderBy('id')->select('id', 'governorate_id', 'name_ar', 'name_en')])
            ->orderBy('id')
            ->get(['id', 'name_ar', 'name_en', 'latitude', 'longitude']);

        return response()->json(['data' => $governorates]);
    }
}
