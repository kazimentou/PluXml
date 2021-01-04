(function() {
	'use strict';

	const tbody = document.getElementById('translations-body');
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

	function fromGoogle(datas) {
		if(typeof datas[0][0] == 'object') {
			console.log('Translated from Google');
			for(var i=0, iMax=2; i<iMax; i++) {
				console.log(datas[0][0][i]);
			}
			return datas[0][0][0].replace(/% (\w)/, '%$1');
		}

		return '';
	}

	function fromMymemory(datas) {
		if(datas.quotaFinished) {
			alert('Quota finished from MyMemory');
		} else if(datas.responseStatus != 200) {
			console.error('response status ' + datas.responseStatus + ' from MyMemory');
		} else if(typeof datas.responseData.translatedText == 'string') {
			if(datas.matches.length > 1) {
				console.log('Translated from MyMemory');
				datas.matches.forEach(function(item) {
					console.log(item.segment + ': ' + item.translation);
				});
			}
			return datas.responseData.translatedText;
		}

		return '';
	}

	// traduit une cellule vide du tableau
	if(typeof localStorage == 'object') {
		const KEY = 'translator';
		const translator = localStorage.getItem(KEY);
		if(translator != null) {
			document.forms.translation_form.elements.translator.value = translator;
		}

		const els = document.getElementsByClassName('translator-motor');
		if(els.length > 0) {
			els[0].addEventListener('change', function(event) {
				if(event.target.name == 'translator') {
					localStorage.setItem(KEY, event.target.value);
				}
			});
		}
	}

	tbody.addEventListener('click', function(event) {
		if(event.target.tagName == 'INPUT' && event.target.value.trim().length == 0 && !event.target.hasAttribute('data-extra')) {
			// On traduit une cellule du tableau si elle est vide
			event.preventDefault();

			const translatorMotor = event.target.form.elements.translator.value;
			if(translatorMotor == '') {
				alert(tbody.dataset.lang);
				return;
			}

			if(!tbody.hasAttribute('data-' + translatorMotor)) {
				console.error('Attribute data-' + translatorMotor + ' is missing in tbody element');
				return;
			}

			const translatorUrl = tbody.dataset[translatorMotor];
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
				xhr.translator = translatorMotor;
				xhr.onload = function() {
					if(this.getResponseHeader('Content-Type').startsWith('application/json')) {
						const datas = JSON.parse(this.responseText);
						switch(this.translator) {
							case 'google':
								input.value = fromGoogle(datas);
								break;
							case 'mymemory':
								input.value = fromMymemory(datas);
								break;
						}

						if(input.value != '') {
							input.parentElement.classList.remove('awaiting');
							input.parentElement.classList.add('new');
							const chks = input.form.elements['langs[]'];
							for(var i=0, iMax = chks.length; i<iMax; i++) {
								if(chks[i].value == this.target) {
									chks[i].checked = true;
									break;
								}
							}
						} else {
							alert('Error. See console.log()');
						}

						input.parentElement.classList.remove('awaiting');
						return;
					}

					console.error('Bad Content-Type');
				};
				xhr.open('GET', uri);
				xhr.send();
			}
		}
	});

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
	function emojiFlag(lang) {
		// https://emojipedia.org/flags/
		// https://en.wikipedia.org/wiki/List_of_ISO_639-2_codes
		// https://iso639-3.sil.org/sites/iso639-3/files/downloads/iso-639-3.tab
		const lang2flag = {
			af: 'ZA',
			zu: 'ZA',
			xh: 'ZA',
			en: 'GB',
			oc: 'FR', // '🏴frocc'
			be: 'BY', // Biélorussie
			ca: 'ES', // '🏴󠁥󠁳󠁣󠁴󠁿'󠁥󠁳󠁣󠁴󠁿 '🏴esct' Catalogne
			ga: 'IE',
			da: 'DK',
			ka: 'GE',
			gl: 'ES', // '🏴esga',
			cy: '🏴󠁧󠁢󠁷󠁬󠁳󠁿', // Pays de Galles Wales
			gd: '🏴󠁧󠁢󠁳󠁣󠁴󠁿', // '🏴gbsct', Scotland
			sq: 'AL',
			eu: 'ES', // '🏴espv', // Euskadi Pays Basque
			rom: 'RO',
			hy: 'AM',
			ko: 'KR',
			nn: 'NO',
			nb: 'NO',
			he: 'IL',
			ja: 'JP',
			el: 'GR',
			ff: 'NE', // '🇪🇭' Peul
			haw: '🏴󠁵󠁳󠁨󠁩󠁿', // '🏴ushi󠁿󠁿 U+E007F Hawaii
			hi: 'IN',
			co: '🏴󠁦󠁲󠁣󠁯󠁲󠁿',
			fil: 'PH',
			tl: 'PH',
			fy: 'NL',
			su: 'SD',
			sw: 'KE',
			te:'IN',
			yi: 'DE',
			lb: 'LU',
			uk: 'UA',
			vi: 'VN',
			bo: 'CN', // '🏴󠁣󠁮󠀵󠀴󠁿',
			fa: 'IR',
			nv: 'US',
			kk: 'KZ',
			lo: 'LA',
			iu: 'CA',
			ny: 'MW',
			ccp: 'BD',
			ceb: 'PH',
			crk: 'CA',
			jv: 'ID',
			jw: 'ID',
			ku: 'TR',
			cs: 'CZ',
			yo : 'NG',
			ti: 'ER',
			ckb: 'IQ',
			uzs: 'UZ',
			ur: 'PK',
			mi: 'MG',
			ne: 'NP',
			or: 'IN',
			ar: 'SA',
			'pt-PT': 'PT',
			'pt-BR': 'BR',
			'zh-HK': 'HK',
			'zh-Hant': 'TW',
			'mni-Mtei': 'IN',
			rhg: 'MM IN'
		}
		var s = '';
		if(lang in lang2flag) {
			s = lang2flag[lang];
			if(s.substr(0, 2) == '🏴') {
				return s;
			}
		} else if(/^(zh-|hmn|lis)/.test(lang)) {
			// Languages in China
			s = 'CN';
		} else if(/^(mez|oj|one|osa|see|chr|ug)/.test(lang)) {
			// Languages in USA
			s = 'US';
		} else {
			s = lang.toUpperCase();
		}
		var result = '';
		for(var i=0, iMax=s.length; i<iMax; i++) {
			result += (s.charCodeAt(i) != 32) ? '&#x1f1' + (s.charCodeAt(i) + 165).toString(16) + ';' : ' ';
		}
		return result;
	}

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

					window.LanguageDisplays.localNames.forEach(function(value) {
						const parts = value.split(':');
						if(true || excludes.indexOf(parts[1]) < 0) {
							const option = document.createElement('OPTION');
							option.value = parts[1];
							option.innerHTML = emojiFlag(parts[1]) +  ' ' + parts[0] + ' (' + parts[1] + ')';
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
