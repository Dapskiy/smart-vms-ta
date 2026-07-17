<?php

namespace App\Console\Commands;

use App\Models\Visitor;
use App\Models\Pic;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MigrateFacePhotos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vms:migrate-face-photos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate base64 face photos from database columns to secure private storage (File-Level Encryption)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Face Photos Migration...');

        // 1. Migrate Visitors
        $visitors = Visitor::all();
        $visitorCount = 0;
        
        foreach ($visitors as $visitor) {
            $raw = $visitor->getRawOriginal('face_photo');
            if (empty($raw)) continue;

            // Decrypt raw database value
            $current = $raw;
            for ($i = 0; $i < 3; $i++) {
                try {
                    $current = Crypt::decryptString($current);
                } catch (\Throwable $e) {
                    break;
                }
            }

            $decoded = json_decode($current, true);
            if (!is_array($decoded)) {
                $decoded = [$current];
            }

            $hasBase64 = false;
            $updatedPaths = [];

            foreach ($decoded as $item) {
                if (empty($item)) continue;

                if (is_string($item) && str_starts_with($item, 'data:image')) {
                    $hasBase64 = true;
                    try {
                        $parts = explode(',', $item);
                        $base64Data = isset($parts[1]) ? $parts[1] : $parts[0];
                        $binaryData = base64_decode($base64Data);

                        $encryptedData = Crypt::encrypt($binaryData);
                        $filename = 'face-photos/' . Str::uuid() . '.enc';
                        Storage::disk('local')->put($filename, $encryptedData);

                        $updatedPaths[] = $filename;
                    } catch (\Throwable $e) {
                        $this->error("Failed to migrate photo for Visitor ID {$visitor->id}: " . $e->getMessage());
                        $updatedPaths[] = $item;
                    }
                } else {
                    $updatedPaths[] = $item;
                }
            }

            if ($hasBase64) {
                $visitor->updateQuietly([
                    'face_photo' => $updatedPaths
                ]);
                $visitorCount++;
            }
        }

        $this->info("Successfully migrated {$visitorCount} Visitor records.");

        // 2. Migrate PICs
        $pics = Pic::all();
        $picCount = 0;

        foreach ($pics as $pic) {
            $raw = $pic->getRawOriginal('face_photo');
            if (empty($raw)) continue;

            $current = $raw;
            for ($i = 0; $i < 3; $i++) {
                try {
                    $current = Crypt::decryptString($current);
                } catch (\Throwable $e) {
                    break;
                }
            }

            $decoded = json_decode($current, true);
            if (!is_array($decoded)) {
                $decoded = [$current];
            }

            $hasBase64 = false;
            $updatedPaths = [];

            foreach ($decoded as $item) {
                if (empty($item)) continue;

                if (is_string($item) && str_starts_with($item, 'data:image')) {
                    $hasBase64 = true;
                    try {
                        $parts = explode(',', $item);
                        $base64Data = isset($parts[1]) ? $parts[1] : $parts[0];
                        $binaryData = base64_decode($base64Data);

                        $encryptedData = Crypt::encrypt($binaryData);
                        $filename = 'pic-photos/' . Str::uuid() . '.enc';
                        Storage::disk('local')->put($filename, $encryptedData);

                        $updatedPaths[] = $filename;
                    } catch (\Throwable $e) {
                        $this->error("Failed to migrate photo for PIC ID {$pic->id}: " . $e->getMessage());
                        $updatedPaths[] = $item;
                    }
                } else {
                    $updatedPaths[] = $item;
                }
            }

            if ($hasBase64) {
                $pic->updateQuietly([
                    'face_photo' => $updatedPaths
                ]);
                $picCount++;
            }
        }

        $this->info("Successfully migrated {$picCount} PIC records.");
        $this->info('Migration completed successfully!');
    }
}
