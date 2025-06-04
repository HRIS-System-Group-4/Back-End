<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendEmployeeCredentialMail extends Mailable
{
    public $employee_id;
    public $password;
    public $email;
    public $company_name;
    public $avatar_url;  // untuk avatar

    public function __construct($employee_id, $password, $email, $company_name, $avatar_url = null)
    {
        $this->employee_id = $employee_id;
        $this->password = $password;
        $this->email = $email;
        $this->company_name = $company_name;
        $this->avatar_url = $avatar_url;
    }

    public function build()
    {
        return $this->view('emails.employee_credentials')
            ->with([
                'employee_id'  => $this->employee_id,
                'password'     => $this->password,
                'email'        => $this->email,
                'company_name' => $this->company_name,
                'avatar_url'   => $this->avatar_url,
            ]);
    }
}
