<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class Go2rtcProxyController extends Controller
{
    private string $base;

    public function __construct()
    {
        $this->base = rtrim(config('cameras.go2rtc_url', 'http://localhost:1984'), '/');
    }

    public function webrtc(Request $request)
    {
        $src = $request->query('src');
        $sdp = $request->getContent();

        $response = Http::timeout(10)
            ->withHeaders(['Content-Type' => 'application/sdp'])
            ->withBody($sdp, 'application/sdp')
            ->post("{$this->base}/api/webrtc?src={$src}");

        return response($response->body(), $response->status())
            ->header('Content-Type', 'application/sdp');
    }
}
