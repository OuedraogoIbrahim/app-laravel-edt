<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\CourseReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class DailyTask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:daily-task';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $users = User::all();

        Notification::send($users, new CourseReminder('course'));
        $this->info('Tache executée avec succès');
    }
}
