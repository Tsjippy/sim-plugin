function getRadioValue(form, selector){
	let el	= form.querySelector(`${selector}:checked`);

	//There is no radio selected currently
	if(el == null){
		//return an empty value
		return '';
	}

	return el.value;
}

function getCheckboxValue(form, selector, compareValue, orgName){
	let value		= '';
	let elements	= '';

	//we are dealing with a specific checkbox
	if(orgName.type == 'checkbox' && compareValue != null ){
		if(orgName.checked){
			return orgName.value;
		}
		
		return '';
	}

	//we should find the checkbox with this value and check if it is checked
	if(compareValue != null){
		elements	= form.querySelector(`${selector}[value="${compareValue}" i]:checked`);
		if(elements != null){
			value = compareValue;
		}
	//no compare value give just return all checked values
	}else{
		elements	= form.querySelectorAll(`${selector}:checked`);
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

function getMultiValue(form, el){
	let value = [];

	form.querySelectorAll(`[name="${el.name}"]`).forEach(elem => value.push(elem.value));

	return value;
}

export function getDataListValue(el){
	let value		= '';
	let origInput 	= el.list.querySelector(`[value='${el.value}' i]`);
			
	if(origInput == null){
		value =  el.value;
	}else{
		value = origInput.dataset.value;
	}

	return value;
}

export function getFieldValue(orgName, form, checkDatalist=true, compareValue=null, lowercase=false){

	let el			= ''; 
	let name		= '';
	let value 		= '';
	let selector	= '';

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
	// We should look for an id
	}else if(orgName.match("E[0-9]")){
		selector	= `[id^=${orgName}]`;
		el			= form.querySelector(selector);
		name		= el.name;
	}else{
		name		= orgName;
		selector	= `[name='${name}' i]`;
		el			= form.querySelector(selector);
	}
	
	if(el == null){
		console.trace();
		console.log("cannot find element with name "+name);

		return value;
	}

	if(el.type == 'radio'){
		value	= getRadioValue(form, selector);
	}else if(el.type == 'checkbox'){
		value	= getCheckboxValue(form, selector, compareValue, orgName);	
	}else if(el.closest('.nice-select-dropdown') != null && el.dataset.value != undefined){
		//nice select
		value = el.dataset.value
	}else if(el.list != null && el.value != '' && checkDatalist){
		value =  getDataListValue(el);
	}else if(el.name.endsWith('[]')){
		value =  getMultiValue(form, el);
	}else if(el.value != null && el.value != 'undefined'){
		value = el.value;
	}
	
	if(lowercase){
		return value.toLowerCase();
	}
	return value;
}