<?php

namespace App\Jobs;

use App\Mail\SendWelcomeEmail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmailJob implements ShouldQueue
{
    use Queueable;

    public $creator;
    public $user;
    public $password;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, $password, User $creator)
    {
        $this->user = $user;
        $this->creator = $creator;
        $this->password = $password;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->user->email)
            ->send(new SendWelcomeEmail(
                user: $this->user,
                password: $this->password,
                creator: $this->creator
            ));
    }
}
