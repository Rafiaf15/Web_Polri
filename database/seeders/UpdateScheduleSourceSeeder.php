<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Schedule;

class UpdateScheduleSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update semua jadwal existing menjadi manual
        Schedule::whereNull('source')->update([
            'source' => 'manual',
            'pdf_filename' => null
        ]);
        
        $this->command->info('Schedule sources updated successfully!');
    }
}
