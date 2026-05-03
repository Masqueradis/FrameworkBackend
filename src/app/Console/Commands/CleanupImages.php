<?php

namespace App\Console\Commands;

use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-images';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes images from MinIO that do not have a link in the database.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $disk = Storage::disk('minio');
        $directory = 'products';
        $filesInStorage = $disk->allFiles($directory);

        if (empty($filesInStorage)) {
            $this->info('There are no files in the database.');
            return self::SUCCESS;
        }

        $filesInDb = ProductImage::pluck('path')->toArray();
        $files = array_diff($filesInStorage, $filesInDb);

        if(empty($files)) {
            $this->info('All files are synced.');
            return self::SUCCESS;
        }

        $this->warn(sprintf("Found %d orphaned files. Deleting...", count($files)));

        $deletedCount = 0;
        foreach ($files as $file) {
            if($disk->delete($file)) {
                $this->line('Deleting ' . $file);
                $deletedCount++;
            }
        }

        $this->info('Deleted ' . $deletedCount . ' files.');

        return self::SUCCESS;
    }
}
