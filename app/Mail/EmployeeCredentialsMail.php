<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmployeeCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public $employeeId;
    public $password;

    public function __construct($employeeId, $password)
    {
        $this->employeeId = $employeeId;
        $this->password = $password;
    }

    public function build()
    {
        return $this->subject('Your Employee Account Credentials')
            ->view('emails.employee_credentials')
            ->with([
                'employeeId' => $this->employeeId,
                'password' => $this->password,
            ]);
    }
}
