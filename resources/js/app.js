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

const casinoNoise = (duration = 0.04, volume = 0.012, delay = 0, filterFrequency = 3200) => {
	const context = getCasinoAudioContext();
	if (! context) return;

	const length = Math.max(1, Math.floor(context.sampleRate * duration));
	const buffer = context.createBuffer(1, length, context.sampleRate);
	const data = buffer.getChannelData(0);

	for (let i = 0; i < length; i += 1) {
		data[i] = (Math.random() * 2 - 1) * (1 - (i / length));
	}

	const source = context.createBufferSource();
	const filter = context.createBiquadFilter();
	const gain = context.createGain();
	const start = context.currentTime + delay;

	filter.type = 'bandpass';
	filter.frequency.setValueAtTime(filterFrequency, start);
	filter.Q.setValueAtTime(1.1, start);
	gain.gain.setValueAtTime(0.0001, start);
	gain.gain.exponentialRampToValueAtTime(volume, start + 0.006);
	gain.gain.exponentialRampToValueAtTime(0.0001, start + duration);

	source.buffer = buffer;
	source.connect(filter);
	filter.connect(gain);
	gain.connect(context.destination);
	source.start(start);
	source.stop(start + duration + 0.02);
};

const casinoSound = {
	click() {
		casinoTone(620, 0.045, 'square', 0.018);
		casinoTone(900, 0.05, 'triangle', 0.014, 0.025);
	},
	charge() {
		casinoTone(420, 0.06, 'triangle', 0.018);
		casinoTone(560, 0.07, 'triangle', 0.02, 0.07);
		casinoTone(720, 0.09, 'sine', 0.022, 0.15);
		casinoTone(960, 0.1, 'sine', 0.018, 0.25);
	},
	spin() {
		for (let i = 0; i < 16; i += 1) {
			const delay = i * 0.105;
			const frequency = 170 + (i * 24);
			casinoTone(frequency, 0.055, 'sawtooth', 0.013 + (i * 0.0007), delay);
			casinoNoise(0.018, 0.008, delay, 2600 + (i * 70));
		}
		casinoTone(560, 0.09, 'triangle', 0.028, 1.72);
	},
	stop(index = 0) {
		const base = 260 + ((index % 3) * 95);
		casinoNoise(0.03, 0.014, 0, 2100);
		casinoTone(base, 0.07, 'triangle', 0.032);
		casinoTone(base * 1.5, 0.09, 'triangle', 0.022, 0.055);
	},
	coinflip() {
		casinoTone(660, 0.06, 'triangle', 0.026);
		casinoTone(880, 0.07, 'triangle', 0.024, 0.08);
		casinoTone(1175, 0.1, 'sine', 0.018, 0.16);
	},
	dice() {
		for (let i = 0; i < 7; i += 1) {
			const delay = i * 0.085;
			casinoNoise(0.035, 0.014, delay, 1800 + (i * 140));
			casinoTone(260 + (i * 45), 0.05, 'square', 0.012, delay);
		}
		casinoTone(690, 0.11, 'triangle', 0.035, 0.7);
	},
	roulette() {
		for (let i = 0; i < 16; i += 1) {
			const delay = i * 0.07;
			const frequency = 180 + (i * 28);
			casinoTone(frequency, 0.045, 'triangle', 0.013, delay);
			casinoNoise(0.018, 0.008, delay, 2300);
		}
		casinoTone(720, 0.12, 'sine', 0.03, 1.2);
	},
	blackjack() {
		casinoNoise(0.055, 0.015, 0, 1500);
		casinoTone(392, 0.08, 'triangle', 0.024);
		casinoTone(494, 0.08, 'triangle', 0.022, 0.075);
		casinoTone(587, 0.11, 'triangle', 0.026, 0.15);
	},
	win() {
		const notes = [523.25, 659.25, 783.99, 1046.5, 1318.5];
		notes.forEach((frequency, index) => {
			const delay = index * 0.105;
			casinoTone(frequency, index === notes.length - 1 ? 0.34 : 0.14, 'triangle', 0.038, delay);
			casinoTone(frequency * 2, 0.09, 'sine', 0.01, delay + 0.025);
		});
	},
	bigwin() {
		const notes = [392, 523.25, 659.25, 783.99, 1046.5, 1318.5, 1567.98];
		notes.forEach((frequency, index) => {
			const delay = index * 0.095;
			casinoTone(frequency, 0.18, 'triangle', 0.045, delay);
			casinoTone(frequency * 1.5, 0.12, 'sine', 0.012, delay + 0.035);
		});
		casinoTone(2093, 0.35, 'sine', 0.035, 0.72);
		casinoTone(2637, 0.42, 'triangle', 0.028, 0.82);
	},
	lose() {
		casinoTone(330, 0.12, 'sine', 0.023);
		casinoTone(247, 0.16, 'sine', 0.02, 0.12);
		casinoTone(196, 0.26, 'triangle', 0.017, 0.25);
	},
	credit() {
		casinoTone(660, 0.08, 'triangle', 0.028);
		casinoTone(880, 0.1, 'triangle', 0.03, 0.085);
		casinoTone(1320, 0.16, 'sine', 0.022, 0.19);
	},
	chip() {
		casinoNoise(0.024, 0.014, 0, 4200);
		casinoTone(720, 0.05, 'triangle', 0.024);
		casinoTone(980, 0.07, 'triangle', 0.021, 0.045);
	},
	toss() {
		for (let i = 0; i < 18; i += 1) {
			const delay = 0.07 + (i * 0.205);
			const frequency = 300 + (i * 34);
			casinoNoise(0.022, 0.008 + (i * 0.0003), delay, 2400 + (i * 80));
			casinoTone(frequency, 0.05, i % 3 === 0 ? 'sine' : 'triangle', 0.015 + (i * 0.0007), delay);
		}
		casinoTone(620, 0.08, 'triangle', 0.026, 3.62);
		casinoTone(880, 0.1, 'triangle', 0.031, 3.76);
		casinoTone(1175, 0.12, 'sine', 0.028, 3.88);
	},
	land() {
		casinoNoise(0.055, 0.018, 0, 1700);
		casinoTone(185, 0.1, 'sine', 0.035);
		casinoTone(370, 0.12, 'triangle', 0.028, 0.055);
		casinoTone(740, 0.18, 'sine', 0.024, 0.12);
	},
	launch() {
		casinoTone(220, 0.09, 'sawtooth', 0.018);
		casinoTone(330, 0.09, 'sawtooth', 0.02, 0.08);
		casinoTone(494, 0.12, 'triangle', 0.025, 0.16);
		casinoTone(659, 0.18, 'sine', 0.026, 0.27);
	},
	milestone() {
		casinoTone(660, 0.06, 'triangle', 0.02);
		casinoTone(990, 0.08, 'triangle', 0.024, 0.055);
	},
	cashout() {
		casinoTone(523.25, 0.08, 'triangle', 0.03);
		casinoTone(659.25, 0.1, 'triangle', 0.034, 0.08);
		casinoTone(987.77, 0.17, 'sine', 0.03, 0.18);
	},
	crash() {
		casinoNoise(0.08, 0.03, 0, 850);
		casinoTone(430, 0.1, 'sawtooth', 0.025);
		casinoTone(280, 0.14, 'sawtooth', 0.024, 0.1);
		casinoTone(150, 0.3, 'triangle', 0.018, 0.22);
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

	if (target.matches('.slot-spin')) return;
	if (text.includes('cara') || text.includes('coroa')) return casinoSound.chip();
	if (target.matches('.dice-primary') && text.includes('lançar')) return casinoSound.dice();
	if (target.matches('.roulette-primary') && text.includes('girar')) return casinoSound.roulette();
	if (text.includes('girar')) return casinoSound.roulette();
	if (text.includes('roleta') || text.includes('rodar')) return casinoSound.roulette();
	if (text.includes('pedir') || text.includes('parar') || text.includes('dobrar')) return casinoSound.blackjack();
	if (text.includes('dado') || text.includes('lançar')) return casinoSound.dice();
	if (text.includes('adicionar') || text.includes('resgatar')) return casinoSound.credit();

	casinoSound.click();
});

window.addEventListener('casino-sfx', (event) => {
	if (! casinoSoundEnabled()) return;

	const name = String(event.detail?.name || '');
	if (typeof casinoSound[name] === 'function') casinoSound[name]();
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
