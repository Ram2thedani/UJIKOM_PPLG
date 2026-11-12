<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RubricItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RubricController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $major = strtoupper((string) $request->query('major', 'PPLG'));

        $items = RubricItem::where('major', $major)
            ->orderBy('page_number')
            ->orderBy('item_order')
            ->get();

        $pages = $items->groupBy('page_number')->map(function ($group, $pageNumber) {
            $num = (int) $pageNumber;
            $firstTitle = $group->first()?->page_title;

            return [
                'page_number' => $num,
                'title' => $firstTitle ?: "Halaman {$num}",
                'total_weight' => round((float) $group->sum('weight'), 2),
                'items' => $group->values(),
            ];
        })->values();

        return response()->json([
            'major' => $major,
            'items' => $items,
            'pages' => $pages,
            'total_weight' => round((float) $items->sum('weight'), 2),
        ]);
    }
}
