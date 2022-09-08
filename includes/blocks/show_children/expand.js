document.addEventListener('DOMContentLoaded', () => {
    // Unhide the children of the ancestor and the current page
    document.querySelectorAll('.childpost .current_page_ancestor > .children.hidden, .current_page_item > .children.hidden').forEach(el=>el.classList.remove('hidden'));

    // Create a button
    button = document.createElement('button');
    button.innerHTML = '&#x25BC;';
    button.classList.add('button');
    button.classList.add('small');
    button.classList.add('expand-children');

    // Insert a button
    document.querySelectorAll('.childpost .page_item_has_children > a').forEach(el=>{
        // There are children and no button yet
        if(el.parentNode.querySelector('.children') != null && el.nextElementSibling.matches(':not(.expand-children)')){
            el.parentNode.insertBefore(button.cloneNode(true), el.nextSibling);
        }
    });

    // Expand al ancestors and children of the current page
    //document.querySelectorAll('.childpost .current_page_ancestor > .expand-children, .current_page_item > .children.hidden').forEach(el=>el.textContent = '-');

    // Add event listeners to the buttons
    document.addEventListener('click', ev=>{
        if(ev.target.matches('.expand-children')){
            ev.target.closest('li').querySelector('.children').classList.toggle('hidden');
        }
    });
})