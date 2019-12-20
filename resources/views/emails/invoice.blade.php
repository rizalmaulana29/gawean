<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Invoice</title>
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
        td, tr, th{
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
        <img src="https://dev.rumahaqiqah.co.id/wp-content/uploads/2018/11/rumahaqiqah-logo.png" class="img-fluid ${3|rounded-top,rounded-right,rounded-bottom,rounded-left,rounded-circle,|}" alt="">
        <table width="100%">
            <!-- <tr>
                <td>
                    Heyyyyy
                </td>
            </tr> -->
            <tbody>
                <tr>
                    <th colspan="3">Invoice <strong>#{{$transdata->id_transaksi}}</strong></th>
                    <th colspan="3">{{ date("Y-m-d",strtotime($transdata->tgl_transaksi)) }}</th>
                </tr>
                <tr>
                    <th colspan="2">
                        <h4>Perusahaan: </h4>
                    </th>
                    <th colspan="2">
                        <h4>Pelanggan: </h4>
                    </th>
                    <th colspan="2">
                        <h4>Status Pembayaran</h4>
                    </th>
                </tr>
                <tr>
                    <td colspan="2">
                        <p>Agro Surya Perkasa<br>
                            <br>
                            <br>
                            rumahaqiqah.co.id
                        </p>
                    </td>
                    <td colspan="2">
                        <p>{{ $nama }}<br>
                            {{ $alamat }}<br>
                            {{ $kokec }}<br>
                            {{ $email }}
                        </p>
                    </td>
                    <td colspan="2">
                        <?php 
                            $bankRek = DB::table('ra_bank_rek')->select('keterangan','id_rekening')->where('id', $transdata->id_payment_method)->first();
                            if($bankRek->keterangan == "cash")
                                {echo "<b>".$bankRek->keterangan."</b><br>";}
                            else
                                {echo "<b>Bank : </b>".$bankRek->keterangan." <br><b>No. Rek : </b>".$bankRek->id_rekening."<br>";}
                        ?>
                        Jenis : {{$transdata->jenis}}<br>
                        Status : {{$transdata->status}}<br>
                        Tipe : {{$transdata->tipe}}<br>
                        Lunas : {{$transdata->lunas}}
                    </td>
                </tr>
                <tr>
                    <th colspan="2">Produk</th>
                    <th>Harga</th>
                    <th>Qty</th>
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
                    <td>Rp {{ number_format($row->harga) }}</td>
                    <td>{{ $row->quantity }}</td>
                    <td colspan="2">Rp {{ number_format($row->total_transaksi) }}</td>
                </tr>
                @endforeach
                <tr>
                    <th colspan="4">Total</th>
                    <td colspan="2">Rp {{ number_format($transdata['nominal']) }}</td>
                </tr>
                <tr>
                    
                </tr>
            </tbody>
            
        </table>

        <br>
            <p>{{$instruksion}}</p>
        
    </div>
</body>
</html>