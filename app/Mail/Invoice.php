<?php namespace App\Mail;

use Illuminate\Mail\Mailable;

class Invoice extends Mailable
{

    /**
     * Create a new message instance.
     *
     * @param string $to_address the address to send the email
     * @param float $winnings   the winnings they won
     * 
     * @return void
     */
    
    public function __construct($to_address, $transdata, $orderdata, $nama, $alamat, $kokec, $email, $instruksion,$hp,$code,$text)
    {
        $this->to_address= $to_address;
        $this->transdata = $transdata;
        $this->orderdata = $orderdata;
        $this->nama      = $nama;
        $this->alamat    = $alamat;
        $this->kokec     = $kokec;
        $this->email     = $email;
        $this->hp        = $hp;
        $this->instruksion = $instruksion;
        $this->code      = $code;
        $this->text      = $text;
        
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
            ->subject('Invoice Pembayaran')
            ->view('emails.invoice')
            ->with(
                [
                    'transdata'     => $this->transdata,
                    'orderdata'     => $this->orderdata,
                    'nama'          => $this->nama,
                    'alamat'        => $this->alamat,
                    'kokec'         => $this->kokec,
                    'email'         => $this->email,
                    'hp'            => $this->hp,
                    'instruksion'   => $this->instruksion,
                    'code'          => $this->$code,
                    'text'          => $this->$text
                ]
            );
    }
}
