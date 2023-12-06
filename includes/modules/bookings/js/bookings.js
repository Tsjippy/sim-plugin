function reset(modal, onlyEnd=false){
    if(!onlyEnd){
        modal.querySelector('.booking-startdate').value     = '';
        modal.querySelector('.calendar.day.startdate').classList.remove('startdate');
        modal.querySelectorAll('.available.unavailable').forEach(dt=>dt.classList.remove('unavailable'));

        modal.querySelector('.booking-date-label-wrapper.enddate').classList.add('disabled')
    }
    modal.querySelector('.booking-enddate').value       = '';
    
    modal.querySelector('.calendar.day.enddate').classList.remove('enddate');

    modal.querySelectorAll('.inbetween').forEach(dt=>dt.classList.remove('inbetween'));    

    modal.querySelectorAll('.room-selector').forEach(el=>el.checked=false);    

    if(modal.querySelectorAll('.room-selector').length>1){
        modal.querySelectorAll('.roomwrapper:not(.hidden)').forEach(el=>el.classList.add('hidden'));
    }
}

async function getMonth(target){
    let wrapper         = target.closest('.bookings-wrap');
    let monthContainers = wrapper.querySelectorAll(`.month-container[data-month="${target.dataset.month}"][data-year="${target.dataset.year}"]`);

    // hide the first month
    if(target.closest('.prev') != null){
        wrapper.querySelectorAll('.calendar.table .month-container:not(.hidden)').forEach(
            (el, index) => {
                if(index % 2 != 0){ // uneven index
                    el.classList.add('hidden');
                }
            }
        );
    }else{
        wrapper.querySelectorAll('.calendar.table .month-container:not(.hidden)').forEach(
            (el, index) => {
                if(index % 2 == 0){ // even index
                    el.classList.add('hidden');
                }
            }
        );
    }

    // month does not exist yet, request the data
    if(monthContainers.length == 0){
        let formData    = new FormData();
        formData.append('month', target.dataset.month);
        formData.append('year', target.dataset.year);
        formData.append('subject', wrapper.dataset.subject);
        formData.append('formid', wrapper.dataset.formid);
        if(wrapper.dataset.elid != undefined){
            formData.append('elid', wrapper.dataset.elid);
        }
        if(wrapper.dataset.shortcodeid != undefined){
            formData.append('shortcodeid', wrapper.dataset.shortcodeid);
        }

        let loaderWrapper	= document.createElement("DIV");
        loaderWrapper.setAttribute('class','loaderwrapper');

        let loader	= document.createElement("IMG");
        loader.setAttribute("src", sim.loadingGif);

        loaderWrapper.insertAdjacentElement('beforeEnd', loader);
        wrapper.querySelectorAll('.calendar.table .roomwrapper>div').forEach(div=>{
            let clone   = loaderWrapper.cloneNode(true);
            div.insertAdjacentElement('beforeEnd', clone);
        });
            
        let response = await FormSubmit.fetchRestApi('bookings/get_next_month', formData);

        if(response){

            console.log(response);

            // add the new months to each room
            wrapper.querySelectorAll('.roomwrapper').forEach((el, index)=>{
                el.querySelector('.loaderwrapper').outerHTML        = response.months[index];
            });

            // hide current navigator and add new one
            wrapper.querySelector('.navigators .navigator:not(.hidden)').classList.add('hidden');
            wrapper.querySelector('.navigators').insertAdjacentHTML('beforeEnd', response.navigator);
            wrapper.querySelector('.booking.details-wrapper').insertAdjacentHTML('beforeEnd', response.details);
        }
    }else{
        // hide the current navigator
        wrapper.querySelector('.navigators .navigator:not(.hidden)').classList.add('hidden');

        // show the new one
        console.log(wrapper);
        console.log(target);
        wrapper.querySelector(`.navigator[data-month="${target.dataset.month}"][data-year="${target.dataset.year}"]`).classList.remove('hidden');
        
        // Show the month calendar
        monthContainers.forEach(el=>el.classList.remove('hidden'));
    }
}

