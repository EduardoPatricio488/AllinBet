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
let casinoAmbient = null;

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

const casinoAmbientProfiles = {
	coinflip: {
		bpm: 82,
		progression: [[220, 277.18, 329.63], [196, 246.94, 293.66], [174.61, 220, 261.63], [196, 246.94, 293.66]],
		arp: [659.25, 783.99, 987.77, 783.99, 739.99, 880, 1046.5, 880],
		bass: 110,
		padWave: 'sine',
		arpWave: 'triangle',
		accent: 1.0,
	},
	dice: {
		bpm: 94,
		progression: [[196, 246.94, 293.66], [220, 261.63, 329.63], [164.81, 207.65, 246.94], [196, 246.94, 293.66]],
		arp: [392, 493.88, 587.33, 659.25, 587.33, 493.88, 783.99, 659.25],
		bass: 98,
		padWave: 'triangle',
		arpWave: 'sine',
		accent: 0.9,
	},
	roulette: {
		bpm: 72,
		progression: [[164.81, 196, 246.94], [146.83, 174.61, 220], [130.81, 164.81, 196], [146.83, 174.61, 220]],
		arp: [329.63, 392, 493.88, 587.33, 493.88, 392, 659.25, 493.88],
		bass: 82.41,
		padWave: 'sine',
		arpWave: 'triangle',
		accent: 0.75,
	},
	blackjack: {
		bpm: 66,
		progression: [[130.81, 164.81, 196], [146.83, 174.61, 220], [110, 146.83, 174.61], [123.47, 164.81, 196]],
		arp: [261.63, 329.63, 392, 493.88, 392, 329.63, 440, 349.23],
		bass: 65.41,
		padWave: 'sine',
		arpWave: 'sine',
		accent: 0.62,
	},
	slots: {
		bpm: 104,
		progression: [[261.63, 329.63, 392], [293.66, 349.23, 440], [220, 277.18, 329.63], [246.94, 293.66, 369.99]],
		arp: [523.25, 659.25, 783.99, 1046.5, 783.99, 659.25, 987.77, 783.99],
		bass: 130.81,
		padWave: 'triangle',
		arpWave: 'triangle',
		accent: 1.08,
	},
	jetx: {
		bpm: 76,
		progression: [[110, 138.59, 164.81], [123.47, 155.56, 185], [130.81, 164.81, 196], [146.83, 185, 220]],
		arp: [220, 277.18, 329.63, 369.99, 440, 369.99, 493.88, 440],
		bass: 55,
		padWave: 'sine',
		arpWave: 'sawtooth',
		accent: 0.78,
	},
};

const currentCasinoGame = () => document.querySelector('[data-casino-game]')?.dataset.casinoGame || 'coinflip';

const createCasinoAmbientVoice = (context, output, {
	frequency,
	wave = 'sine',
	gainValue = 0.01,
	attack = 0.8,
	release = 1.4,
	filterFrequency = 1400,
} = {}) => {
	const oscillator = context.createOscillator();
	const filter = context.createBiquadFilter();
	const gain = context.createGain();

	oscillator.type = wave;
	oscillator.frequency.setValueAtTime(frequency, context.currentTime);
	filter.type = 'lowpass';
	filter.frequency.setValueAtTime(filterFrequency, context.currentTime);
	filter.Q.setValueAtTime(0.35, context.currentTime);
	gain.gain.setValueAtTime(0.0001, context.currentTime);
	gain.gain.exponentialRampToValueAtTime(Math.max(0.0001, gainValue), context.currentTime + attack);

	oscillator.connect(filter);
	filter.connect(gain);
	gain.connect(output);
	oscillator.start();

	return { oscillator, gain, release };
};

const playCasinoAmbientNote = (context, output, frequency, duration, volume, wave = 'triangle') => {
	const startAt = context.currentTime;
	const oscillator = context.createOscillator();
	const filter = context.createBiquadFilter();
	const gain = context.createGain();

	oscillator.type = wave;
	oscillator.frequency.setValueAtTime(frequency, startAt);
	filter.type = 'lowpass';
	filter.frequency.setValueAtTime(Math.min(3600, frequency * 5), startAt);
	filter.Q.setValueAtTime(0.4, startAt);
	gain.gain.setValueAtTime(0.0001, startAt);
	gain.gain.exponentialRampToValueAtTime(Math.max(0.0001, volume), startAt + 0.025);
	gain.gain.exponentialRampToValueAtTime(0.0001, startAt + duration);

	oscillator.connect(filter);
	filter.connect(gain);
	gain.connect(output);
	oscillator.start(startAt);
	oscillator.stop(startAt + duration + 0.04);
};

