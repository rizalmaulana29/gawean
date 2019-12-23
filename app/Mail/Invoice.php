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
    
    public function __construct($to_address, $transdata, $orderdata, $nama, $alamat, $kokec, $email, $parent_id,$hp,$number,$title)
    {
        $this->to_address  = $to_address;
        $this->transdata   = $transdata;
        $this->orderdata   = $orderdata;
        $this->nama        = $nama;
        $this->alamat      = $alamat;
        $this->kokec       = $kokec;
        $this->email       = $email;
        $this->hp          = $hp;
        $this->parent_id   = $parent_id;
        $this->number      = $number;
        $this->title       = $title;
        
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
                    'parent_id'     => $this->parent_id,
                    'number'        => $this->number,
                    'title'         => $this->title
                ]
            );
    }
}
