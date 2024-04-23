<?php 

//TODO: Daftarkan semua list Error di sini

/*
 0 s/d 500 : Internal Error
 1xxxx : Authentication Error
        11xxx : Client Header Error
				11001: X-SIGN header not set
				11002: X-TOKEN header not set

		12xxx : Client Signature Error
				12000: Signature not valid
				12001: Duplicate of request id
				12002: Timestamp expired 

		13xxx : Client Token Error
				13001: Token data size invalid
				13002: Token not match vs server
				13003: Token not found in database
				13999: Client Token Expired, need to relogin
		14xxx : Client Session Error

		19xxx : Auth Error
				19100: Client not Valid (cause by APPID / APPSECRET not valid)
				192xx: User not valid
						19210 : User not Found
						19215 : User is inactive
						19220 : Password not match



 2xxxx :
 9xxxx : Application Error 
*/
$ERROR_LIST = [
	10000 => 'Signature Error',
	10010 => 'Client Token error',
	10015 => 'Client Header error',
	10020 => 'Session error',
	10025 => 'Client not valid',
];