const self = {}

export function Construct() {
	self.currentSubPage = null
	self.Pages = {}
}

export function getPage(id) {
	return self.Pages[id]
}

export function AddSubpage(el, module, isdefault) {
	el.module = module
	el.Show = ()=> {
		self.currentSubPage.classList.add('hidden');
		
		el.classList.remove('hidden');
		self.currentSubPage = el
	}
	self.Pages[el.id] = el
	if (isdefault) {
		self.currentSubPage = self.Pages[el.id]
	}
}

export function Show(obj) {
	var el;
	if (typeof obj === 'string') {
		el = document.getElementById(obj)
	} else {
		el = obj;
	}

	if (obj==null) {
		console.error('sub page yang akan ditampilkan tidak ditemukan')
		return;
	}

	// fading currentSubPage
	self.currentSubPage.classList.add('hidden');

	// show newPage
	el.classList.remove('hidden');
	self.currentSubPage = el
}