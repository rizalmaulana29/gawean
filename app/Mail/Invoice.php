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
    
    public function __construct($to_address, $transdata, $orderdata)
    {
        $this->to_address = $to_address;
        $this->transdata = $transdata;
        $this->orderdata = $orderdata;
        
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
                    'transdata' => $this->transdata,
                    'orderdata' => $this->orderdata
                ]
            );
    }
}
