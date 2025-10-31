export function showLoader(element, replace=true, size=50, text='', returnHtml=false, inButton=false){

    if(element == null && returnHtml == false){
		return;
	}

    if(isNaN(size)){
        return false;
    }

    size            = parseInt(size);

    let factor		= size / 100;

    let wrapper		        = document.createElement('div');
    wrapper.style.height	= (factor * 110) + 'px';
    wrapper.classList.add('loader-wrapper');

    let loader		    = document.createElement('div');
    loader.style.width	= (factor * 100) + 'px';
    loader.style.height	= (factor * 100) + 'px';
    loader.classList.add('loader');

    wrapper.appendChild(loader);

    for(let i = 0; i < 8; i++){
        let dot	= document.createElement('div');
        dot.classList.add('dot');
        dot.style.width		= (factor * 16) + 'px';
        dot.style.height	= (factor * 16) + 'px';	

        if(inButton){
            dot.style.border= '1px solid white';
        }

        switch (i) {
            case 0:
                dot.style.top				= (factor * 3) + 'px';
                dot.style.left				= (factor * 44) + 'px';
                break;
            case 1:
                dot.style.top				= (factor * 15) + 'px';
                dot.style.left				= (factor * 73) + 'px';
                dot.style.animationDelay	= '0.15s';	
                break;
            case 2:
                dot.style.top				= (factor * 44) + 'px';
                dot.style.left				= (factor * 87) + 'px';
                dot.style.animationDelay	= '0.3s';
                break;
            case 3:
                dot.style.top				= (factor * 73) + 'px';
                dot.style.left				= (factor * 73) + 'px';
                dot.style.animationDelay	= '0.45s';
                break;
            case 4:
                dot.style.top				= (factor * 85) + 'px';
                dot.style.left				= (factor * 44) + 'px';
                dot.style.animationDelay	= '0.6s';
                break;
            case 5:
                dot.style.top				= (factor * 73) + 'px';
                dot.style.left				= (factor * 15) + 'px';
                dot.style.animationDelay	= '0.75s';
                break;
            case 6:
                dot.style.top				= (factor * 44) + 'px';
                dot.style.left				= (factor * 1) + 'px';
                dot.style.animationDelay	= '0.9s';
                break;
            case 7:
                dot.style.top				= (factor * 15) + 'px';
                dot.style.left				= (factor * 15) + 'px';
                dot.style.animationDelay	= '1.05s';
                break;
            default:
                dot.style.top				= 0;
                dot.style.left				= 0;
                break;
        }

        loader.appendChild(dot);
    }

    let span	= document.createElement('span');
    span.classList.add('loader-text');
    span.textContent	= text;

    if(inButton){
		span.style.fontWeight 	= 'normal';
		span.style.marginLeft 	= '0px';
		span.style.marginRight 	= '10px';
        
        wrapper.prepend(span);
    }else{
        wrapper.appendChild(span);
    }

    if(returnHtml){
        return wrapper.outerHTML;
    }

    if(replace){
		element.parentNode.replaceChild(wrapper, element);
	}else{
        let el  = element.nextElementSibling;
        if(el == null){
            element.parentNode.insertAdjacentElement('beforeEnd', wrapper);
        }else{
            element.parentNode.insertBefore(wrapper, el);
        }
	}

	return wrapper;
}
