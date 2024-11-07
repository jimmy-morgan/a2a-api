<?php

namespace App\Jobs;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Twilio;
use Log;
use App\Models\User;

class SendVerificationSMS extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    protected $user;
    protected $twilio;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->twilio = new Twilio;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $message = 'Vacation Rental Technologies SMS Access Code: '.$this->user->sms_verify_token;
            Twilio::message($this->user->cell_phone, $message);
        } catch (\Exception $e) {
            Log::Error('Unable to send sms: '.$e->getMessage());
        }
    }
}
