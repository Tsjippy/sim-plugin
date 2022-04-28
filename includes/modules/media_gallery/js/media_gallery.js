console.log('Media galery js loaded');

function showImage(index){
    //hide all containers
    document.querySelectorAll('.large-image:not(.hidden)').forEach(el=>el.classList.add('hidden'));

    //show new one
    document.querySelector('.large-image[data-index="'+index+'"]').classList.remove('hidden');
}

async function load_more(index, showfirst, skipAmount){
    var amount  = document.querySelector('#media-amount').value;
    if(amount == skipAmount) return;
    var types   = [];
    document.querySelectorAll('.media-type-selector:checked').forEach(el=>types.push(el.value));
    
    var formData	= new FormData();
    formData.append('amount', amount);
    formData.append('page', document.querySelector('#paged').value);
    formData.append('skipAmount', skipAmount);
    formData.append('types', types);
    formData.append('startIndex', index+1);

    var response    = await fetchRestApi('media_gallery/load_more_media', formData);

    document.querySelectorAll('#medialoaderwrapper:not(.hidden), .loaderwrapper:not(.hidden)').forEach(el=>el.classList.add('hidden'));

    if(!response){
        display_message('All media are loaded', 'info');
        document.getElementById('loadmoremedia').classList.add('hidden');
    }else{
        document.querySelector('.mediawrapper').insertAdjacentHTML('beforeEnd', response);

        if(showfirst){
            var el      = document.querySelector('[data-index="'+index+'"]');
            var next_el = el.nextElementSibling.nextElementSibling;

            showImage(next_el.dataset.index);
        }

        //hide the load more button if the last cell has no next button
        var cells = document.querySelectorAll('.large-image');
        if(cells[cells.length-1].querySelector('.nextbtn') == null){
            document.getElementById('loadmoremedia').classList.add('hidden');
        }
    }
}

async function mediaSearch(target){
    var amount          = document.querySelector('#media-amount').value;
    var types           = [];
    document.querySelectorAll('.media-type-selector:checked').forEach(el=>types.push(el.value));
    var searchstring    = target.closest('.mediabuttons').querySelector('.searchtext').value;
    
    var formData	= new FormData();
    formData.append('amount', amount);
    formData.append('types', types);
    formData.append('search', searchstring);

    var response    = await fetchRestApi('media_gallery/media_search', formData);
    
    if(!response){
        display_message('Nothing found', 'warning');
    }else{
        document.querySelector('.mediawrapper').innerHTML = response;
    }
}

document.querySelectorAll('.searchtext').forEach(el=>{
    el.addEventListener("keyup", function(event){
        if (event.keyCode === 13) {
            mediaSearch(event.target);
        }
    });
});

