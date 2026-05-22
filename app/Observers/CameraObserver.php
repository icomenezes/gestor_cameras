<?php

namespace App\Observers;

use App\Models\Camera;
use App\Services\Go2rtcService;

class CameraObserver
{
    public function __construct(private Go2rtcService $go2rtc) {}

    public function saved(Camera $camera): void
    {
        $this->go2rtc->syncCamera($camera);
    }

    public function deleted(Camera $camera): void
    {
        $this->go2rtc->removeCamera($camera);
    }
}
