<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class AgenMail extends Mailable
{

    /**
     * Create a new message instance.
     *
     * @param string $to_address the address to send the email
     * @param float $winnings   the winnings they won
     * 
     * @return void
     */

    public function __construct($to_address, $nama, $password, $link_email_verify)
    {
        $this->to_address  = $to_address;
        $this->password = $password;
        // $this->transdata   = $transdata;
        // $this->orderdata   = $orderdata;
        $this->nama             = $nama;
        $this->link_email_verify = $link_email_verify;
        // $this->alamat      = $alamat;
        // $this->email       = $email;
        // $this->hp          = $hp;
        // $this->parent_id   = $parent_id;
        // $this->number      = $number;

    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            // ->from($address = 'noreply@domain.com', $name = 'Sender name')
            ->to($this->to_address)
            ->subject('Informasi Pendaftaran')
            ->view('emails.signup')
            ->with(
                [
                    // 'transdata'     => $this->transdata,
                    // 'orderdata'     => $this->orderdata,
                    'nama'          => $this->nama,
                    // 'alamat'        => $this->alamat,
                    'email'         => $this->to_address,
                    'password'      => $this->password,
                    'link_email_verify'  => $this->link_email_verify,
                    // 'parent_id'     => $this->parent_id,
                    // 'number'        => $this->number,
                ]
            );
    }
}
