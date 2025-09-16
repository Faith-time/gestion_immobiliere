import { tns } from 'tiny-slider/src/tiny-slider'
import AOS from 'aos'
import 'aos/dist/aos.css'

document.addEventListener('DOMContentLoaded', () => {
	'use strict'

	AOS.init({
		duration: 800,
		easing: 'slide',
		once: true,
	});

	const preloader = () => {
		const loader = document.querySelector('.loader');
		const overlay = document.getElementById('overlayer');

		const fadeOut = (el) => {
			if (!el) return;
			el.style.opacity = 1;
			(function fade() {
				if ((el.style.opacity -= 0.1) < 0) {
					el.style.display = 'none';
				} else {
					requestAnimationFrame(fade);
				}
			})();
		};

		setTimeout(() => {
			fadeOut(loader);
			fadeOut(overlay);
		}, 200);
	};
	preloader();

	const initSliders = () => {
		const sliderConfigs = [
			{
				selector: '.hero-slide',
				config: {
					container: '.hero-slide',
					mode: 'carousel',
					speed: 700,
					autoplay: true,
					controls: false,
					nav: false,
					autoplayButtonOutput: false,
					controlsContainer: '#hero-nav',
				},
			},
			{
				selector: '.img-property-slide',
				config: {
					container: '.img-property-slide',
					mode: 'carousel',
					speed: 700,
					items: 1,
					gutter: 30,
					autoplay: true,
					controls: false,
					nav: true,
					autoplayButtonOutput: false,
				},
			},
			{
				selector: '.property-slider',
				config: {
					container: '.property-slider',
					mode: 'carousel',
					speed: 700,
					gutter: 30,
					items: 3,
					autoplay: true,
					autoplayButtonOutput: false,
					controlsContainer: '#property-nav',
					responsive: {
						0: { items: 1 },
						700: { items: 2 },
						900: { items: 3 },
					},
				},
			},
			{
				selector: '.testimonial-slider',
				config: {
					container: '.testimonial-slider',
					mode: 'carousel',
					speed: 700,
					items: 3,
					gutter: 50,
					autoplay: true,
					autoplayButtonOutput: false,
					controlsContainer: '#testimonial-nav',
					responsive: {
						0: { items: 1 },
						700: { items: 2 },
						900: { items: 3 },
					},
				},
			},
		];

		sliderConfigs.forEach(({ selector, config }) => {
			if (document.querySelector(selector)) {
				tns(config);
			}
		});
	};
	initSliders();
});
