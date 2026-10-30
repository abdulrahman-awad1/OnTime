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
        ini_set('memory_limit', '1024M');
        set_time_limit(0);

        $localCsvPath = storage_path('app/medicines.csv');

        if (!File::exists($localCsvPath)) {
            $this->error("ملف medicines.csv غير موجود في المسار: {$localCsvPath}");
            return self::FAILURE;
        }

        $this->info('جاري قراءة ملف الـ CSV...');

        DB::disableQueryLog();
        $now = now();
        $chunk = [];
        $totalRowsRead = 0;
        $processedNames = [];

        if (($handle = fopen($localCsvPath, 'r')) !== false) {
            // تخطي السطر الأول (Header: Drugname, Price, Search Query, Date, ...)
            $header = fgetcsv($handle);

            while (($row = fgetcsv($handle)) !== false) {
                $totalRowsRead++;

                // حسب صورة ملفك: العمود 0 هو Drugname، العمود 1 هو Price، العمود 6 هو Form، العمود 9 هو Category
                $rawName = trim($row[0] ?? '');

                if ($rawName === '') {
                    continue;
                }

                $price      = (float) ($row[1] ?? 0);
                $form       = trim($row[6] ?? '');
                $scientific = trim($row[9] ?? '');

                // دمج الشكل الدوائي (Tablet/Capsule) مع الاسم لعدم ضياع التركيزات المختلفة لنفس الدواء
                $nameEn = ($form !== '' && !str_contains(strtolower($rawName), strtolower($form)))
                    ? "{$rawName} ({$form})"
                    : $rawName;

                $processedNames[$nameEn] = true;

                $chunk[$nameEn] = [
                    'commercial_name_en' => $nameEn,
                    'commercial_name_ar' => $nameEn,
                    'scientific_name'    => $scientific,
                    'price_egp'          => $price,
                    'is_active'          => true,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ];

                // إدخال كل 1000 دواء فريد معاً
                if (count($chunk) >= 1000) {
                    Medicine::upsert(
                        array_values($chunk),
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
                array_values($chunk),
                ['commercial_name_en'],
                ['scientific_name', 'price_egp', 'is_active', 'updated_at']
            );
        }

        $this->info("إجمالي السطور المقروءة من ملف CSV: {$totalRowsRead}");
        $this->info("إجمالي الأدوية الفريدة المستخرجة من الملف: " . count($processedNames));
        $this->info("إجمالي الأدوية في قاعدة البيانات الآن: " . Medicine::count());

        return self::SUCCESS;
    }
}
