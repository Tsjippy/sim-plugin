function click_listener(target){
    //close modal on close click
    if(target.matches(".close")){
        target.closest('.modal').classList.add('hidden');
    }
}

var clickListener = require('./globals.js');
clickListener.push( click_listener);

export function hide_modals(){

}