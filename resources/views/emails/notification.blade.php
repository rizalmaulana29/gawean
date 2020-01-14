<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Paid Notification - Rumah Aqiqah</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <?php 
        use Illuminate\Support\Facades\DB;
    ?>
</head>
<body>
    <table style="border-collapse:collapse;width:100%">
		<tbody>
            
			<tr>
				<td style="padding:15px 16px"><img src="https://dev.rumahaqiqah.co.id/wp-content/uploads/2018/11/rumahaqiqah-logo.png" class="img-fluid ${3|rounded-top,rounded-right,rounded-bottom,rounded-left,rounded-circle,|}" alt="rumahaqiqah.co.id" style="border:none;max-width:100%;display:block;width:192px" class="CToWUd"></td>
			</tr>
            <tr>
                <td style="padding:8px 16px">
                    <div>
                        <h2 style="color: blue;">NOTIFIKASI PEMBAYARAN</h2>
                    </div>
                </td>
            </tr>
			<tr>
				<td style="padding:15px 16px">
					<table border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse;width:100%">
						<tbody>
							<tr>
								<td>
									<h1 style="margin:0 0 16px;font-size:25px;line-height:38px">Assalamu'alaikum {{ $nama }},</h1>
									<p style="margin:0;font-size:16px;line-height:24px">Terima kasih atas pembayaran transaksi Anda dengan Nomor Invoice: <b style="color: blue;">{{$transdata->id_transaksi}}</b></p>
								</td>
							</tr>
						</tbody>
					</table>
				</td>
			</tr>

			<tr>
				<td style="padding:12px 16px">
					<h2 style="background:#f5f6fa;font-size:16px;line-height:24px;margin:0;padding:8px 16px;border-color:#dee2ee;border-style:solid;border-width:1px 1px 0;border-radius:8px 8px 0 0">
						Informasi Pembayaran
					</h2>
					<div style="border-color:#dee2ee;border-style:solid;border-width:0 1px 1px;padding:16px;border-radius:0 0 8px 8px">
						<table style="width:100%;table-layout:fixed" cellspacing="0" cellpadding="0">
							<tbody>
								<tr>
									<td style="font-size:14px;color:#8a93a7;padding:3px">{{$title}}</td>
                                    <?php 
                                        $bankRek = DB::table('ra_bank_rek')->select('keterangan','id_rekening','gambar','id_payment_method','parent_id')->where('id', $transdata->id_payment_method)->first();
									?>
									@if($bankRek->keterangan == "cash")
									<td style="font-size:14px;color:#8a93a7;padding:3px">{{$bankRek->keterangan}}</td>
									@else
									<td>{{$bankRek->keterangan}}<br>{{$bankRek->id_rekening}}</td>
									@endif
								</tr>

								<tr style="margin:0 0 10px">
									<td style="font-size:14px;padding:0 3px">
										{{$number}}
									</td>
                                <td style="font-size:14px;padding:0 3px">
                                    @if($bankRek->parent_id != 26)
                                        <img style="max-height:20px;max-width:100%" src="https://dev-backend.rumahaqiqah.co.id/{{$bankRek->gambar}}">
									@else
                                        <img style="max-height:20px;max-width:100%" src="{{$bankRek->gambar}}">
									@endif
                                </td>
                            </tr>

                            <tr>
                            	<td style="font-size:14px;color:#8a93a7;padding:15px 3px 3px">
                            		Total Pembayaran
                            	</td>
                            	<td style="font-size:14px;color:#8a93a7;padding:15px 3px 3px">
                            		Account Holder Name
                            	</td>
                            </tr>

                            <tr>
                            	<td style="font-size:16px;padding:0 3px;color:#0064d2;font-weight:bold">IDR {{ number_format($transdata['nominal']) }}</td>
                            	<td style="font-size:14px;padding:0 3px"><a style="color:#000000;text-decoration:none">RUMAHAQIQAH.CO.ID</a></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </td>
        </tr>

        <tr>
        	<td style="padding:12px 16px">
        		<h2 style="background:#f5f6fa;font-size:16px;line-height:24px;margin:0;padding:8px 16px;border-color:#dee2ee;border-style:solid;border-width:1px 1px 0;border-radius:8px 8px 0 0">
        			Detail Pesanan
        		</h2>
        		<div style="border-color:#dee2ee;border-style:solid;border-width:0 1px 1px;padding:16px;border-radius:0 0 8px 8px">
        				<table style="width:100%" cellspacing="0" cellpadding="0">
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
        					</tbody>
        				</table>
        			</div>
        		</td>
        	</tr>

        	
            <tr>
                <td style="padding:8px 16px">
                <div>
                    <h3>Untuk Check pemesanan Anda, Anda bisa klik tombol link dibawah ini <br><br>
                    <a href="https://dev.rumahaqiqah.co.id/tracking-order/?id={{$transdata->id_transaksi}}" rel="noopener" style="color:#fff;background-color:#3648e8;border-radius:3px;text-transform:uppercase;text-decoration:none;font-weight:bold;padding:10px 30px" target="_blank">Check Transaksi</a></h3>
                </div>
                </td>
            </tr>
        	<tr>
        		<td style="padding:12px 16px">
        			<p style="font-size:14px;line-height:21px;margin:0 0 20px">
        				Butuh bantuan? Silahkan <a href="https://api.whatsapp.com/send?phone=6281370071330&text=Assalam%27alaikum%20rumahaqiqah%20Saya%20Mau%20bertanya%20perihal%20aqiqah">klik disini.</a>
        				<br> Ingat Order ID: {{$transdata->id_transaksi}} Anda saat menghubungi Customer Care.
        			</p>

        			<p style="font-size:14px;line-height:21px;margin:0 0 20px">Terima kasih telah memilih <a href="https://rumahaqiqah.co.id">rumahaqiqah.co.id</a></p>

        			<p style="font-size:14px;line-height:21px;margin:0 0 20px">Salam,
        				<br><a href="https://rumahaqiqah.co.id">rumahaqiqah.co.id</a></p>
        			</td>
        		</tr>

        		<tr style="background-color:#f7f7f7">
        			<td>

        			</td>
        		</tr>

        		<tr valign="top">
        			<td style="padding-top:30px;padding-bottom:30px">
        				<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" style="text-align:center">
        					<tbody>
        						<tr>
        							<td>
        							</td>
        						</tr>
        						<tr>
        							<td>
        								<p style="margin:0;padding-top:15px;font-size:16px;color:#222222;line-height:1.5;font-weight:bold;padding-bottom:5px">PT. Agro Surya Perkasa</p>
        							</td>
        						</tr>
        						<tr>
        							<td>
        								<!-- <img title="tiket.com" alt="tiket.com" src="https://ci6.googleusercontent.com/proxy/CaFICBS7Fa5W5eXw27iM0NT3DIc2NXdXcGnQb4KtaVgeUAivjzL2KMtSfshvsWQdH9mAginSKdhKmXv_8ZX64y6jmyxB_9Unmt2LrV51lkJ7Lo6nyGzdFpl_DxqGP44T=s0-d-e1-ft#http://www.tiket.com/assets_version/cardamom/dist/images/a-blibli-company.png" width="130" class="CToWUd"> -->
        							</td>
        						</tr>
        						<tr>
        							<td>
        								<p style="padding-top:5px;font-size:13px;line-height:1.22;margin:0">© 2019-2020 PT. Agro Surya Perkasa. All Rights Reserved.</p>
        							</td>
        						</tr>
        					</tbody>
        				</table>
        			</td>
        		</tr>

        	</tbody>
    </table>
    
</body>
</html>