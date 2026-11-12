<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RubricItem;
use Illuminate\Http\JsonResponse;

class RubricController extends Controller
{
    public const PAGE_TITLES = [
        1 => 'Analisis, Desain, & Arsitektur Database',
        2 => 'Implementasi Koding, Fitur, & Standar',
        3 => 'Pengujian, Debugging, & Dokumentasi',
    ];

    public function index(): JsonResponse
    {
        $items = RubricItem::orderBy('item_order')->get();

        $pages = $items->groupBy('page_number')->map(function ($group, $pageNumber) {
            $num = (int) $pageNumber;

            return [
                'page_number' => $num,
                'title' => self::PAGE_TITLES[$num] ?? "Halaman {$num}",
                'total_weight' => round((float) $group->sum('weight'), 2),
                'items' => $group->values(),
            ];
        })->values();

        return response()->json([
            'items' => $items,
            'pages' => $pages,
            'total_weight' => round((float) $items->sum('weight'), 2),
        ]);
    }
}
