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

let casinoAudioContext = null;

const getCasinoAudioContext = () => {
	if (! casinoSoundEnabled()) return null;

	try {
		casinoAudioContext ??= new (window.AudioContext || window.webkitAudioContext)();
		if (casinoAudioContext.state === 'suspended') casinoAudioContext.resume();
		return casinoAudioContext;
	} catch {
		return null;
	}
};

const casinoTone = (frequency, duration = 0.08, type = 'sine', volume = 0.045, delay = 0) => {
	const context = getCasinoAudioContext();
	if (! context) return;

	const start = context.currentTime + delay;
	const oscillator = context.createOscillator();
	const gain = context.createGain();

	oscillator.type = type;
	oscillator.frequency.setValueAtTime(frequency, start);
	gain.gain.setValueAtTime(0.0001, start);
	gain.gain.exponentialRampToValueAtTime(volume, start + 0.012);
	gain.gain.exponentialRampToValueAtTime(0.0001, start + duration);
	oscillator.connect(gain);
	gain.connect(context.destination);
	oscillator.start(start);
	oscillator.stop(start + duration + 0.02);
};

const casinoSound = {
	click() { casinoTone(520, 0.045, 'square', 0.025); },
	spin() {
		casinoTone(150, 0.09, 'sawtooth', 0.025);
		casinoTone(185, 0.09, 'sawtooth', 0.022, 0.09);
		casinoTone(220, 0.09, 'sawtooth', 0.02, 0.18);
	},
	stop(index = 0) { casinoTone(170 + (index * 45), 0.08, 'triangle', 0.035); },
	coinflip() {
		casinoTone(740, 0.07, 'triangle', 0.035);
		casinoTone(980, 0.07, 'triangle', 0.03, 0.09);
		casinoTone(740, 0.09, 'triangle', 0.035, 0.18);
	},
	dice() {
		casinoTone(300, 0.06, 'square', 0.025);
		casinoTone(420, 0.06, 'square', 0.025, 0.07);
		casinoTone(560, 0.1, 'triangle', 0.035, 0.14);
	},
	roulette() {
		for (let i = 0; i < 7; i += 1) casinoTone(180 + (i * 35), 0.055, 'triangle', 0.018, i * 0.075);
	},
	blackjack() {
		casinoTone(440, 0.07, 'triangle', 0.03);
		casinoTone(620, 0.09, 'triangle', 0.035, 0.11);
	},
	win() {
		casinoTone(523.25, 0.1, 'triangle', 0.045);
		casinoTone(659.25, 0.1, 'triangle', 0.045, 0.11);
		casinoTone(783.99, 0.14, 'triangle', 0.05, 0.22);
		casinoTone(1046.5, 0.2, 'triangle', 0.045, 0.38);
	},
	lose() {
		casinoTone(260, 0.12, 'sine', 0.025);
		casinoTone(190, 0.18, 'sine', 0.02, 0.13);
	},
	credit() {
		casinoTone(660, 0.07, 'triangle', 0.035);
		casinoTone(880, 0.11, 'triangle', 0.035, 0.09);
	},
};

document.addEventListener('click', (event) => {
	const button = event.target.closest('[data-casino-sound-toggle]');
	if (! button) return;

	const enabled = ! casinoSoundEnabled();
	try { localStorage.setItem(casinoSoundPreferenceKey, enabled ? 'on' : 'off'); } catch {}

	if (enabled) {
		casinoAudioContext = null;
		getCasinoAudioContext();
		casinoTone(660, 0.08, 'triangle', 0.03);
	}

	updateCasinoSoundControls();
	window.dispatchEvent(new CustomEvent('allinbet:casino-sound-preference', { detail: { enabled } }));
});

