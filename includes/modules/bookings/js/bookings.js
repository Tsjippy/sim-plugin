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
}

async function getMonth(target){
    let wrapper         = target.closest('.bookings-wrap');
    let monthContainer  = wrapper.querySelector(`.month-container[data-month="${target.dataset.month}"][data-year="${target.dataset.year}"]`);

    // hide the first month
    if(target.closest('.prev') != null){
        wrapper.querySelectorAll('.calendar.table .month-container:not(.hidden)')[1].classList.add('hidden');
    }else{
        wrapper.querySelectorAll('.calendar.table .month-container:not(.hidden)')[0].classList.add('hidden');
    }

    // month does not exist yet
    if(monthContainer == null){
        let formData    = new FormData();
        formData.append('month', target.dataset.month);
        formData.append('year', target.dataset.year);
        formData.append('subject', target.closest('.bookings-wrap').dataset.subject);
        formData.append('shortcodeid', target.closest('.bookings-wrap').dataset.shortcodeid);

        let loaderWrapper	= document.createElement("DIV");
        loaderWrapper.setAttribute('class','loaderwrapper');

        let loader	= document.createElement("IMG");
        loader.setAttribute("src", sim.loadingGif);

        loaderWrapper.insertAdjacentElement('beforeEnd', loader);
        wrapper.querySelector('.calendar.table').insertAdjacentElement('beforeEnd', loaderWrapper);
            
        let response = await FormSubmit.fetchRestApi('bookings/get_next_month', formData);

        if(response){
            loaderWrapper.outerHTML                                = response.month;
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
        monthContainer.classList.remove('hidden');
    }
}

function changeBookingData(target){
    let selector    = '';
    let el  = document.querySelector(`.booking-subject-selector`);
    if(el == null){
        selector    = '.booking.modal';
    }else{
        selector    = `[name="${el.value}-modal"]`;
    }
    document.querySelector(selector).classList.remove('hidden');
}

function storeDates(target){
    let modal   = target.closest('.modal');
    
    let startEl     = target.closest('form').querySelector('[name="booking-startdate"]');
    let endEl       = target.closest('form').querySelector('[name="booking-enddate"]');

    startEl.value   = modal.querySelector('.booking-startdate').dataset.isodate;
    endEl.value     = modal.querySelector('.booking-enddate').dataset.isodate;

    startEl.closest('.selected-booking-dates').classList.remove('hidden');

    Main.hideModals();

    target.closest('form').querySelector('.change-booking-date').textContent    = 'Change';

    reset(modal);
}

function daySelected(target){ 
    let modal   = target.closest('.modal');

    if(modal == null){
        return;
    }

    // we already have an selection
    if(modal.querySelector('.calendar.day.startdate') != null && modal.querySelector('.calendar.day.enddate') != null){
        let onlyEnd = false;
        if(target.matches('.inbetween')){
            onlyEnd = true;
        }
        
        reset(modal, onlyEnd);
    }

    if(modal.querySelector('.calendar.day.startdate') == null){
        modal.querySelector('.booking-startdate').value             = target.dataset.date;
        modal.querySelector('.booking-startdate').dataset.isodate   = target.dataset.isodate;

        target.classList.add('startdate');
        modal.querySelectorAll('.booking-date-label-wrapper.disabled').forEach(el=>el.classList.remove('disabled'));

        // do not allow any date before the startdate to be the enddate
        let dts     = target.closest('.calendar.table').querySelectorAll('dt.calendar.day:not(.head, .unavailable)');
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
        target.closest('.calendar.table').querySelectorAll('.available.booked:not(.enddate, .startdate)').forEach(dt=>dt.classList.remove('booked'));

        // color the dates between start and end
        let dts     = target.closest('.calendar.table').querySelectorAll('dt.calendar.day:not(.head)');
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
        target.closest('.bookings-wrap').querySelector(`.booking-detail-wrapper[data-bookingid="${target.dataset.bookingid}"]`).classList.remove('hidden');
    }
});