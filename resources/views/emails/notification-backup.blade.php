<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Paid Notification</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body{
            font-family:'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;
            text-align:left;
            color:#333;
            font-size:12px;
            margin:0;
        }
        .container{
            margin:0 auto;
            margin-top:20px;
            padding:30px;
            /* width:750px; */
            height:auto;
            background-color:#fff;
        }
        caption{
            font-size:20px;
            margin-bottom:15px;
        }
        table{
            border:1px solid #333;
            border-collapse:collapse;
            margin:0 auto;
            /* width:740px; */
        }
        .noborder{
            border:0;
            margin:0 !important;
            border-collapse:collapse;
        }
        table.produk td, tr, th{
            padding:10px;
            border:1px solid #333;
            /* width:185px; */
        }
        th{
            background-color: #f0f0f0;
        }
        h4, p{
            margin:0px;
        }
    </style>
</head>
<body>

    <div class="container">
        <div style="margin-bottom: 5px;">
            <img src="https://dev.rumahaqiqah.co.id/wp-content/uploads/2018/11/rumahaqiqah-logo.png" class="img-fluid ${3|rounded-top,rounded-right,rounded-bottom,rounded-left,rounded-circle,|}" alt="">
        </div>
        <?php use Illuminate\Support\Facades\DB; 
            $kantor = DB::table('ra_kantor')->select('alamat','kota','tlp')->where('id', $transdata->id_kantor)->first();
        ?>
        <div style="margin-bottom: 20px;">
            <p>{{$kantor->alamat}} {{$kantor->kota}} {{$kantor->tlp}}</p>
        </div>
        <div>
        </div>
        <hr>

        <div style="margin-bottom: 20px; margin-top: 20px; text-align: left;" >
            <div>
                <h2 style="color: blue;">NOTIFIKASI PEMBAYARAN</h2>
            </div>
            <table class="noborder" cellspacing="0" width="100%">
                <tr class="noborder" >
                    <td class="noborder">No. Invoice</td>
                    <td class="noborder" style="width: 5px;">:</td>
                    <td class="noborder">{{$transdata->id_transaksi}}</td>
                    <td width="40%"></td>
                    <td class="noborder">Tgl. Transaksi</td>
                    <td class="noborder" style="width: 5px;">:</td>
                    <td class="noborder">{{ date("Y-m-d",strtotime($transdata->tgl_transaksi)) }}</td>
                </tr>
                <tr>
                    <td>Nama Customer</td>
                    <td class="noborder">:</td>
                    <td class="noborder">{{ $nama }}</td>
                    <td></td> 
                    <td class="noborder">Jenis</td>
                    <td class="noborder">:</td>
                    <td class="noborder">{{$transdata->jenis}}</td>             
                </tr>
                <tr>
                    <td class="noborder">Alamat</td>
                    <td class="noborder">:</td>
                    <td class="noborder">{{ $alamat }} {{ $kokec }}</td>                
                    <td></td>  
                    <td class="noborder">Status</td>
                    <td class="noborder">:</td>
                    <td class="noborder">{{$transdata->status}}</td>            
                </tr>
                <tr>
                    <td class="noborder">No. Handphone</td>
                    <td class="noborder">:</td>
                    <td class="noborder">{{ $hp }}</td>                
                    <td></td> 
                    <td class="noborder">Tipe</td>
                    <td class="noborder">:</td>
                    <td class="noborder">{{$transdata->tipe}}</td>             
                </tr>
                <tr>
                    <td class="noborder">Email</td>
                    <td class="noborder">:</td>
                    <td class="noborder">{{ $email }}</td>                
                    <td></td>  
                    <?php $bankRek = DB::table('ra_bank_rek')->select('keterangan','id_rekening')->where('id', $transdata->id_payment_method)->first();
                        if($bankRek->keterangan == "cash")
                    
                            {echo "<td>".$bankRek->keterangan."</td>";}
                        else
                            {echo "<td>Bank </td>
                                   <td>:</td>
                                   <td>".$bankRek->keterangan."<br>".$bankRek->id_rekening."</td>";}
                    ?>                           
            </table>
        </div>
        <table width="100%" class="produk">
            
            <tbody>
                
                <tr>
                    <th colspan="2" style="text-align: center;">Produk</th>
                    <th style="text-align: center;">Harga</th>
                    <th style="text-align: center;">Qty</th>
                    <th colspan="2">Subtotal</th>
                </tr>
                @foreach ($orderdata as $row)
                <tr>
                    <td colspan="2">
                        <?php 
                            $label = DB::table('ra_produk_harga')->select('label')->where('id', $row->ra_produk_harga_id)->first();
                            echo $label->label;
                        ?>
                    </td>
                    <td style="text-align: right;">Rp {{ number_format($row->harga) }}</td>
                    <td style="text-align: center;">{{ $row->quantity }}</td>
                    <td colspan="2" style="text-align: right;">Rp {{ number_format($row->total_transaksi) }}</td>
                </tr>
                @endforeach
                <tr>
                    <th colspan="4">Total</th>
                    <th colspan="2" style="text-align: right;">Rp {{ number_format($transdata['nominal']) }}</th>
                </tr>
                <tr>
                    
                </tr>
            </tbody>
            
        </table>
        <div style="margin-top: 30px">
            <div style="text-align: left;">
                <p>Terima Kasih atas pembayaran yg Anda lakukan. Untuk Check pemesanan Anda, Anda bisa klik link berikut ini<a class="btn btn-success" href="https://dev.rumahaqiqah.co.id/tracking-order/?id={{$transdata->id_transaksi}}">klik disini</a></p>
            </div>
        </div>
    </div>
</body>
</html>