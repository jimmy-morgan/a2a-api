<?php

namespace App\Jobs;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Mail;
use Log;
use App\Models\User;

class SendPasswordResetEmail extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    protected $user;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            Mail::send('emails.passwordreset', ['user' => $this->user], function ($message) {
                $message->to($this->user->email);
                $message->subject('Password Updated');
            });
        } catch (\Exception $e) {
            Log::Error('Unable to send password reset email: '.$e->getMessage());
        }
    }
}
