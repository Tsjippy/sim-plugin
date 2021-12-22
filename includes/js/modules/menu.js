export function body_scrolling(type){
	//don't do anything on homepage
	if(document.querySelector('body').classList.contains('home')) return;
	
	if(type == 'disable'){
		//disable scrolling of the body
		document.querySelector("body").style.overflow = 'hidden';

		var menu = document.querySelector("#masthead");
		menu.style.overflowY	= 'scroll';
		menu.style.top			= '0px';
		menu.style.left			= '0';
		menu.style.right		= '0';
		menu.style.bottom		= '0';
		menu.style.position		= 'absolute';
	}else{
		//disable scrolling of the body
		document.querySelector("body").style.overflow = '';

		var menu = document.querySelector("#masthead");
		menu.style.overflowY	= '';
		menu.style.top			= '';
		menu.style.left			= '';
		menu.style.right		= '';
		menu.style.bottom		= '';
		menu.style.position		= '';
	}
}

function click_listener(target){
    //we clicked the menu
    if(target.closest('.menu-toggle') != null){
        if(document.querySelector('.menu-toggle').getAttribute("aria-expanded")=="true"){
            body_scrolling('disable');
        }else{
            body_scrolling('enable');
        }
    }

    //if clicked outside the menu, then close the menu
    if(
        document.querySelector('.menu-toggle').getAttribute("aria-expanded")=="true" &&
        target.closest('#site-navigation') == null && 
        target.closest('#mobile-menu-control-wrapper') == null
    ){
        document.querySelector('#mobile-menu-control-wrapper').classList.remove("toggled");
        document.querySelector('.menu-toggle').setAttribute("aria-expanded", 'false');
        document.querySelector('#site-navigation').classList.remove("toggled");
        body_scrolling('enable');
    }
}

clickListener.push( click_listener);