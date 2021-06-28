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
    
    public function __construct($to_address, $transdata, $orderdata, $nama, $alamat, $email, $parent_id,$hp,$number,$title,$varian)
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
        $this->title       = $title;
        $this->varian      = $varian;
        
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
            ->subject('Invoice Pembayaran')
            ->view('emails.invoice')
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
                    'title'         => $this->title,
                    'varian'        => $this->varian
                ]
            );
    }
}
