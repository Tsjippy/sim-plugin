export function close_mobile_menu(){
	//close mobile menu
	document.querySelectorAll('#site-navigation, #mobile-menu-control-wrapper').forEach(el=>el.classList.remove('toggled'));
	document.querySelector('body').classList.remove('mobile-menu-open');
	document.querySelector("#mobile-menu-control-wrapper > button").ariaExpanded = 'false';
}