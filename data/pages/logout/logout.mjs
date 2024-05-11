export async function Init() {
	window.$cookie.set('FGTATOKEN', null, -1)
	window.$cookie.set('USER_ID', null, -1)
	window.$cookie.set('USER_FULLNAME', null, -1)
}