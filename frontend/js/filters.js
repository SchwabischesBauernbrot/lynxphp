const getFiltersFromLocalStorage = () => {
	const savedFilters = JSON.parse(localStorage.getItem('filters1'));
	return savedFilters.reduce((acc, filter) => {
		const regexFilter = filter.type.endsWith('r');
		if (regexFilter) {
			acc[filter.type].push(new RegExp(filter.val, 'i')); //todo: serialize flags? probs not necessary
		} else {
			acc[filter.type].add(filter.val);
		}
		return acc;
	}, {
		single: new Set(),
		fid: new Set(),
		fname: new Set(),
		fsub: new Set(),
		ftrip: new Set(),
		fmsg: new Set(),
		fnamer: [],
		ftripr: [],
		fsubr: [],
		fmsgr: [],
	});
};

let { single, fid, fname, ftrip, fsub, fmsg, fnamer, ftripr, fsubr, fmsgr } = getFiltersFromLocalStorage();

let filtersTable;
const updateFiltersTable = () => {
	[...filtersTable.children].slice(3).forEach(row => row.remove());
	filtersTable.insertAdjacentHTML('beforeend', filters({filterArr: JSON.parse(localStorage.getItem('filters1'))}))
	const closeButtons = filtersTable.querySelectorAll('.close');
	for (let elem of closeButtons) {
		let { type: closeType, data: closeData } = elem.dataset;
		if (closeType.endsWith('r')) {
			closeData = new RegExp(closeData, 'i');
		}
		elem.addEventListener('click', () => { toggleFilter(closeType, closeData) });
	}
}

const updateSavedFilters = () => {
	setLocalStorage('filters1', JSON.stringify([
		...([...single].map(x => ({type:'single', val:x}))),
		...([...fid].map(x => ({type:'fid', val:x}))),
		...([...ftrip].map(x => ({type:'ftrip', val:x}))),
		...([...fname].map(x => ({type:'fname', val:x}))),
		...([...fsub].map(x => ({type:'fsub', val:x}))),
		...([...fmsg].map(x => ({type:'fmsg', val:x}))),
		...fnamer.map(x => ({type:'fnamer', val:x.source.toString()})),
		...ftripr.map(x => ({type:'ftripr', val:x.source.toString()})),
		...fsubr.map(x => ({type:'fsubr', val:x.source.toString()})),
		...fmsgr.map(x => ({type:'fmsgr', val:x.source.toString()})),
	]));
	updateFiltersTable();
};

const anyFilterMatches = (filteringPost) => {
	const { board, postId, userId, name, subject, tripcode } = filteringPost.dataset;
	const postMessage = filteringPost.querySelector('.post-message');
	const message = postMessage ? postMessage.textContent : null;
	return fid.has(userId)
		|| fname.has(name)
		|| ftrip.has(tripcode)
		|| fsub.has(tripcode)
		|| fmsg.has(message)
		|| fnamer.some(r => r.test(name))
		|| ftripr.some(r => r.test(tripcode))
		|| fsubr.some(r => r.test(subject))
		|| fmsgr.some(r => r.test(message))
}

const togglePostsHidden = (posts, state, single) => {
	for (let elem of posts) {
		const showing = (!state && (!anyFilterMatches(elem) || single));
		if (showing) { //possible fix for multiple filters & unhiding conflicts
			elem.classList['remove']('hidden');
		} else {
			elem.classList['add']('hidden');
		}
		if (!isCatalog) {
			elem.querySelector('.postmenu').children[0].textContent = (showing ? 'Hide' : 'Show');
		}
	}
};

//I wish this wasn't necessary, but css selectors dont support regex :(
const getPostsByRegex = (attribute, regex) => {
	const matches = [];
	for (let elem of document.querySelectorAll(`[${attribute}]`)) {
		const value = elem.getAttribute(attribute).toString();
		if (regex.test(value) === true) {
			matches.push(elem);
		}
	}
	return matches;
};

const getPostsByMessage = (data, regex=false) => {
	//you asked for this
	const postMessages = [...document.querySelectorAll(`.${isCatalog ? 'catalog-tile': 'post-container' } .post-message`)];
	const matchingMessages = postMessages.filter(m => (regex ? data.test(m.textContent) : m.textContent.includes(data)));
	return matchingMessages.map(m => m.closest(`.${isCatalog ? 'catalog-tile': 'post-container' }`));
}

const getPostsByFilter = (type, data) => {
	let posts = [];
	switch (type) {
		case 'single':
			const [dataBoard, dataPostId] = data.split('-');
			const singlePost = document.querySelector(`[data-board="${dataBoard}"][data-post-id="${dataPostId}"]`);
			posts = singlePost ? [singlePost] : [];
			break;
		case 'fid':
			posts = document.querySelectorAll(`[data-user-id="${data}"]`);
			break;
		case 'fname':
			posts = document.querySelectorAll(`[data-name="${CSS.escape(data)}"]`);
			break;
		case 'fnamer':
			posts = getPostsByRegex('data-name', data);
			break;
		case 'ftrip':
			posts = document.querySelectorAll(`[data-tripcode="${CSS.escape(data)}"]`);
			break;
		case 'ftripr':
			posts = getPostsByRegex('data-tripcode', data);
			break;
		case 'fsub':
			posts = document.querySelectorAll(`[data-subject="${CSS.escape(data)}"]`);
			break;
		case 'fsubr':
			posts = getPostsByRegex('data-subject', data);
			break;
		case 'fmsg':
			posts = getPostsByMessage(data);
			break;
		case 'fmsgr':
			posts = getPostsByMessage(data, true);
			break;
		default:
			break;
	}
	return [...posts];
};

