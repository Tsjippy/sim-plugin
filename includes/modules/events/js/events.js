async function request_month(target, month, year){
    url.searchParams.set('month', month);
    url.searchParams.set('yr', year);
    window.history.pushState({}, '', url);

    //hide all
    document.querySelectorAll('#monthview .events-wrap:not(hidden)').forEach(el=>el.classList.add('hidden'));

    var calendar_page   = document.querySelector('.events-wrap[data-date="'+year+'-'+month+'"]');
    if(calendar_page == null){
        target.closest('.calendar-wrap').insertAdjacentHTML('beforeEnd','<img class="loader" src="'+sim.loading_gif+'" style="margin-left: auto;margin-right: auto;display: block;">');
        
        var formdata = new FormData();
        formdata.append('month',month);
        formdata.append('year',year);

        var response    = await fetchRestApi('events/get_month_html', formdata);
        
        if(response) {
            target.closest('.calendar-wrap').querySelector('.loader').remove();
            document.querySelector('#monthview').insertAdjacentHTML('beforeEnd', response);
        }
    }else{
        calendar_page.classList.remove('hidden');
    }

    document.querySelector('div.month_selector .current').textContent = document.querySelector('select.month_selector').options[month-1].text;
    document.querySelector('div.year_selector .current').textContent = year;
}

async function request_week(target, wknr, year){
    url.searchParams.set('yr', year);
    url.searchParams.set('week', wknr);
    window.history.pushState({}, '', url);

    //hide all
    document.querySelectorAll('#weekview .events-wrap:not(hidden)').forEach(el=>el.classList.add('hidden'));

    var calendar_page   = document.querySelector('.events-wrap[data-weeknr="'+wknr+'"]');
    if(calendar_page == null){
        target.closest('.calendar-wrap').insertAdjacentHTML(
            'beforeEnd',
            `<img class="loader" src="${sim.loading_gif}" style="margin-left: auto;margin-right: auto;display: block;">`
        );
        
        var formdata = new FormData();
        formdata.append('wknr',wknr);
        formdata.append('year',year);

        var response    = await fetchRestApi('events/get_week_html', formdata);
        
        if(response) {
            target.closest('.calendar-wrap').querySelector('.loader').remove();
            document.querySelector('#weekview').insertAdjacentHTML('beforeEnd',response);
        }
    }else{
        calendar_page.classList.remove('hidden');
    }

    document.querySelector('div.week_selector .current').textContent = wknr;
    document.querySelector('div.year_selector .current').textContent = year;
}

async function request_expand_list(offset, month='', year=''){
    //remove any existing element when requesting specific date
    if(month != '' || year != ''){
        document.querySelectorAll('#listview article').forEach(el=>el.remove());

        url.searchParams.set('yr', year);
        url.searchParams.set('month', month);
        window.history.pushState({}, '', url);
    }

    document.getElementById('listview').insertAdjacentHTML('beforeEnd','<img class="loader" src="'+sim.loading_gif+'" style="margin-left: auto;margin-right: auto;display: block;">');
    
    var formdata = new FormData();
    formdata.append('offset',offset);
    formdata.append('month',month);
    formdata.append('year',year);
    
    var response    = await fetchRestApi('events/get_list_html', formdata);
        
    if(response) {
        document.querySelector('#listview').querySelector('.loader').remove();
        document.querySelector('#listview').insertAdjacentHTML('beforeEnd',response);
    }
}                                                    
                                                                           
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
        var selected_view   = document.querySelector('.viewselector.selected').dataset.type;

        if ( xDiff > 0 ) {
            /* right swipe */
            var target  = evt.target.closest('.events-wrap').querySelector('.next a');
        } else {
            /* left swipe */
            var target  = evt.target.closest('.events-wrap').querySelector('.prev a');
        }
        var month = target.dataset.month;
        var week  = target.dataset.weeknr;
        var year  = target.dataset.year;
        
        if(selected_view == 'weekview'){
            request_week(evt.target, week, year);
        }else if(selected_view == 'monthview'){
            request_month(target, month, year);
        }
    }
    /* reset values */
    xDown = null;
    yDown = null;                                             
};

const url = new URL(window.location);
var xDown = null;                                                        
var yDown = null;

document.addEventListener("DOMContentLoaded",function() {
	console.log("Events.js loaded");
});

