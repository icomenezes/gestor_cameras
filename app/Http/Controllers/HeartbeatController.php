<?php

namespace App\Http\Controllers;

use App\Services\AccessLogService;
use Illuminate\Http\Request;

class HeartbeatController extends Controller
{
    public function __invoke(Request $request, AccessLogService $log)
    {
        $log->heartbeat($request->user(), $request->integer('camera_id') ?: null);
        return response()->json(['ok' => true]);
    }
}