const startCasinoAmbient = () => {
	const context = getCasinoAudioContext();
	if (! context) return;

	const game = currentCasinoGame();
	const profile = casinoAmbientProfiles[game] || casinoAmbientProfiles.coinflip;

	if (casinoAmbient && casinoAmbient.profile === game) return;
	if (casinoAmbient) stopCasinoAmbient();

	const master = context.createGain();
	const compressor = context.createDynamicsCompressor();
	const lowpass = context.createBiquadFilter();

	master.gain.setValueAtTime(0.0001, context.currentTime);
	master.gain.exponentialRampToValueAtTime(0.034, context.currentTime + 1.8);

	compressor.threshold.setValueAtTime(-24, context.currentTime);
	compressor.knee.setValueAtTime(18, context.currentTime);
	compressor.ratio.setValueAtTime(8, context.currentTime);
	compressor.attack.setValueAtTime(0.012, context.currentTime);
	compressor.release.setValueAtTime(0.35, context.currentTime);

	lowpass.type = 'lowpass';
	lowpass.frequency.setValueAtTime(5200, context.currentTime);
	lowpass.Q.setValueAtTime(0.25, context.currentTime);

	master.connect(compressor);
	compressor.connect(lowpass);
	lowpass.connect(context.destination);

	const voices = [];
	const bass = createCasinoAmbientVoice(context, master, {
		frequency: profile.bass,
		wave: 'sine',
		gainValue: 0.018,
		attack: 1.4,
		filterFrequency: 260,
	});
	voices.push(bass);

	const pad = [
		createCasinoAmbientVoice(context, master, {
			frequency: profile.progression[0][0],
			wave: profile.padWave,
			gainValue: 0.010,
			attack: 1.8,
			filterFrequency: 1200,
		}),
		createCasinoAmbientVoice(context, master, {
			frequency: profile.progression[0][1],
			wave: profile.padWave,
			gainValue: 0.008,
			attack: 1.9,
			filterFrequency: 1500,
		}),
		createCasinoAmbientVoice(context, master, {
			frequency: profile.progression[0][2],
			wave: 'sine',
			gainValue: 0.006,
			attack: 2.0,
			filterFrequency: 2000,
		}),
	];
	voices.push(...pad);

	const chordDurationMs = Math.round((60000 / profile.bpm) * 4);
	let chordIndex = 0;
	let arpIndex = 0;

	const advanceChord = () => {
		if (!casinoAmbient || !casinoSoundEnabled() || document.hidden) return;

		chordIndex = (chordIndex + 1) % profile.progression.length;
		const chord = profile.progression[chordIndex];

		bass.oscillator.frequency.cancelScheduledValues(context.currentTime);
		bass.oscillator.frequency.exponentialRampToValueAtTime(chord[0] / 2, context.currentTime + 0.9);

		pad.forEach((voice, index) => {
			voice.oscillator.frequency.cancelScheduledValues(context.currentTime);
			voice.oscillator.frequency.exponentialRampToValueAtTime(chord[index], context.currentTime + 1.1);
		});

		casinoAmbient.chordTimer = window.setTimeout(advanceChord, chordDurationMs);
	};

	const scheduleArpeggio = () => {
		if (!casinoAmbient || !casinoSoundEnabled() || document.hidden) return;

		const frequency = profile.arp[arpIndex % profile.arp.length];
		const volume = 0.0048 * profile.accent;
		playCasinoAmbientNote(context, master, frequency, 0.42, volume, profile.arpWave);
		if (arpIndex % 4 === 0) {
			playCasinoAmbientNote(context, master, frequency / 2, 0.32, volume * 0.34, 'sine');
		}
		arpIndex += 1;
		casinoAmbient.arpTimer = window.setTimeout(scheduleArpeggio, Math.round(60000 / profile.bpm / 2));
	};

	casinoAmbient = {
		profile: game,
		master,
		voices,
		chordTimer: null,
		arpTimer: null,
	};

	advanceChord();
	scheduleArpeggio();
};

const stopCasinoAmbient = () => {
	if (!casinoAmbient || !casinoAudioContext) return;

	const ambient = casinoAmbient;
	const now = casinoAudioContext.currentTime;

	ambient.master.gain.cancelScheduledValues(now);
	ambient.master.gain.setValueAtTime(Math.max(0.0001, ambient.master.gain.value), now);
	ambient.master.gain.exponentialRampToValueAtTime(0.0001, now + 0.55);

	window.clearTimeout(ambient.chordTimer);
	window.clearTimeout(ambient.arpTimer);

	window.setTimeout(() => {
		ambient.voices.forEach(({ oscillator }) => {
			try { oscillator.stop(); } catch {}
		});
	}, 650);

	casinoAmbient = null;
};

const syncCasinoAmbient = () => {
	if (casinoSoundEnabled() && ! document.hidden) {
		startCasinoAmbient();
	} else {
		stopCasinoAmbient();
	}
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
		startCasinoAmbient();
	} else {
		stopCasinoAmbient();
	}

	updateCasinoSoundControls();
	window.dispatchEvent(new CustomEvent('allinbet:casino-sound-preference', { detail: { enabled } }));
});

document.addEventListener('click', (event) => {
	if (! casinoSoundEnabled()) return;

	startCasinoAmbient();

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
document.addEventListener('DOMContentLoaded', syncCasinoAmbient);
document.addEventListener('livewire:navigated', updateCasinoSoundControls);
document.addEventListener('livewire:navigated', setupCasinoSlotSounds);
document.addEventListener('livewire:navigated', syncCasinoAmbient);
document.addEventListener('visibilitychange', syncCasinoAmbient);

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
