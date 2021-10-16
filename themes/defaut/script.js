(function() {
	'use strict';

	// --------- Footnotes -----------

	const article = document.querySelector('.article[id^="post-"]');
	if(article != null) {
		const footnotes = article.querySelectorAll('a[data-footnote]');
		if(footnotes.length > 0) {
			const el = document.createElement('UL');
			el.className = 'footnotes';
			Array.from(footnotes).forEach(function(item, x) {
				const i = x+1;
				const idSrc = `footnote-${i}`;
				const idTarget = `note-${i}`;
				item.innerHTML = `<sup>(${i})</sup>`;
				item.id = idSrc
				item.href = `#${idTarget}`;
				const note = document.createElement('LI');
				note.innerHTML = `<a href="#${idSrc}">${i}. </a>${item.dataset.footnote}`;
				note.id = idTarget;
				el.appendChild(note);
				item.title = note.textContent.replace(/^\d+\./, '');
			});

			article.appendChild(el);
		}
	}

	// ------- chapters ----------

	const newPages = [...document.body.querySelectorAll('.new-page > h2')];
	if(newPages.length != 0) {
		if(newPages.length > 1) {
			// On crée une barre de navigation s'il y a plus de 1 chapitre
			var innerHTML = '';
			newPages.forEach((item, i) => {
			  const caption = item.textContent;
			  innerHTML += `<button data-page="${i}">${caption}</button>`;
			});

			// On crée la barre de navigation
			const pagination_numbers_container = document.createElement('NAV');
			pagination_numbers_container.className = 'art-nav center';
			pagination_numbers_container.innerHTML = innerHTML;
			const page0 = newPages[0].parentElement;
			page0.parentElement.insertBefore(pagination_numbers_container, page0);

			// On gére le click sur la barre de navigation
			pagination_numbers_container.addEventListener('click', (evt) => {
			  if(evt.target.hasAttribute('data-page')) {
				evt.preventDefault();
				// On affiche uniquement le chapitre demandé
				[...document.body.querySelectorAll('.new-page.active')].forEach((item) => {
				  item.classList.remove('active');
				});
				const i = parseInt(evt.target.dataset.page);
				newPages[i].parentElement.classList.add('active');
				// On met en évidence uniquement le bouton du chapitre affiché
				[...pagination_numbers_container.querySelectorAll('.active')].forEach((item) => {
				  item.classList.remove('active');
				});
				event.target.classList.add('active');
			  }
			});
		}

		// On allume sur le premier .new-page ( Fire up )
		newPages[0].parentElement.classList.add('active');
		const btn = document.body.querySelector('.art-nav button');
		if(btn != null) {
		btn.classList.add('active');
		}
	}

	// -------------- scroll to top smoothly -------------------

	const topLink = document.body.querySelector('.footer a[href$="#top"]');
	const topPage = document.getElementById('top');
	if(topLink != null && topPage != null) {
		topLink.onclick = function(event) {
			event.preventDefault();
			topPage.scrollIntoView({behavior: 'smooth'});
		}
	}

})();
