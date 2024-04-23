export async function generate_session_token(endpoint, data) {
	var baseAddress = window.$tpl.baseAddress
	var url = baseAddress + '/' + endpoint

	var postData = {
		requestData: data
	}

	var postDataJson = JSON.stringify(postData);
	var privateKeyString = getPrivateKeyString();
	var appid = getAppId()
	var timestamp = getTimestamp()
	var requestId = getRequestId()      // uniq kode setiap kali kirim

	var dataToSign = `POST:${endpoint}:${appid}:${requestId}:${timestamp}:${postDataJson}`;
	var signature = await createSignature(dataToSign, privateKeyString);

	var postHeader = new Headers({
		'Content-Type': 'application/json; charset=UTF-8',
		'X-APPID' : appid,
		'X-TIMESTAMP' : timestamp,
		'X-REQID' : requestId,
		'X-SIGN' : signature
	})

	try {
		var result = await url_fetch(url, postDataJson, postHeader);
		if (result.code!==0) {
			throw new Error(result.message)
		}
	
		if (result.responseData===undefined) {
			throw new Error(`responseData belum didefinisikan di response API`);
		}
	
		if (result.debugoutput!==undefined && result.debugoutput!=="" ) {
			console.log(result.debugoutput);
		}
	
		return result.responseData;
	} catch (err) {
		throw err
	}

}


export async function call(endpoint, data, options) {
	var baseAddress = window.$tpl.baseAddress;
	var url = baseAddress + '/' + endpoint;
	
	var postData = {
		requestData: data
	}

	var opt = {}
	if (options!=null) {

	}

	var postDataJson = JSON.stringify(postData);
	var privateKeyString = getPrivateKeyString();
	var appid = getAppId()
	var token = getToken()
	var timestamp = getTimestamp()
	//var sessionId = getSessionId()
	var requestId = getRequestId()      // uniq kode setiap kali kirim

	var dataToSign = `POST:${endpoint}:${appid}:${requestId}:${timestamp}:${token}:${postDataJson}`;
	var signature = await createSignature(dataToSign, privateKeyString);


	var postHeader = new Headers({
		'Content-Type': 'application/json; charset=UTF-8',
		'X-APPID' : appid,
		'X-TOKEN': token,
		'X-TIMESTAMP' : timestamp,
		'X-REQID' : requestId,
		'X-SIGN' : signature
	})

	try {
		var result = await url_fetch(url, postDataJson, postHeader);
		if (result.code!==0) {
			if ([13001, 13002].includes(result.code)) {
				// session habis, relogin
				location.href = 'page/login'
			} else {
				throw new Error(result.message)
			}
		}

		if (result.responseData===undefined) {
			throw new Error(`responseData belum didefinisikan di response API`);
		}

		if (result.debugoutput!==undefined && result.debugoutput!=="" ) {
			console.log(result.debugoutput);
		}

		return result.responseData;
	} catch (err) {
		throw err
	}

	



} 

async function url_fetch(url, body, headers) {
	var fetchData = {
		method: 'POST',
		body: body,
		headers: headers
	}

	return new Promise(function(resolve, reject) {
		fetch(url, fetchData)
		.then((response) => {
			return response.json()
		})
		.then((result) => {
			resolve(result)
		})
		.catch(function(error) {
			reject(error)
		});
	})
}

async function createSignature(dataToSign, privateKeyString) {
	var privateKey = await importPrivateKey(privateKeyString)
	var dataBytes = new TextEncoder().encode(dataToSign);
	var signature = await window.crypto.subtle.sign(
		{ name: "RSASSA-PKCS1-v1_5" },
		privateKey,
		dataBytes
	);
	var signature =  Array.prototype.map.call(new Uint8Array(signature), x => ('00' + x.toString(16)).slice(-2)).join('');
	return signature;
} 

async function verifySignature(signedData, signature, publicKeyString) {
	var publicKey = await importPrivateKey(publicKeyString)
	var dataBytes = new TextEncoder().encode(signedData);
	var signatureBinary = hexToBytes(signature);
	var verificationResult = await window.crypto.subtle.verify(
		{ name: "RSASSA-PKCS1-v1_5", hash: { name: "SHA-256" } },
		publicKey,
		signatureBinary,
		dataBytes
	)	
	return 	verificationResult;
}


async function importPrivateKey(privateKeyString) {
	var keyBinaryData = getKeyBinnary(privateKeyString)
	var privateKey = await window.crypto.subtle.importKey(
        "pkcs8", 
        keyBinaryData, 
        { name: "RSASSA-PKCS1-v1_5", hash: { name: "SHA-256" } }, 
        true, 
        ["sign"]
    );
	return privateKey;
}

async function importPublicKey(publicKeyString) {
	var keyBinaryData = getKeyBinnary(privateKeyString)
	var publicKey = await window.crypto.subtle.importKey(
		"spki", 
		keyBinaryData,
		{ name: "RSASSA-PKCS1-v1_5", hash: { name: "SHA-256" } },
		true, 
		["verify"]
	);
	return publicKey;
}

function getKeyBinnary(keyString) {
	const binaryString = window.atob(keyString.split('\n').slice(1, -1).join(''));
	const binaryData = new Uint8Array(binaryString.length);
	for (let i = 0; i < binaryString.length; i++) {
		binaryData[i] = binaryString.charCodeAt(i);
	}
	return binaryData
}

function getPrivateKeyString() {
	return window.$tpl.keypair.privatekey;
}

function getAppId() {
	return window.$tpl.appid;
}

function getToken() {
	var token = window.$cookie.get('FGTATOKEN');
	return token;
}

function getTimestamp() {
	var currentTime = new Date().getTime();
	var utcTimestamp = new Date(currentTime).toISOString();
	var timestamp = utcTimestamp;
	return timestamp;
}

function getRequestId() {
	return window.$utils.generateUUID();
}

function getSessionId() {
	var sessid = window.$ui.getCookie('PHPSESSID');
	return sessid;
}

