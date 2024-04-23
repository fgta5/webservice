import * as tfiwebapi from './web-api-1.mjs';


export async function Prepare() {
	console.log('Preparing module');
	window.$api = tfiwebapi;
}

export async function Init() {
	console.log('Default init');
}

export async function Ready() {
	console.log('Module Ready');
}
