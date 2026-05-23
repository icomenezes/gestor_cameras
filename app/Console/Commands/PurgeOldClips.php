<?php

namespace App\Console\Commands;

use App\Models\Clip;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PurgeOldClips extends Command
{
    protected $signature   = 'clips:purge {--days=2 : Apagar clipes mais antigos que N dias}';
    protected $description = 'Remove clipes com mais de N dias (padrão: 2)';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = Carbon::now()->subDays($days);

        $clips = Clip::where('created_at', '<', $cutoff)->get();

        if ($clips->isEmpty()) {
            $this->info('Nenhum clipe para remover.');
            return 0;
        }

        $removed = 0;
        foreach ($clips as $clip) {
            if ($clip->file_path) {
                $path = storage_path('app/' . $clip->file_path);
                if (file_exists($path)) {
                    @unlink($path);
                }
            }
            $clip->delete();
            $removed++;
        }

        $msg = "clips:purge removeu {$removed} clipe(s) com mais de {$days} dia(s).";
        $this->info($msg);
        Log::info($msg);

        return 0;
    }
}
