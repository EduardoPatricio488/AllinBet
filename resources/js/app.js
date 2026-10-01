const casinoSoundPreferenceKey = 'allinbet:casino:sound';

const casinoSoundEnabled = () => {
	try {
		return localStorage.getItem(casinoSoundPreferenceKey) === 'on';
	} catch {
		return false;
	}
};

const updateCasinoSoundControls = () => {
	const enabled = casinoSoundEnabled();

	document.querySelectorAll('[data-casino-sound-toggle]').forEach((button) => {
		button.setAttribute('aria-pressed', String(enabled));
		button.textContent = enabled ? 'Som: ligado' : 'Som: desligado';
	});
};

document.addEventListener('click', (event) => {
	const button = event.target.closest('[data-casino-sound-toggle]');

	if (! button) {
		return;
	}

	const enabled = ! casinoSoundEnabled();

	try {
		localStorage.setItem(casinoSoundPreferenceKey, enabled ? 'on' : 'off');
	} catch {
	}

	updateCasinoSoundControls();
	window.dispatchEvent(new CustomEvent('allinbet:casino-sound-preference', { detail: { enabled } }));
});

document.addEventListener('DOMContentLoaded', updateCasinoSoundControls);
document.addEventListener('livewire:navigated', updateCasinoSoundControls);

const animateCasinoCounter = (element) => {
	if (element.dataset.counted === 'true') {
		return;
	}

	element.dataset.counted = 'true';

	const target = Number.parseInt(element.dataset.casinoCountTo, 10);
	const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	element.textContent = String(Number.isFinite(target) ? target : 0);

	if (! reduceMotion) {
		element.classList.add('casino-count-pop');
	}
};

const setupCasinoLobbyEffects = () => {
	const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	const revealElements = document.querySelectorAll('[data-casino-reveal]');

	if (! ('IntersectionObserver' in window) || reduceMotion) {
		revealElements.forEach((element) => element.classList.add('casino-reveal', 'is-visible'));
		document.querySelectorAll('[data-casino-count-to]').forEach(animateCasinoCounter);

		return;
	}

	const revealObserver = new IntersectionObserver((entries, observer) => {
		entries.forEach((entry) => {
			if (! entry.isIntersecting) {
				return;
			}

			revealCasinoElement(entry.target);
			observer.unobserve(entry.target);
		});
	}, { threshold: 0.12 });

	revealElements.forEach((element) => {
		if (element.dataset.revealObserved === 'true') {
			return;
		}

		element.dataset.revealObserved = 'true';
		element.classList.add('casino-reveal', 'casino-reveal-pending');
		revealObserver.observe(element);

		const bounds = element.getBoundingClientRect();

		if (bounds.top < window.innerHeight && bounds.bottom > 0) {
			requestAnimationFrame(() => revealCasinoElement(element));
		}
	});

	const counterObserver = new IntersectionObserver((entries, observer) => {
		entries.forEach((entry) => {
			if (! entry.isIntersecting) {
				return;
			}

			animateCasinoCounter(entry.target);
			observer.unobserve(entry.target);
		});
	}, { threshold: 0.5 });

	document.querySelectorAll('[data-casino-count-to]').forEach((element) => {
		if (element.dataset.counted !== 'true') {
			counterObserver.observe(element);
		}
	});
};

const revealCasinoElement = (element) => {
	element.classList.add('casino-reveal', 'is-visible');
	element.classList.remove('casino-reveal-pending');
};

document.addEventListener('DOMContentLoaded', setupCasinoLobbyEffects);
document.addEventListener('livewire:navigated', setupCasinoLobbyEffects);