async function approve(target){    
    let formData    = new FormData();
    formData.append('id', target.dataset.id);

    let row         = target.closest('tr');

    Main.showLoader(target.closest('td'));
        
    let response = await FormSubmit.fetchRestApi('bookings/approve', formData);

    if(response){

        // Remove table if empty
        if(row.closest('tbody').rows.length == 1){
            row.closest('table').remove()
        }else{
            // remove the row
            row.remove();
        }

        let subjectWrapper  = document.querySelector(`.bookings-wrap[data-subject='${response.subject}']`);

        for (let i = 0; i < response.months.length; i++) {
            // Get the calendar
            subjectWrapper.querySelectorAll(`.month-container[data-month='${response.months[i]}'][data-year='${response.years[i]}']`).forEach(el=>el.outerHTML   = response.html);
        }

        // Add the details
        subjectWrapper.querySelector(`.booking.details-wrapper`).insertAdjacentHTML('beforeEnd', response.details);
        
    }
}

async function remove(target){    
    let formData    = new FormData();
    formData.append('id', target.dataset.id);
    let row         = target.closest('tr');

    Main.showLoader(target.closest('td'));
        
    let response = await FormSubmit.fetchRestApi('bookings/remove', formData);

    if(response){
        // Remove table if empty
        if(row.closest('tbody').rows.length == 1){
            row.closest('table').remove()
        }else{
            // remove the row
            row.remove();
        }

        Main.displayMessage(response);
    }
}

function changeBookingData(target){
    let selector;
    let el  = document.querySelector(`.booking-subject-selector:checked`);
    if(el == null){
        selector    = '.booking.modal';
        console.log(selector);
    }else{
        selector    = `[name="${el.value}-modal"]`;
        console.log(selector);
    }

    Main.showModal(document.querySelector(selector));
}

function storeDates(target){
    let modal   = target.closest('.modal');

    // remove all previous dates
    target.closest('form').querySelectorAll('.selected-booking-dates .clone_div').forEach((el, index)=>{
        if(index>0){
            el.remove();
        }
    });

    let parent          = target.closest('form').querySelector('.selected-booking-dates');
    let original        = parent.querySelector('.clone_div');

    // set values and create clones
    modal.querySelectorAll('.startdate').forEach((el, index)=>{
        let clone		= original;

        if(index > 0){
            clone		= FormFunctions.cloneNode(original);
        }
    
        let startEl     = clone.querySelector('[name^="booking-startdate"]');
        let endEl       = clone.querySelector('[name^="booking-enddate"]');
        let roomEl      = clone.querySelector('[name^="booking-room"]');
        let room        = el.closest('.roomwrapper').dataset.room;

        startEl.value   = el.dataset.isodate;
        endEl.value     = modal.querySelectorAll('.day.enddate')[index].dataset.isodate;

        if(room == undefined){
            roomEl.closest('div').classList.add('hidden');
            roomEl.value    = '';
        }else{
            roomEl.closest('div').classList.remove('hidden');
            roomEl.value    = room;
        }

        parent.insertAdjacentElement('beforeEnd', clone);
        
        FormFunctions.fixNumbering(parent);
    });

    parent.classList.remove('hidden');

    Main.hideModals();

    target.closest('form').querySelector('.change-booking-date').textContent    = 'Change';

    target.closest('form').querySelector('.change-booking-date').classList.remove('hidden');

    reset(modal);
}

function daySelected(target){ 
    let modal   = target.closest('.modal');

    if(modal == null){
        return;
    }

    let roomWrapper = target.closest('.roomwrapper');

    // we already have an selection
    if(roomWrapper.querySelector('.calendar.day.startdate') != null && roomWrapper.querySelector('.calendar.day.enddate') != null){
        let onlyEnd = false;
        if(target.matches('.inbetween')){
            onlyEnd = true;
        }
        
        reset(modal, onlyEnd);
    }

    if(roomWrapper.querySelector('.calendar.day.startdate') == null){
        //roomWrapper.querySelector('.booking-startdate').value             = target.dataset.date;
        //roomWrapper.querySelector('.booking-startdate').dataset.isodate   = target.dataset.isodate;

        target.classList.add('startdate');
        roomWrapper.querySelectorAll('.booking-date-label-wrapper.disabled').forEach(el=>el.classList.remove('disabled'));

        // do not allow any date before the startdate to be the enddate
        let dts     = roomWrapper.querySelectorAll('dt.calendar.day:not(.head, .unavailable)');
        let skip    = false;
        for (i = 0; i < dts.length; ++i) {
            // all dates after the booked date are available
            if(dts[i] == target){
                skip = true;
            // until we encounter another booked date
            }else if(skip && dts[i].matches('.booked:not(.available)')){
                skip = false;
            }

            if(!skip){
                dts[i].classList.add('unavailable');
            }
        }
    }else{
        // store enddate
        modal.querySelector('.booking-enddate').value               = target.dataset.date;
        modal.querySelector('.booking-enddate').dataset.isodate     = target.dataset.isodate;
        target.classList.add('enddate');

        // make other dates available again
        roomWrapper.querySelectorAll('.available.booked:not(.enddate, .startdate)').forEach(dt=>dt.classList.remove('booked'));

        // color the dates between start and end
        let dts     = roomWrapper.querySelectorAll('dt.calendar.day:not(.head)');
        let skip    = true;
        for (i = 0; i < dts.length; ++i) {
            
            // until we encounter another booked date
            if(dts[i] == target){
                break;
            }

            if(!skip){
                dts[i].classList.add('inbetween');
            }

            // all dates after the booked date are available
            if(dts[i].matches('.startdate')){
                skip = false;
            }
        }

        modal.querySelectorAll('.actions .action.disabled').forEach(el=>el.classList.remove('disabled'));
    }
}

