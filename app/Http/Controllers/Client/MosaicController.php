<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\MosaicLayout;
use Illuminate\Http\Request;

class MosaicController extends Controller
{
    public function show()
    {
        $user    = auth()->user();
        $cameras = $user->activeCameras()->get();
        $layout  = MosaicLayout::firstOrNew(['user_id' => $user->id], [
            'grid'             => '2x2',
            'camera_ids'       => [],
            'rotation_seconds' => null,
        ]);

        return view('client.mosaic', compact('cameras', 'layout'));
    }

    public function save(Request $request)
    {
        $request->validate([
            'grid'             => ['required', 'in:2x2,3x3,1+5'],
            'camera_ids'       => ['nullable', 'array'],
            'camera_ids.*'     => ['integer'],
            'rotation_seconds' => ['nullable', 'integer', 'in:5,10,15,30,60'],
        ]);

        MosaicLayout::updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'grid'             => $request->grid,
                'camera_ids'       => $request->camera_ids ?? [],
                'rotation_seconds' => $request->rotation_seconds ?: null,
            ]
        );

        return response()->json(['ok' => true]);
    }
}
