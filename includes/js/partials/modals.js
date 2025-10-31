export function showModal(modal){
    if(typeof(modal) == 'string'){
        modal = document.getElementById(modal + "-modal");
    }
    
    if(modal != null){
        // Prevent main page scrolling
        document.body.style.top			= `-${window.scrollY}px`;
        document.body.style.position 	= 'fixed';

        let prim						= document.getElementById('primary');
        if(prim != null){
            prim.style.zIndex			= '';
        }

        modal.classList.remove('hidden');

        modal.style.display	= 'block';
    }	
}

export function hideModals(){
    let modals	= document.querySelectorAll('.modal:not(.hidden)');
    if(modals.length != 0){
        modals.forEach(modal=>{
            modal.classList.add('hidden');
            modal.style.removeProperty('display');
            
            const event = new Event('modalclosed');
            modal.dispatchEvent(event);
        });

        // Turn main page scrolling on again
        const scrollY					= document.body.style.top;
        document.body.style.position 	= '';
        document.body.style.top 		= '';
        window.scrollTo(0, parseInt(scrollY || '0') * -1);
        
        /* 		let prim						= document.getElementById('primary');
        if(prim != null){
            prim.style.zIndex			= 1;
        } */
    }
}