document.addEventListener("click",function(event) {
	var target = event.target;
    if(target.classList.contains('prevnext')){
        var requested_month = target.dataset.month;
        var requested_week  = target.dataset.weeknr;
        var requested_year  = target.dataset.year;

        if(requested_month != null){
            request_month(target, requested_month, requested_year);
        }else if(requested_week != null){
            request_week(target, requested_week, requested_year);
        }
    }

    if(target.classList.contains('calendar-day')){
        var date    = target.dataset.date;

        //hide events of all days
        document.querySelectorAll('.event-details-wrapper:not(.hidden)').forEach(el=>el.classList.add('hidden'));
        
        //show the events of this day
        target.closest('.events-wrap').querySelector('.event-details-wrapper[data-date="'+date+'"]').classList.remove('hidden');

        //unselect previous slected date
        document.querySelectorAll('.calendar-day.selected').forEach(el=>el.classList.remove('selected'));
        //make this date selected
        target.classList.add('selected');
    }

    if(target.classList.contains('calendar-hour')){
        var date        = target.dataset.date;
        var starttime   = target.dataset.starttime;

        //hide all other events
        document.querySelectorAll('.event-details-wrapper:not(.hidden)').forEach(el=>el.classList.add('hidden'));
        
        //show the event
        var eventdetail = target.closest('.events-wrap').querySelector('.event-details-wrapper[data-date="'+date+'"][data-starttime="'+starttime+'"]');
        if(eventdetail == null){
            target.closest('.events-wrap').querySelector('.event-details-wrapper[data-date="empty"]').classList.remove('hidden');
        }else{
            eventdetail.classList.remove('hidden');

            //unselect previous selected date
            document.querySelectorAll('.calendar-hour.selected').forEach(el=>el.classList.remove('selected'));
            //make this date selected
            target.classList.add('selected');

            if(isMobileDevice()){
                window.scrollTo(0, eventdetail.offsetTop);
            }else{
                //scroll the detail into view
                window.scrollTo(0, eventdetail.offsetHeight);
            }
        }
    }

    if(target.classList.contains('viewselector')){
        var parent  = target.closest('.search-form');
        if(target.dataset.type  == 'weekview' || target.dataset.type  == 'monthview'){
            parent.querySelector('div.week_selector').classList.toggle('hidden');
            parent.querySelector('div.month_selector').classList.toggle('hidden');
        }

        //select class
        document.querySelectorAll('.viewselector.selected').forEach(el=>el.classList.remove('selected'));
        target.classList.add('selected');

        //show view
        document.querySelectorAll('.calendarview').forEach(el=>el.classList.add('hidden'));
        document.getElementById(target.dataset.type).classList.remove('hidden');

        //change url
        url.searchParams.set('view', target.dataset.type.replace('view',''));
        window.history.pushState({}, '', url);
    }

    if(target.id=='add_calendar'){
        document.getElementById('calendaraddingoptions').classList.toggle('hidden');   
    }
    
    if(target.classList.contains('calendarurl')){
        if(target.textContent != ''){
            navigator.clipboard.writeText(target.textContent);
            Swal.fire({
                icon: 'success',
                title: 'Copied '+target.textContent,
                showConfirmButton: false,
                timer: 1500
            })
        }
    }
});

document.addEventListener("change",function(event) {
	var target = event.target;
    if(target.classList.contains('week_selector')){
        var year    = target.closest('.date-search').querySelector('.year_selector').value;
        if(document.querySelector('.viewselector.selected').dataset.type=='weekview'){
            request_week(target, target.value, year);
        }else if(document.querySelector('.viewselector.selected').dataset.type=='listview'){
            request_expand_list(0,target.value,year);
        }

        //change url
        url.searchParams.set('week', target.value);
    }

    if(target.classList.contains('month_selector')){
        var year    = target.closest('.date-search').querySelector('.year_selector').value;
        if(document.querySelector('.viewselector.selected').dataset.type=='monthview'){
            request_month(target, target.value, year);
        }else if(document.querySelector('.viewselector.selected').dataset.type=='listview'){
            request_expand_list(0,target.value, year);
        }
        url.searchParams.set('month', target.value);
    }

    if(target.classList.contains('year_selector')){
        var month   = target.closest('.date-search').querySelector('.month_selector').value;
        if(document.querySelector('.viewselector.selected').dataset.type=='monthview'){
            request_month(target, month, target.value);
        }else if(document.querySelector('.viewselector.selected').dataset.type=='listview'){
            request_expand_list(0,month, target.value);
        }else if(document.querySelector('.viewselector.selected').dataset.type=='weekview'){
            var wknr  = target.closest('.date-search').querySelector('.week_selector').value;
            request_week(target, wknr, target.value);
        }
        url.searchParams.set('yr', target.value);
    }
    
    window.history.pushState({}, '', url);
});

window.onscroll = function() {
    var d = document.documentElement;
    var offset = d.scrollTop + window.innerHeight;
    var height = d.offsetHeight;
  
    //if we scrolled to the bottom of the page and the list view is actve, and we are not currently loading, load more
    if (offset >= height-2 && document.querySelector('.viewselector.selected').dataset.type=='listview' && document.querySelector('#listview .loader') == null) {
        var skipcount  = document.querySelector("#listview").querySelectorAll('article').length;

        request_expand_list(skipcount);
    }
};

document.addEventListener('touchstart', handleTouchStart);        
document.addEventListener('touchmove', handleTouchMove);