const setFilterState = (type, data, state) => {
	const addOrDelete = state ? 'add' : 'delete';
	switch (type) {
		case 'single':
			single[addOrDelete](data);
			break;
		case 'fid':
			fid[addOrDelete](data);
			break;
		case 'fname':
			fname[addOrDelete](data);
			break;
		case 'fnamer':
			fnamer = fnamer.filter(r => r.source != data.source);
			if (state) {
				fnamer.push(data);
			}
			break;
		case 'ftrip':
			ftrip[addOrDelete](data);
			break;
		case 'ftripr':
			ftripr = ftripr.filter(r => r.source != data.source);
			if (state) {
				ftripr.push(data);
			}
			break;
		case 'fsub':
			fsub[addOrDelete](data);
			break;
		case 'fsubr':
			fsubr = fsubr.filter(r => r.source != data.source);
			if (state) {
				fsubr.push(data);
			}
			break;
		case 'fmsg':
			fmsg[addOrDelete](data);
			break;
		case 'fmsgr':
			fmsgr = fmsgr.filter(r => r.source != data.source);
			if (state) {
				fmsgr.push(data);
			}
			break;
		default:
			break;
	}
};

const toggleFilter = (filterType, filterData, state) => {
	const posts = getPostsByFilter(filterType, filterData);
	setFilterState(filterType, filterData, state);
	togglePostsHidden(posts, state, filterType === 'single');
	updateSavedFilters();
}

const postMenuChange = function(e) {
	const postContainer = this.parentElement.parentElement.parentElement;
	const postDataset = postContainer.dataset
	const filterType = this.value;
	const hiding = !postContainer.classList.contains('hidden');
	let filterData;
	switch (filterType) {
		case 'single':
			filterData = `${postDataset.board}-${postDataset.postId}`;
			break;
		case 'fid':
			filterData = postDataset.userId;
			break;
		case 'fname':
			filterData = postDataset.name;
			break;
		case 'ftrip':
			filterData = postDataset.tripcode;
			break;
		case 'fsub':
			filterData = postDataset.subject;
			break;
	}
	toggleFilter(filterType, filterData, hiding);
	this.value = '';
};

for (let menu of document.getElementsByClassName('postmenu')) {
	menu.value = '';
	menu.addEventListener('change', postMenuChange, false);
}

const getHiddenElems = () => {
	let posts = [];
	for (let elem of single) {
		posts = posts.concat(getPostsByFilter('single', elem));
	}
	for (let id of fid) {
		posts = posts.concat(getPostsByFilter('fid', id));
	}
	for (let name of fname) {
		posts = posts.concat(getPostsByFilter('fname', name));
	}
	for (let subject of fsub) {
		posts = posts.concat(getPostsByFilter('fsub', subject));
	}
	for (let message of fmsg) {
		posts = posts.concat(getPostsByFilter('fmsg', message));
	}
	for (let tripcode of ftrip) {
		posts = posts.concat(getPostsByFilter('ftrip', tripcode));
	}
	for (let namer of fnamer) {
		posts = posts.concat(getPostsByFilter('fnamer', namer));
	}
	for (let tripcoder of ftripr) {
		posts = posts.concat(getPostsByFilter('ftripr', tripcoder));
	}
	for (let subr of fsubr) {
		posts = posts.concat(getPostsByFilter('fsubr', subr));
	}
	for (let messager of fmsgr) {
		posts = posts.concat(getPostsByFilter('fmsgr', messager));
	}
	return posts;
};

togglePostsHidden(getHiddenElems(), true);

window.addEventListener('addPost', function(e) {
	const newPost = e.detail.post;
	if (anyFilterMatches(newPost)) {
		newPost.classList.add('hidden');
	}
	if (e.detail.hover) { return; }
	const menu = newPost.querySelector('.postmenu');
	menu.value = '';
	menu.addEventListener('change', postMenuChange, false);
});

window.addEventListener('updatePostMessage', function(e) {
	const newPost = e.detail.post;
	if (anyFilterMatches(newPost)) {
		newPost.classList.add('hidden');
	}
});

window.addEventListener('settingsReady', function(e) {

	filtersTable = document.getElementById('advancedfilters');
	updateFiltersTable();

	const filtersForm = document.getElementById('filter-form');
	filtersForm.addEventListener('submit', (e) => {
		e.preventDefault();
		const isRegex = filtersForm.elements.regex.checked;
		const type = `${filtersForm.elements.type.value}${isRegex ? 'r' : ''}`;
		const val = isRegex ? new RegExp(filtersForm.elements.value.value, 'i') : filtersForm.elements.value.value;
		console.log('adding filter', type, val);
		toggleFilter(type, val, true);
	})

	const filterClearButton = document.getElementById('filters-clear');
	const clearFilters = () => {
		single = new Set(),
		fid = new Set(),
		fname = new Set(),
		fsub = new Set(),
		fmsg = new Set(),
		ftrip = new Set(),
		fnamer = [],
		ftripr = [],
		fsubr = [],
		fmsgr = [],
		updateFiltersTable();
		togglePostsHidden(document.querySelectorAll(`.${isCatalog ? 'catalog-tile': 'post-container' }`), false);
		updateSavedFilters();
		console.log('cleared hidden posts');
	}
	filterClearButton.addEventListener('click', clearFilters, false);

});