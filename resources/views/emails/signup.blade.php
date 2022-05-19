<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Informasi</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<!-- style di ambil -->
	<?php 
        use Illuminate\Support\Facades\DB;
    ?>
</head>
<body>
	<table style="border-collapse:collapse;width:100%">
        <tbody>
            
             
            <tr>
                <td style="padding:8px 16px">
                    <div>
                        <h2 style="color: blue;">Informasi Pendaftaran</h2>
                    </div>
                </td>
            </tr>
            <tr>
                <td style="padding:15px 16px">
				<table style="border-collapse: collapse; width: 100%;" border="0">
<tbody>
<tr>
<td style="width: 7.54031%;"><img src="https://storage.googleapis.com/donol.cinte.id/asp/logo%20kawan%20dagang_agro-01.png" alt="" width="147" height="111" /></td>
<td style="width: 67.8909%;">&nbsp;</td>
<td style="width: 24.5687%;"><img src="https://storage.googleapis.com/donol.cinte.id/asp/agro%20baru-01%20copy.png" alt="" width="291" height="120" /></td>
</tr>
<tr>
<td style="width: 99.9999%; margin: 10px 10px 10px 10px; padding: 10px 10px 10px 10px;" colspan="3">
<p class="p1" style="margin: 0px; font-variant-numeric: normal; font-variant-east-asian: normal; font-stretch: normal; font-size: 12px; line-height: normal; font-family: 'Helvetica Neue';">&nbsp;</p>
<p class="p1" style="margin: 0px; font-variant-numeric: normal; font-variant-east-asian: normal; font-stretch: normal; font-size: 12px; line-height: normal; font-family: 'Helvetica Neue';">&nbsp;</p>
<p class="p1" style="margin: 0px; font-variant-numeric: normal; font-variant-east-asian: normal; font-stretch: normal; font-size: 12px; line-height: normal; font-family: 'Helvetica Neue';">Halo   {{ $nama }}</p>
<p class="p2" style="margin: 0px; font-variant-numeric: normal; font-variant-east-asian: normal; font-stretch: normal; font-size: 12px; line-height: normal; font-family: 'Helvetica Neue'; min-height: 14px;">&nbsp;</p>
<p class="p1" style="margin: 0px; font-variant-numeric: normal; font-variant-east-asian: normal; font-stretch: normal; font-size: 12px; line-height: normal; font-family: 'Helvetica Neue';">Selamat bergabung di <b>Kawan Dagang</b>.</p>
<p class="p1" style="margin: 0px; font-variant-numeric: normal; font-variant-east-asian: normal; font-stretch: normal; font-size: 12px; line-height: normal; font-family: 'Helvetica Neue';">Disini Kamu bisa meningkatkan potensi jualan Kamu karena akan dibimbing oleh para coaches serta mentor yang keren-keren.</p>
<p class="p2" style="margin: 0px; font-variant-numeric: normal; font-variant-east-asian: normal; font-stretch: normal; font-size: 12px; line-height: normal; font-family: 'Helvetica Neue'; min-height: 14px;">&nbsp;</p>
<p class="p1" style="margin: 0px; font-variant-numeric: normal; font-variant-east-asian: normal; font-stretch: normal; font-size: 12px; line-height: normal; font-family: 'Helvetica Neue';">Apakah Kamu sudah melakukan AKTIVASI akun?<span class="Apple-converted-space">&nbsp;</span></p>
<p class="p1" style="margin: 0px; font-variant-numeric: normal; font-variant-east-asian: normal; font-stretch: normal; font-size: 12px; line-height: normal; font-family: 'Helvetica Neue';">Kalau belum, segera lakukan Aktivasi akun kamu untuk mendapatkan Promo menarik serta benefit lainnya dengan klik link berikut ini.</p>
<p class="p2" style="margin: 0px; font-variant-numeric: normal; font-variant-east-asian: normal; font-stretch: normal; font-size: 12px; line-height: normal; font-family: 'Helvetica Neue'; min-height: 14px;">&nbsp;</p>
<p class="p1" style="margin: 0px; font-variant-numeric: normal; font-variant-east-asian: normal; font-stretch: normal; font-size: 12px; line-height: normal; font-family: 'Helvetica Neue';"><a href="{{ $link_email_verify }}">{{ $link_email_verify }}</a></p>
<p class="p2" style="margin: 0px; font-variant-numeric: normal; font-variant-east-asian: normal; font-stretch: normal; font-size: 12px; line-height: normal; font-family: 'Helvetica Neue'; min-height: 14px;">&nbsp;</p>
<p class="p1" style="margin: 0px; font-variant-numeric: normal; font-variant-east-asian: normal; font-stretch: normal; font-size: 12px; line-height: normal; font-family: 'Helvetica Neue';">Klik link berikut untuk login</p>
<p class="p1" style="margin: 0px; font-variant-numeric: normal; font-variant-east-asian: normal; font-stretch: normal; font-size: 12px; line-height: normal; font-family: 'Helvetica Neue';">&nbsp;</p>
<p class="p1" style="margin: 0px; font-variant-numeric: normal; font-variant-east-asian: normal; font-stretch: normal; font-size: 12px; line-height: normal; font-family: 'Helvetica Neue';"><span style="font-family: Helvetica Neue;"><span style="font-size: 12px;"><a href="https://kawandagang.id/agen/login">https://kawandagang.id/agen/login</a></span></span></p>
<p class="p1" style="margin: 0px; font-variant-numeric: normal; font-variant-east-asian: normal; font-stretch: normal; font-size: 12px; line-height: normal; font-family: 'Helvetica Neue';">&nbsp;</p>
<p class="p1" style="margin: 0px; font-variant-numeric: normal; font-variant-east-asian: normal; font-stretch: normal; font-size: 12px; line-height: normal; font-family: 'Helvetica Neue';"><span style="font-family: Helvetica Neue;"><span style="font-size: 12px;">username :  {{ $email }}</span></span></p>
<p class="p1" style="margin: 0px; font-variant-numeric: normal; font-variant-east-asian: normal; font-stretch: normal; font-size: 12px; line-height: normal; font-family: 'Helvetica Neue';">&nbsp;</p>
<p class="p1" style="margin: 0px; font-variant-numeric: normal; font-variant-east-asian: normal; font-stretch: normal; font-size: 12px; line-height: normal; font-family: 'Helvetica Neue';"><span style="font-family: Helvetica Neue;"><span style="font-size: 12px;">password :  {{ $password }}</span></span></p>
<p class="p1" style="margin: 0px; font-variant-numeric: normal; font-variant-east-asian: normal; font-stretch: normal; font-size: 12px; line-height: normal; font-family: 'Helvetica Neue';">&nbsp;</p>
<p class="p1" style="margin: 0px; font-variant-numeric: normal; font-variant-east-asian: normal; font-stretch: normal; font-size: 12px; line-height: normal; font-family: 'Helvetica Neue';">&nbsp;</p>
<p class="p1" style="margin: 0px; font-variant-numeric: normal; font-variant-east-asian: normal; font-stretch: normal; font-size: 12px; line-height: normal; font-family: 'Helvetica Neue';">Apabila kamu sudah aktivasi akun dan melakukan transaksi pertama, maka kamu akan mendapatkan banyak sekali benefit dari platform <b>Kawan Dagang</b> diantaranya sebagai berikut berikut:</p>
<p class="p1" style="margin: 0px; font-variant-numeric: normal; font-variant-east-asian: normal; font-stretch: normal; font-size: 12px; line-height: normal; font-family: 'Helvetica Neue';">1. Mendapatkan harga super hemat hingga 30% dibandingkan harga pasaran</p>
<p class="p1" style="margin: 0px; font-variant-numeric: normal; font-variant-east-asian: normal; font-stretch: normal; font-size: 12px; line-height: normal; font-family: 'Helvetica Neue';">2. Dilatih, dibimbing dan didampingi oleh coaches serta mentor keren untuk berjualan</p>
<p class="p1" style="margin: 0px; font-variant-numeric: normal; font-variant-east-asian: normal; font-stretch: normal; font-size: 12px; line-height: normal; font-family: 'Helvetica Neue';">3. Group Telegram https://bit.ly/KawanDagangTalent , untuk mendapatkan materi - materi promo gratis dan sharing informasi  </p>
<p class="p1" style="margin: 0px; font-variant-numeric: normal; font-variant-east-asian: normal; font-stretch: normal; font-size: 12px; line-height: normal; font-family: 'Helvetica Neue';">4. Kemudahan mendapatkan produk yang tersebar di Pulau Jawa dan Sumatera</p>
<p class="p1" style="margin: 0px; font-variant-numeric: normal; font-variant-east-asian: normal; font-stretch: normal; font-size: 12px; line-height: normal; font-family: 'Helvetica Neue';">5. Berhak mengikuti Reward (Umroh, Trip to Turkey, serta hadiah menarik lainnya)</p>
<p class="p2" style="margin: 0px; font-variant-numeric: normal; font-variant-east-asian: normal; font-stretch: normal; font-size: 12px; line-height: normal; font-family: 'Helvetica Neue'; min-height: 14px;">&nbsp;</p>
<p class="p1" style="margin: 0px; font-variant-numeric: normal; font-variant-east-asian: normal; font-stretch: normal; font-size: 12px; line-height: normal; font-family: 'Helvetica Neue';">Ajak juga keluarga, rekan, sahabat serta teman kamu untuk ikut bergabung bersama <b>Kawan Dagang</b> untuk mendapatkan manfaatnya.</p>
<p class="p2" style="margin: 0px; font-variant-numeric: normal; font-variant-east-asian: normal; font-stretch: normal; font-size: 12px; line-height: normal; font-family: 'Helvetica Neue'; min-height: 14px;">&nbsp;</p>
<p class="p1" style="margin: 0px; font-variant-numeric: normal; font-variant-east-asian: normal; font-stretch: normal; font-size: 12px; line-height: normal; font-family: 'Helvetica Neue';"><b>Kawan Dagang</b></p>
<p class="p1" style="margin: 0px; font-variant-numeric: normal; font-variant-east-asian: normal; font-stretch: normal; font-size: 12px; line-height: normal; font-family: 'Helvetica Neue';">Saatnya Kamu Jadi Miliarder</p>
</td>
</tr>
<tr>
<td style="width: 7.54031%;"><img src="https://storage.googleapis.com/donol.cinte.id/asp/rumah%20aqiqah%20logo-04.png" alt="" width="179" height="179" /></td>
<td style="width: 67.8909%;"><img style="display: block; margin-left: auto; margin-right: auto;" src="https://storage.googleapis.com/donol.cinte.id/asp/SANUSA%20COLOR-05-05.png" alt="" /></td>
<td style="width: 24.5687%;"><img src="https://storage.googleapis.com/donol.cinte.id/asp/RUMAH%20QURBAN%20COLOR-04.png" alt="" width="303" height="139" /></td>
</tr>
</tbody>
</table>
                </td>
            </tr>

            
			
        	<tr>
        		<td style="padding:12px 16px">
        			<p style="font-size:14px;line-height:21px;margin:0 0 20px">
        				Butuh bantuan? Silahkan <a href="https://api.whatsapp.com/send?phone=6281370071330&text=Assalamu%27alaikum%20Rumah%20Aqiqah">klik disini.</a>
        			</p>

        			 
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
        								<!-- ////////////////////////////////////////////////////////////////////////////////////// -->
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
        								<p style="padding-top:5px;font-size:13px;line-height:1.22;margin:0">© 2019-2022 PT. Agro Surya Perkasa. All Rights Reserved.</p>
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

