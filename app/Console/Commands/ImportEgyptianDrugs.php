<?php

namespace App\Console\Commands;

use App\Models\Medicine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportEgyptianDrugs extends Command
{
    protected $signature = 'medicines:import';

    protected $description = 'يستورد قاعدة بيانات الأدوية المصرية (مفتوحة المصدر) من GitHub لجدول medicines';

    private string $csvUrl = 'https://raw.githubusercontent.com/karem505/egyptian-drug-database/main/data/egyptian-drugs.csv';

    public function handle(): void
    {
        $this->info('بنحمّل ملف الأدوية...');

        $response = Http::timeout(60)->get($this->csvUrl);

        if (! $response->ok()) {
            $this->error('فشل تحميل الملف من GitHub.');

            return;
        }

        $lines = explode("\n", trim($response->body()));
        $header = str_getcsv(array_shift($lines));

        $this->info('عدد الأدوية في الملف: '.count($lines));

        $bar = $this->output->createProgressBar(count($lines));
        $chunk = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $row = str_getcsv($line);
            $data = array_combine($header, $row);

            $chunk[] = [
                'commercial_name_en' => $data['commercial_name_en'] ?? '',
                'commercial_name_ar' => $data['commercial_name_ar'] ?? null,
                'scientific_name' => $data['scientific_name'] ?? null,
                'manufacturer' => $data['manufacturer'] ?? null,
                'drug_class' => $data['drug_class'] ?? null,
                'route' => $data['route'] ?? null,
                'price_egp' => is_numeric($data['price_egp'] ?? null) ? $data['price_egp'] : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // نحفظ كل 500 دواء مع بعض بدل ما نعمل insert لكل واحد لوحده - أسرع بكتير
            if (count($chunk) >= 500) {
                Medicine::insert($chunk);
                $chunk = [];
                $bar->advance(500);
            }
        }

        if (! empty($chunk)) {
            Medicine::insert($chunk);
            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->newLine();
        $this->info('تم استيراد الأدوية بنجاح - الإجمالي في قاعدة البيانات: '.Medicine::count());
    }
}
