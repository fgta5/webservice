import * as webapi from './web-api-1.mjs'
import * as webmask from './web-mask-1.mjs'


export async function Prepare() {
	console.log('Preparing module')
	window.$api = webapi
	window.$mask = webmask

	// create_mask()
}

export async function Init() {
	console.log('Default init')
}

export async function Ready() {
	console.log('Module Ready')

	var masks = document.getElementsByClassName('page-cover-mask')
	for (var elm of masks) {
		elm.parentNode.removeChild(elm)
	}
}


// function create_mask() {
// 	window.$mask = {
// 		el: null
// 	}
	
// 	window.$mask.attach = (elid) => {
// 		window.$mask.el = document.getElementById(elid)
// 	}

// 	window.$mask.hide = () => {
// 		if (window.$mask.el!=null) {
// 			window.$mask.el.classList.add('hidden')
// 		}
// 	}

// 	window.$mask.isVisible = () => {
// 		if (window.$mask.el!=null) {
// 			if (window.$mask.el.classList.contain('hidden')) {
// 				return false
// 			} else {	
// 				return true
// 			}
// 		} else {
// 			return false
// 		}
// 	}

// 	window.$mask.show = (message) => {

// 	}

// 	window.$mask.attach('page-cover-mask')
// }