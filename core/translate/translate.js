(function() {
	'use strict';

	const tbody = document.getElementById('translations-body');
	const translatorUrl = tbody.hasAttribute('data-url') ? tbody.dataset.url : false;
	const langs = document.querySelectorAll('input[name="langs[]"]');

	tbody.addEventListener('focusin', function(event) {
		if(event.target.tagName == 'INPUT') {
			event.preventDefault();
			const index = event.target.parentElement.cellIndex;
			const rulers = document.getElementsByClassName('ruler');
			if(rulers.length > 0) {
				// Update CSS
				for(var i=0, iMax=rulers[0].cells.length; i<iMax; i++) {
					if(i == index) {
						rulers[0].cells[i].classList.add('active');
					} else {
						rulers[0].cells[i].classList.remove('active');
					}
				}
				for(i=0, iMax=langs.length; i<iMax; i++) {
					const parent = langs[i].parentElement;
					if(i == index - 2) {
						parent.classList.add('active');
					} else {
						parent.classList.remove('active');
					}
				}
			}
		}
	});

	if(typeof translatorUrl == 'string') {
		tbody.addEventListener('click', function(event) {
			if(event.target.tagName == 'INPUT' && event.target.value.trim().length == 0 && !event.target.hasAttribute('data-extra')) {
				event.preventDefault();
				// On traduit une cellule du tableau si elle est vide
				const targetLang = event.target.name.replace(/^(\w+).*/, '$1');
				const srcLang = langs[0].value;
				const name = event.target.name.replace(/.*(\[\d+\])$/, srcLang + '$1');
				const idiom = event.target.form.elements[name].value;
				if(confirm('Translate :\n' + idiom)) {
					const input = event.target
					input.parentElement.classList.add('awaiting');
					const uri = translatorUrl.replace(/#SL#/, srcLang).replace(/#TL#/, targetLang).replace(/#Q#/, encodeURIComponent(idiom));
					const xhr = new XMLHttpRequest();
					xhr.target = targetLang;
					xhr.onload = function() {
						if(this.getResponseHeader('Content-Type').startsWith('application/json')) {
							const datas = JSON.parse(this.responseText);
							if(typeof datas[0][0] == 'object') {
								console.log(this.target);
								for(var i=0, iMax=2; i<iMax; i++) {
									console.log(datas[0][0][i]);
								}
								input.value = datas[0][0][0];
								input.parentElement.classList.remove('awaiting');
								input.parentElement.classList.remove('missing');
								input.parentElement.classList.add('new');
								const chks = input.form.elements['langs[]'];
								for(var i=0, iMax = chks.length; i<iMax; i++) {
									if(chks[i].value == this.target) {
										chks[i].checked = true;
										break;
									}
								}
								return;
							}
						}
						console.error('Bad Content-Type');
					};
					xhr.open('GET', uri);
					xhr.send();
				}
			}
		});
	} else {
		console.error('Url for translations is missing');
	}

	// deactive tous les inputs sauf pour les langues choisies
	document.forms[1].addEventListener('submit', function(event) {
		const langNodes = event.target.elements['langs[]'];
		var checked = false;
		const noSubmits = [];
		for(var i=0, iMax=langNodes.length; i<iMax; i++) {
			if(langNodes[i].checked) {
				checked = true;
			} else {
				noSubmits.push(langNodes[i].value);
			}
		}

		if(!checked) {
			alert('Aucune langue sélectionée');
			event.preventDefault();
			return false;
		} else {
			noSubmits.forEach(function(lang) {
				const els = document.querySelectorAll('#translations-body input[name^="' + lang + '["]');
				for(var j=0, jMax=els.length; j<jMax; j++) {
					els[j].disabled = true;
				}
			});
		}
	});

	// Pour ajout d'une nouvelle langue
	const newLang = document.getElementById('id_new');
	if(newLang != null) {
		var updated = false;
		newLang.addEventListener('focus', function(event) {
			if(!updated) {
				event.preventDefault();
				const script1 = document.createElement('SCRIPT');
				script1.src = 'https://ssl.gstatic.com/inputtools/js/ln/17/' + navigator.language.replace(/-.*$/, '') + '.js';
				script1.onload = function(params) {
					const select = event.target;
					select.textContent = '';

					const excludes = select.dataset.excludes.split('|');
					/*
					const theList = window.LanguageDisplays.nativeNames;
					for(var i in theList) {
						const option = document.createElement('OPTION');
						option.value = i;
						option.textContent = theList[i];
						select.appendChild(option);
					}
					*/

					window.LanguageDisplays.localNames.forEach(function(value) {
						const parts = value.split(':');
						if(excludes.indexOf(parts[1]) < 0) {
							const option = document.createElement('OPTION');
							option.value = parts[1];
							option.textContent = parts[0] + ' (' + parts[1] + ')';
							select.appendChild(option);
						}
					});

					updated = true;
				}
				document.head.appendChild(script1);
			}
		}) ;
	}
})();
