<?php

namespace App\Console\Commands;

use App\Models\Medicine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SyncMedicinesCommand extends Command
{
    protected $signature = 'medicines:sync';
    protected $description = 'استيراد ومزامنة الأدوية من ملف CSV بكفاءة عالية';

    public function handle(): int
    {
        // زيادة الذاكرة والوقت مؤقتًا للعمليات الكبيرة
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $this->info('بدء عملية مزامنة قاعدة بيانات الأدوية من ملف CSV...');

        $localCsvPath = storage_path('app/medicines.csv');

        if (!File::exists($localCsvPath)) {
            $this->error("ملف medicines.csv غير موجود في المسار: {$localCsvPath}");
            return self::FAILURE;
        }

        DB::disableQueryLog();
        $now = now();
        $activeNames = [];
        $chunk = [];

        $this->info('جاري قراءة الملف وتحديث البيانات...');

        if (($handle = fopen($localCsvPath, 'r')) !== FALSE) {
            // قراءة سطر الهيدر (أسماء الأعمدة)
            $header = fgetcsv($handle);

            if (!$header) {
                $this->error('ملف الـ CSV فارغ أو تالف.');
                fclose($handle);
                return self::FAILURE;
            }

            // تنظيف أسماء الأعمدة من أي رموز مخفية (مثل BOM)
            $header[0] = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $header[0]);

            while (($row = fgetcsv($handle)) !== FALSE) {
                // ربط السطر بأسماء الأعمدة
                if (count($header) !== count($row)) {
                    continue;
                }

                $item = array_combine($header, $row);

                $rawName = trim($item['Drugname'] ?? $item['commercial_name_en'] ?? '');
                $form    = trim($item['Form'] ?? '');

                if ($rawName === '') {
                    continue;
                }

                $nameEn = $form !== '' && !str_contains(strtolower($rawName), strtolower($form))
                    ? "{$rawName} ({$form})"
                    : $rawName;

                $price      = (float) ($item['Price'] ?? 0);
                $scientific = trim($item['Category'] ?? $item['scientific_name'] ?? '');

                $activeNames[] = $nameEn;

                $chunk[] = [
                    'commercial_name_en' => $nameEn,
                    'commercial_name_ar' => $nameEn,
                    'scientific_name'    => $scientific,
                    'price_egp'          => $price,
                    'is_active'          => true,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ];

                // إدخال كل 1000 سجل معاً لتوفير الرامات
                if (count($chunk) >= 1000) {
                    Medicine::upsert(
                        $chunk,
                        ['commercial_name_en'],
                        ['scientific_name', 'price_egp', 'is_active', 'updated_at']
                    );
                    $chunk = [];
                }
            }

            fclose($handle);
        }

        // إدخال المتبقي
        if (!empty($chunk)) {
            Medicine::upsert(
                $chunk,
                ['commercial_name_en'],
                ['scientific_name', 'price_egp', 'is_active', 'updated_at']
            );
        }

        // إيقاف تفعيل الأدوية القديمة التي لم تعد موجودة في الملف
        if (count($activeNames) > 0) {
            Medicine::where('updated_at', '<', $now)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        $this->info('تمت المزامنة بنجاح! إجمالي الأدوية النشطة: ' . Medicine::where('is_active', true)->count());
        return self::SUCCESS;
    }
}
