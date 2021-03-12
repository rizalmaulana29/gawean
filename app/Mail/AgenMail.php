<?php namespace App\Mail;

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
    
    public function __construct($to_address, $transdata, $orderdata, $nama, $alamat, $email, $parent_id,$hp,$number,$title)
    {
        $this->to_address  = $to_address;
        $this->transdata   = $transdata;
        $this->orderdata   = $orderdata;
        $this->nama        = $nama;
        $this->alamat      = $alamat;
        $this->email       = $email;
        $this->hp          = $hp;
        $this->parent_id   = $parent_id;
        $this->number      = $number;
        
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->to($this->to_address)
            ->subject('Informasi Pendaftaran')
            ->view('emails.signup')
            ->with(
                [
                    'transdata'     => $this->transdata,
                    'orderdata'     => $this->orderdata,
                    'nama'          => $this->nama,
                    'alamat'        => $this->alamat,
                    'email'         => $this->email,
                    'hp'            => $this->hp,
                    'parent_id'     => $this->parent_id,
                    'number'        => $this->number,
                ]
            );
    }
}
