const self = {}

export function Construct() {
	self.currentSubPage = null
	self.Pages = {}
}

export function AddSubpage(el, isdefault) {
	self.Pages[el.id] = el
	if (isdefault) {
		self.currentSubPage = el
	}
}

export function Show(el) {
	// fading currentSubPage
	self.currentSubPage.classList.add('hidden');

	// show newPage
	el.classList.remove('hidden');
	self.currentSubPage = el
}