document.addEventListener('click', async ev=>{
    var target  = ev.target;
    var parent  = target.closest('.large-image');

    if(target.matches('.media-item')){
        ev.preventDefault();
		ev.stopPropagation();
        showImage(target.closest('.cell').dataset.index);
    }

    if(target.matches('.closebtn')){
        parent.classList.add('hidden');

        //stop any video's
        var iframe  = parent.querySelector( 'iframe');
        if(iframe != null){
            iframe.src  = iframe.src;
        }
    }

    if(target.matches('.prevbtn')){
        var el      = target.closest('.large-image');
        var prev_el = el.previousElementSibling.previousElementSibling;

        showImage(prev_el.dataset.index);
    }

    if(target.matches('.nextbtn')){
        var el      = target.closest('.large-image');
        var next_el = el.nextElementSibling;
        if(next_el != null){
            var next_el = next_el.nextElementSibling;
        }

        //load more
        if(next_el == null){
            document.getElementById('medialoaderwrapper').classList.remove('hidden');

            document.getElementById('paged').value = parseInt(document.getElementById('paged').value)+1;
            load_more(el.dataset.index, true);
        }else{
            showImage(next_el.dataset.index);
        }
    }

    if(target.id == 'loadmoremedia'){
        ev.preventDefault();
		ev.stopPropagation();
        //find the last image
        var media = document.querySelectorAll('.cell');

        document.getElementById('paged').value = parseInt(document.getElementById('paged').value)+1;

        load_more(media[media.length-1].firstChild.dataset.index, false);

        showLoader(target, false);
    }

    if(target.matches('.buttonwrapper .description')){
        display_message(target.dataset.description);
    }

    // media type selector
    if(target.matches('.media-type-selector')){
        if(target.checked){
            document.querySelectorAll('.cell.'+target.value).forEach(el=>el.classList.remove('hidden'));

            // remove all more than the maximum
            var visible_cells   = document.querySelectorAll('.cell:not(.hidden)');
            var amount          = document.querySelector('#media-amount').value;
            for (let i = amount; i < visible_cells.length; i++) { 
                visible_cells[i].remove();
            }
        }else{
            document.querySelectorAll('.cell.'+target.value).forEach(el=>el.classList.add('hidden'));

            var media = document.querySelectorAll('.cell');
            // load more of the remaining types untill we reach the maximum
            var visible_cells   = document.querySelectorAll('.cell:not(.hidden)');
            load_more(media[media.length-1].dataset.index, false, visible_cells.length);
        }
    }

    if(target.matches('.mediabuttons .search')){
        ev.preventDefault();
		ev.stopPropagation();
        mediaSearch(target);
    }

    if(target.matches('.download')){
        ev.preventDefault();
        var answer = await Swal.fire({
            title: 'Warning',
            html: "Downloading of materials is only allowed for use in presentations. <br>You should not share this file with others as it may contain privacy sensitive information",
            showCancelButton: true,
            confirmButtonText: 'I promise not to share this file',
            confirmButtonColor: "#bd2919"
        });
    
        //swap and/or
        if (answer.isConfirmed) {
            console.log(target.querySelector('a'));
            target.querySelector('a').click();
        }
    }
});

document.addEventListener('change', ev=>{
    var target= ev.target;

    if(target.id=='media-amount'){
        //reset page count
        document.getElementById('paged').value = 1;

        //Check how many we have currently
        var media       = document.querySelectorAll('.cell');
        var curAmount   = media.length;

        // We need to add more
        if(target.value > curAmount){
            var start   = parseInt(media[media.length-1].dataset.index);
            load_more(start, false, curAmount);
        // We need to remove some
        }else if(target.value < curAmount){
            var i = 1;
            document.querySelectorAll('.cell, .large-image').forEach(el=>{
                if(i > target.value){
                    el.remove()
                }

                if(el.matches('.cell')){
                    i++;
                }
            });
        }
    }
});

function handleTouchStart(evt) {
    const firstTouch = evt.touches[0];                                      
    xDown = firstTouch.clientX;                                      
    yDown = firstTouch.clientY;                                      
};                                                
                                                                           
function handleTouchMove(evt) {
    if ( ! xDown || ! yDown ) {
        return;
    }

    var xUp = evt.touches[0].clientX;                                    
    var yUp = evt.touches[0].clientY;

    var xDiff = xDown - xUp;
    var yDiff = yDown - yUp;
                                                                        
    if ( Math.abs( xDiff ) > Math.abs( yDiff ) ) {
        var el = document.querySelector('.large-image:not(.hidden)');
        if ( xDiff > 0 ) {
            /* right swipe */
            var next_el = el.nextElementSibling.nextElementSibling;
        } else {
            /* left swipe */
            var next_el = el.previousElementSibling.previousElementSibling;
        }
        showImage(next_el.dataset.index);
    }
    /* reset values */
    xDown = null;
    yDown = null;                                             
};


var xDown = null;                                                        
var yDown = null;
document.addEventListener('touchstart', handleTouchStart);        
document.addEventListener('touchmove', handleTouchMove);