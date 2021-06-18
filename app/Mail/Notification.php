<?php namespace App\Mail;

use Illuminate\Mail\Mailable;

class Notification extends Mailable
{

    /**
     * Create a new message instance.
     *
     * @param string $to_address the address to send the email
     * @param float $winnings   the winnings they won
     * 
     * @return void
     */
    
    public function __construct($to_address, $payment, $orderdata, $nama, $alamat, $email, $parent_id,$hp,$title,$number,$varian)
    {
        $this->to_address  = $to_address;
        $this->payment   = $payment;
        $this->orderdata   = $orderdata;
        $this->nama        = $nama;
        $this->alamat      = $alamat;
        $this->email       = $email;
        $this->hp          = $hp;
        $this->parent_id   = $parent_id;
        $this->title       = $title;
        $this->number      = $number;
        $this->varian      = $varian;
        
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // dd(
        // $this->to_address , 
        // $this->payment   ,
        // $this->orderdata   ,
        // $this->nama        ,
        // $this->alamat      ,
        // $this->kokec       ,
        // $this->email       ,
        // $this->hp          ,
        // $this->parent_id );
        return $this
            ->to($this->to_address)
            ->subject('Paid Notifikasi')
            ->view('emails.notification')
            ->with(
                [
                    'transdata'     => $this->payment,
                    'orderdata'     => $this->orderdata,
                    'nama'          => $this->nama,
                    'alamat'        => $this->alamat,
                    'email'         => $this->email,
                    'hp'            => $this->hp,
                    'parent_id'     => $this->parent_id,
                    'title'         => $this->title,
                    'number'        => $this->number,
                    'varian'        => $this->varian
                ]
            );
    }
}