document.addEventListener('click', (event) => {
	if (! casinoSoundEnabled()) return;

	const target = event.target.closest('button, [role="button"]');
	if (! target || target.matches('[data-casino-sound-toggle]')) return;
	if (target.disabled || target.getAttribute('aria-disabled') === 'true') return;

	const text = (target.textContent || '').trim().toLowerCase();

	if (target.matches('.slot-spin') || text.includes('girar')) return casinoSound.spin();
	if (text.includes('cara') || text.includes('coroa') || text.includes('lançar moeda')) return casinoSound.coinflip();
	if (text.includes('roleta') || text.includes('rodar')) return casinoSound.roulette();
	if (text.includes('pedir') || text.includes('parar') || text.includes('dobrar')) return casinoSound.blackjack();
	if (text.includes('dado') || text.includes('lançar')) return casinoSound.dice();
	if (text.includes('adicionar') || text.includes('resgatar')) return casinoSound.credit();

	casinoSound.click();
});

window.addEventListener('casino-toast', (event) => {
	if (! casinoSoundEnabled()) return;
	const title = String(event.detail?.title || '').toLowerCase();
	if (title.includes('vitória') || title.includes('vitoria') || Number(event.detail?.amount || 0) > 0) casinoSound.win();
});

const setupCasinoSlotSounds = () => {
	if (! window.MutationObserver) return;

	document.querySelectorAll('.slot-machine').forEach((machine) => {
		if (machine.dataset.soundObserved === 'true') return;
		machine.dataset.soundObserved = 'true';

		const observer = new MutationObserver((mutations) => {
			if (! casinoSoundEnabled()) return;

			mutations.forEach((mutation) => {
				if (mutation.type !== 'attributes' || mutation.attributeName !== 'style') return;
				const strip = mutation.target;
				if (! strip.matches('.slot-strip')) return;
				if (strip.style.display === 'none') {
					const reel = strip.closest('.slot-reel');
					const index = reel ? [...machine.querySelectorAll('.slot-reel')].indexOf(reel) : 0;
					casinoSound.stop(index);
				}
			});
		});

		observer.observe(machine, { subtree: true, attributes: true, attributeFilter: ['style'] });
	});
};

document.addEventListener('DOMContentLoaded', updateCasinoSoundControls);
document.addEventListener('DOMContentLoaded', setupCasinoSlotSounds);
document.addEventListener('livewire:navigated', updateCasinoSoundControls);
document.addEventListener('livewire:navigated', setupCasinoSlotSounds);

const animateCasinoCounter = (element) => {
	if (element.dataset.counted === 'true') return;
	element.dataset.counted = 'true';
	const target = Number.parseInt(element.dataset.casinoCountTo, 10);
	const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	element.textContent = String(Number.isFinite(target) ? target : 0);
	if (! reduceMotion) element.classList.add('casino-count-pop');
};

const revealCasinoElement = (element) => {
	element.classList.add('casino-reveal', 'is-visible');
	element.classList.remove('casino-reveal-pending');
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
			if (! entry.isIntersecting) return;
			revealCasinoElement(entry.target);
			observer.unobserve(entry.target);
		});
	}, { threshold: 0.12 });

	revealElements.forEach((element) => {
		if (element.dataset.revealObserved === 'true') return;
		element.dataset.revealObserved = 'true';
		element.classList.add('casino-reveal', 'casino-reveal-pending');
		revealObserver.observe(element);
		const bounds = element.getBoundingClientRect();
		if (bounds.top < window.innerHeight && bounds.bottom > 0) requestAnimationFrame(() => revealCasinoElement(element));
	});

	const counterObserver = new IntersectionObserver((entries, observer) => {
		entries.forEach((entry) => {
			if (! entry.isIntersecting) return;
			animateCasinoCounter(entry.target);
			observer.unobserve(entry.target);
		});
	}, { threshold: 0.5 });

	document.querySelectorAll('[data-casino-count-to]').forEach((element) => {
		if (element.dataset.counted !== 'true') counterObserver.observe(element);
	});
};

document.addEventListener('DOMContentLoaded', setupCasinoLobbyEffects);
document.addEventListener('livewire:navigated', setupCasinoLobbyEffects);
