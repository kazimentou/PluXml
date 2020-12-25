'use strict';

(function() {
	'use strict';

	const form = document.querySelector('form[enctype="multipart/form-data"]');
	if(form == null) {
		return;
	}

	const inputFiles = form.querySelector('input[type="file"]');
	if(inputFiles == null) {
		return;
	}

	const filesList = document.getElementById('files_list');
	if(filesList == null || !filesList.hasAttribute('data-limits')) {
		console.error('<input type="file" data-limits="..." /> is missing');
		return;
	}

	const limits = filesList.dataset.limits.split(';').map(function(value) {
		return parseInt(value);
	});

	const queryFiles = new Array();
	const ACCEPT = /^image\//i;

	form.onsubmit = function(event) {
		event.preventDefault();
		if(queryFiles.length == 0) {
			return;
		}
		const formData = new FormData(form);
		const name = inputFiles.name;
		queryFiles.forEach(function(item) {
			formData.append(name, item);
		});
		const request = new XMLHttpRequest();
		request.onload = function() {
			if(request.status == 200) {
				console.log('ok');
				window.location.href = form.action;
			} else {
				console.error(request.status, request.responseText);
			}
		};
		request.open('POST', form.action);
		request.send(formData);
	}

	function formatBytes(value) {
		for(var i=0, UNITS = [' bytes', ' KB', ' MB', ' GB', ' TB'], iMax=UNITS.length, r=value ; i<iMax; i++) {
			if(r < 1024) {
				return r.toFixed(2) + UNITS[i];
				break;
			}
			r /= 1024;
		}
		return
	}

	function filesHandler(files) {
		if(typeof files == 'object' && typeof files.length == 'number') {
			for(var i=0, iMax=files.length; i<iMax; i++) {
				queryFiles.push(files[i]);
			}
		}
		const batch = {
			count: queryFiles.length,
			size: 0,
		};
		if(queryFiles.length > 0) {
			filesList.classList.remove('awaiting');
		} else {
			filesList.classList.add('awaiting');
		}
		filesList.textContent = '';
		var isBigFile = false; // checks size for each file
		for(var i=0; i<batch.count; i++) {
			const file1 = queryFiles[i];
			const wrapper = document.createElement('LI');
			if(ACCEPT.test(file1.type)) {
				const img = document.createElement('IMG');
				wrapper.appendChild(img);
				const reader = new FileReader();
			    reader.onload = function(e) {
					img.src = e.target.result;
				};
			    reader.readAsDataURL(file1);
			} else {
				const el = document.createElement('P');
				el.innerHTML = '&nbsp;';
				wrapper.appendChild(el);
			}
			const name = document.createElement('P');
			name.textContent = file1.name;
			wrapper.appendChild(name);
			const size = document.createElement('P');
			size.textContent = formatBytes(file1.size);
			batch.size += file1.size;
			if(file1.size > limits[2]) {
				wrapper.classList.add('big-size');
				isBigFile = true;
			}
			wrapper.appendChild(size);
			const deleteBtn = document.createElement('BUTTON');
			deleteBtn.textContent = 'X';
			deleteBtn.dataset.id = i;
			wrapper.appendChild(deleteBtn);
			filesList.appendChild(wrapper);
		}

		['count', 'size'].forEach(function(field) {
			const el = document.getElementById('batch-' + field);
			if(el != null) {
				el.textContent = (field != 'size') ? batch[field] : formatBytes(batch[field]);
			}
		});

		document.getElementById('btn_upload').disabled = (isBigFile || batch.count == 0 || batch.count > limits[0] || batch.size > limits[1]);
	}

	inputFiles.addEventListener('change', function(event) {
		filesHandler(this.files);
		inputFiles.value = '';
	}, false);

	function dragHandler(event) {
		if(typeof event.target.tagName == undefined) {
			return;
		}

		if(event.target.tagName != 'INPUT') {
			event.stopPropagation();
			event.preventDefault();
		}

		switch(event.type) {
			case 'dragenter':
			case 'dragover':
				event.target.classList.add('active');
				break;
			case 'drop':
				if(event.target.tagName != 'INPUT') {
					const dt = event.dataTransfer;
					filesHandler(dt.files);
				}
				// no break !
			default:
				event.target.classList.remove('active');
		}
	}

	['dragenter', 'dragover', 'dragleave', 'dragexit', 'dragend', 'drop'].forEach(function(eventType) {
		filesList.addEventListener(eventType, dragHandler, false);
		inputFiles.addEventListener(eventType, dragHandler);
	});

	filesList.addEventListener('click', function(event) {
		if(event.target.hasAttribute('data-id')) {
			event.preventDefault();
			const trash = queryFiles.splice(parseInt(event.target.dataset.id), 1);
			filesHandler();
		}
	});

	// Move the medias

	const treeview = document.querySelector('.treeview > ul');
	const formMedias = document.querySelector('form[data-chk]');
	if(treeview != null && formMedias != null) {
		const selection = formMedias.elements.selection;
		selection.addEventListener('change', function(event) {
			if(event.target.value == 'move') {
				treeview.classList.add('move');
			} else {
				treeview.classList.remove('move');
			}
		});
		treeview.addEventListener('click', function(event) {
			if(typeof event.target.href == 'string') {
				if(selection.value == 'move') {
					event.preventDefault();
					formMedias.elements.folder.value = event.target.href.replace(/^.*\bpath=/, '');
					const moves = treeview.querySelectorAll('.active');
					for(var i=0,iMax=moves.length; i<iMax; i++) {
						moves[i].classList.remove('active');
					}
					event.target.parentElement.classList.add('active');
					const btn = formMedias.querySelector('button[data-select]');

					if(btn != null) {
						btn.click();
					}
				}
			}
		});
	}

})();