function roomSelected(target){

    let modal   = target.closest('.modal');

    if(modal == null){
        modal = target.closest('.booking.overview'); // when viewing the results
    }

    if(modal == null){
        return;
    }

    console.log(target)

    // show date warning
    modal.querySelectorAll(`.booking-date-wrapper.hidden`).forEach(el=>el.classList.remove('hidden'));

    // show month navigator
    modal.querySelectorAll(`.navigators.hidden`).forEach(el=>el.classList.remove('hidden'));

    // Show the selected room
    modal.querySelectorAll(`[data-room="${target.value}"]`).forEach(el=>el.classList.toggle('hidden'));
}

document.addEventListener('DOMContentLoaded', () => {
    console.log("Bookings.js loaded");

    // show booking date selector
    document.querySelectorAll(`.booking-subject-selector`).forEach(el=>el.addEventListener(`change`, (ev)=>{
        Main.showModal(document.querySelector(`[name="${ev.target.value}-modal"]`));
    }));

    // show booking calendar
    document.querySelectorAll(`.admin-booking-subject-selector`).forEach(el=>el.addEventListener(`change`, (ev)=>{
        document.querySelector(`.bookings-wrap[data-subject="${ev.target.value}"]`).classList.toggle('hidden');
    }));

    document.querySelectorAll(".tables-wrapper").forEach(wrapper=>{
        offset	= wrapper.getBoundingClientRect().x;
        wrapper.style.marginLeft = `-${offset}px`;
    });

    document.querySelectorAll('.booking-detail tr.actions').forEach(row=>row.addEventListener('submissionArchived', ev => {
        let wrapper     = ev.target.closest('.booking-detail-wrapper');
        let bookingId   = wrapper.dataset.bookingid;

        // mark dates as available again
        document.querySelectorAll(`.calendar.day.booked[data-bookingid="${bookingId}"]`).forEach(el=>{
            el.classList.remove('booked');
            el.classList.add('available');
        });

        // remove details
        wrapper.remove();
        
        Main.displayMessage('Succesfully archived');
    }));
});

document.addEventListener('click', (ev) => {
    let target  = ev.target;
    let modal   = target.closest('.modal');

    if(target.closest('dt') != null){
        target  = target.closest('dt');
    }

    if(target.matches('.action.reset')){
        reset(modal);
    }

    if(target.matches('.prevnext')){
        getMonth(target);
    }

    if(target.matches('.change-booking-date, [name="booking-startdate"], [name="booking-enddate"]')){
        changeBookingData(target);
    }

    if(target.matches('.action.confirm')){
        storeDates(target);
    }

    if(target.matches('.bookings-wrap .available:not(.unavailable)')){
        daySelected(target);
    }

    if(target.matches('.form.table-wrapper .booked')){
        // Hide others
        target.closest('.bookings-wrap').querySelectorAll(`.booking-detail-wrapper:not(.hidden)`).forEach(el=>el.classList.add('hidden'));
        
        // Show the details
        target.closest('.bookings-wrap').querySelectorAll(`.booking-detail-wrapper[data-bookingid="${target.dataset.bookingid}"]`).forEach(el=>{
            el.classList.remove('hidden');
            el.scrollIntoView({block: "center"});
        });
    }

    if(target.matches('.button.approve')){
        approve(target);
    }else if(target.matches('.button.delete')){
        remove(target);
    }else if(target.matches('.room-selector')){
        roomSelected(target);
    }
});