<?php

namespace App\Jobs;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Mail;
use Log;
use App\Models\User;

class SendForgotPasswordEmail extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    protected $user;
    protected $reset_token;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user, $reset_token)
    {
        $this->user        = $user;
        $this->reset_token = $reset_token;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            Mail::send('emails.forgotpassword', [
                'user' => $this->user,
                'reset_token' => $this->reset_token,
            ], function ($message) {
                $message->to($this->user->email);
                $message->subject('Let’s Get You Signed In!');
            });
        } catch (\Exception $e) {
            Log::Error('Unable to send forgot password email: '.$e->getMessage());
        }
    }
}
