export function changeUrl(target, secondTab=''){
    let newParam	= target.dataset.paramVal;
    let hash		= target.dataset.hash;
    const url 		= new URL(window.location);

    //Change the url params
    if(target.closest('.tabcontent') == null || target.parentNode.classList.contains('modal-content')){
        //Add query-arg if it is a main tab
        url.searchParams.set('main-tab', newParam);
        url.searchParams.delete('second-tab');
    }else{
        url.searchParams.set('second-tab', newParam);
    }

    if(secondTab != ''){
        url.searchParams.set('second-tab', secondTab);
    }
    
    window.history.pushState({}, '', url);

    if(hash != null){
        window.location.hash	= hash;
    }else{
        window.location.hash	= '';
    }

    // switch tab when clicking on a change url link
    if(target.tagName == 'A'){
        switchTab();
    }
}

export function switchTab(event=null){
    let params = new Proxy(new URLSearchParams(window.location.search), {
        get: (searchParams, prop) => searchParams.get(prop),
    });
    
    let mainTab 	= params['main-tab'];
    let lastTab		= '';

    if(mainTab != null){
        //find the tab and display it
        document.querySelectorAll(`[data-param-val="${mainTab}"]:not(.active)`).forEach(tabbutton=>{
            //only process non-modal tabs
            if(tabbutton.closest('.modal') == null){
                let result	= displayTab(tabbutton);
                if(result){
                    lastTab	= result;
                }
            }
        });
    }

    let secondTab = params.second_tab;
    if(secondTab != null){
        //find the tab and display it
        document.querySelectorAll(`[data-param-val="${secondTab}"]:not(.active)`).forEach(tabbutton=>{
            displayTab(tabbutton);
        });
    }
}

export function displayTab(tabButton){

    //remove all existing highlights
    document.querySelectorAll('.highlight').forEach(el=>el.classList.remove('highlight'));

    let tab;
    // Get content area
    if(tabButton.dataset.target == undefined){
        tab = document.querySelector('#'+tabButton.dataset.paramVal.trim());
    }else{
        tab = tabButton.closest('div:not(.tablink-wrapper)').querySelector('#'+tabButton.dataset.target);
    }
    
    if(tab != null){
        tab.classList.remove('hidden');

        if(tabButton.tagName != 'A'){
            //Mark the other tabbuttons as inactive
            tabButton.parentNode.querySelectorAll(`:scope > .active:not(#${tabButton.id})`).forEach(child=>{
                //Make inactive
                child.classList.remove("active");
                    
                //Hide the tab
                var childTab	= child.closest('div:not(.tablink-wrapper)').querySelector('#'+child.dataset.target);
                if(childTab == null){
                    console.error('Tab to hide not found:');
                    console.error(child.closest('div:not(.tablink-wrapper)'));
                    console.error('#'+child.dataset.target);
                }else{
                    childTab.classList.add('hidden');
                }
            });
            
            //Mark the tabbutton as active
            tabButton.classList.add("active");
        }

        //scroll to field
        if (window.location.hash) {
            var hash 		= window.location.hash.replace('#', '');

            var hashField	= tab.querySelector(`[name^="${hash}"]`);
        
            if(hashField != null){
                hashField.scrollIntoView({block: "center"});

                var el			= hashField.closest('.input-wrapper');
                if(el != null){
                    hashField.closest('.input-wrapper').classList.add('highlight');
                }
                hashField.classList.add('highlight');
                hashField.focus();
            }
        }

        // position any tables on this tab, as they can only be positioned when visible
        if(typeof(SimTableFunctions) != 'undefined'){
            SimTableFunctions.positionTable();
        }

        return tab;
    }else{
        return false;
    }
}