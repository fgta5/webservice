const obj_txt_username = document.getElementById('obj_txt_username')
const obj_txt_password = document.getElementById('obj_txt_password')


const btn_login = document.getElementById('btn_login')
const txt_error = document.getElementById('txt_error')
const txt_result = document.getElementById('txt_result')


let self = {};

export async function Init() {

	self.default_login_text = btn_login.innerHTML;
	btn_login.addEventListener('click', ()=>{
		btn_login_click()
	})
}


async function btn_login_click() {
	txt_error.classList.add('hidden')
	txt_result.classList.add('hidden')
	btn_login.disabled = true;
	btn_login.innerHTML = 'wait...'

	try {

		var queryString = window.location.search;
		var urlParams = new URLSearchParams(queryString);
		var redirectto = urlParams.get('referer')

		var username = obj_txt_username.value 
		var password = obj_txt_password.value

		var endpoint = 'api/login'
		var data = {
			user_id: username,
			user_password: password
		}

		
		var result = await window.$api.call(endpoint, data)
		var success = result.success
		var message = result.message
		var user_id = result.user_id 
		var user_fullname = result.user_fullname
		var token = result.token
		var expired = result.expired

		if (success) {
			window.$cookie.set('FGTATOKEN', token, expired)
			window.$cookie.set('USER_ID', user_id, expired)
			window.$cookie.set('USER_FULLNAME', user_fullname, expired)

			txt_result.classList.remove('hidden')
			txt_result.innerHTML = 'login berhasil, halaman akan diredirect';

			if (redirectto!='') {
				location.href = redirectto
			} else {
				location.href = '/'
			}
		} else {
			throw new Error(message)
		}
	} catch (err) {
		txt_error.innerHTML = err.message;
		txt_result.innerHTML = '';

		txt_error.classList.remove('hidden')
		txt_result.classList.add('hidden')
	} finally {
		btn_login.disabled = false
		btn_login.innerHTML = self.default_login_text 
	}
}