const btn_login = document.getElementById('btn_login')
const btn_refreshtoken = document.getElementById('btn_refreshtoken')
const btn_logout = document.getElementById('btn_logout');

let self = {};

export async function Init() {
	btn_login.addEventListener('click', ()=>{
		btn_login_click();
	})

	btn_refreshtoken.addEventListener('click', ()=>{
		btn_refreshtoken_click();
	})

	btn_logout.addEventListener('click', ()=>{
		btn_logout_click();
	})
}


async function btn_login_click() {
	console.log('login click');

	try {
		var endpoint = 'api/login'
		var data = {
			username: 'agung',
			password: 'rahasia'
		}
		var result = await window.$api.call(endpoint, data)
		var token = result.token
		var expired = result.expired

		window.$cookie.set('FGTATOKEN', token, expired)
		location.reload()
	} catch (err) {
		console.error(err);
	}

}


async function btn_refreshtoken_click() {
	console.log('refresh token click')

	try {
		var endpoint = 'api/refresh-token'
		var data = {}
		var result = await window.$api.call(endpoint, data)
		console.log(result)

		var token = result.newtoken
		var expired = result.expired
		window.$cookie.set('FGTATOKEN', token, expired)

	} catch (err) {
		console.error(err);
	}
}

async function btn_logout_click() {
	console.log('logout click')

	try {
		var endpoint = 'api/logout'
		var data = {
		}

		var result = await window.$api.call(endpoint, data)
		console.log(result);
		if (result.success) {
			window.$cookie.set('FGTATOKEN', null, -1)
			location.reload()
		}
	} catch (err) {
		console.error(err);
	}

}