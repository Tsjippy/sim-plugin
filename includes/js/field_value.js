function getRadioValue(form, name){
	let el	= form.querySelector(`[name='${name}' i]:checked`);

	//There is no radio selected currently
	if(el == null){
		//return an empty value
		return '';
	}

	return el.value;
}

function getCheckboxValue(form, name, compareValue, orgName){
	let value		= '';
	let elements	= '';

	//we are dealing with a specific checkbox
	if(orgName.type == 'checkbox' ){
		if(orgName.checked){
			return orgName.value;
		}
		
		return '';
	}

	//we should find the checkbox with this value and check if it is checked
	if(compareValue != null){
		elements	= form.querySelector(`[name='${name}' i][value="${compareValue}" i]:checked`);
		if(elements != null){
			value = compareValue;
		}
	//no compare value give just return all checked values
	}else{
		elements	= form.querySelectorAll(`[name="${name}" i]:checked`);
		value		= [];
		elements.forEach(el=>{
			value.push(el.value);
			/* if(value != ''){
				value += ', ';
			}
			value += el.value; */
		});
	}

	return value;
}

export function getDataListValue(el){
	let value		= '';
	let origInput 	= el.list.querySelector(`[value='${el.value}' i]`);
			
	if(origInput == null){
		value =  el.value;
	}else{
		value = origInput.dataset.value;
		if(value == ''){
			value =  el.value;
		}
	}

	return value;
}

export function getFieldValue(orgName, form, checkDatalist=true, compareValue=null, lowercase=false){
	let el		= ''; 
	let name	= '';
	let value 	= '';

	//name is not a name but a node
	if(orgName instanceof Element){
		el			= orgName;		
		//check if valid input type
		if(el.tagName != 'INPUT' && el.tagName != 'TEXTAREA' && el.tagName != 'SELECT' && el.closest('.nice-select-dropdown') == null){
			el = el.querySelector('input, select, textarea');
		}
		if(el == null){
			el			= orgName;
		}
		name		= el.name;
	}else{
		name		= orgName;
		el			= form.querySelector(`[name='${name}' i]`);
	}
	
	if(el == null){
		console.trace();
		console.log("cannot find element with name "+name);

		return value;
	}

	if(el.type == 'radio'){
		value	= getRadioValue(form, name);
	}else if(el.type == 'checkbox'){
		value	= getCheckboxValue(form, name, compareValue, orgName);	
	}else if(el.closest('.nice-select-dropdown') != null && el.dataset.value != undefined){
		//nice select
		value = el.dataset.value
	}else if(el.list != null && checkDatalist){
		value =  getDataListValue(el);
	}else if(el.value != null && el.value != 'undefined'){
		value = el.value;
	}
	
	if(lowercase){
		return value.toLowerCase();
	}
	return value;
}