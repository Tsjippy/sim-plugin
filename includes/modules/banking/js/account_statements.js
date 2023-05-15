
console.log("Account statements.js loaded");

function toggle_statements(event){
	var button = event.target;

	var target = button.dataset.target;
	document.querySelectorAll('.'+target).forEach(function(tablerow){
		if(tablerow.style.display == 'none'){
			tablerow.style.display = 'table-row';
			button.textContent = button.textContent.replace('Show','Hide');
		}else{
			tablerow.style.display = 'none';
			button.textContent = button.textContent.replace('Hide','Show');
		}
	});
}

document.addEventListener("DOMContentLoaded",function() {
	
	document.querySelectorAll('.statement_button').forEach(function(button){
		button.addEventListener('click', toggle_statements);
	})
});