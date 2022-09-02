import { __ } from '@wordpress/i18n';
import {useBlockProps, InspectorControls} from "@wordpress/block-editor";
import './editor.scss';
import apiFetch from "@wordpress/api-fetch";
import {useState, useEffect} from "@wordpress/element";
import {Panel, PanelBody, CheckboxControl, Spinner} from "@wordpress/components";

// Hide the gutenberg top bar when full screen
document.addEventListener('click', async ev=>{
    var target  = ev.target;

    if(target.matches('.media-item')){
        document.querySelector('.interface-interface-skeleton__header').style.zIndex = 0; 
    }

    if(target.matches('.closebtn')){
        document.querySelector('.interface-interface-skeleton__header').style.zIndex = 30;
    }
});

const Edit = ({attributes, setAttributes}) => {
	const {categories} = attributes;

	const [html, setHtml] = useState([]);

	const [cats, setCats] = useState(< Spinner />);

	const [fetchedCats, storeFetchedCats] = useState([]);

	const onCatChanged	= function(checked, id){
		let copy;

		if(checked && !categories.includes(id)){
			copy = [ ...categories, id];
		}else if(!checked){
			console.log(categories)
			copy = categories.filter(val => val != id);
		}

		setAttributes({categories: copy});
	}

	useEffect( 
		async () => {
			const response = await apiFetch({
				path: sim.restApiPrefix+'/mediagallery/show',
				method: 'POST',
    			data: { categories: categories },
			});
			setHtml( response );

			setCats( fetchedCats.map( c => (
				<CheckboxControl
					label		= {c.name}
					onChange	= {(checked) => onCatChanged(checked, c.id)}
					checked		= {categories.includes(c.id)}
				/>
			)));
		} ,
		[fetchedCats, attributes.categories]
	);

	useEffect( async () => {
		let fetchedCategories 	= await apiFetch({path: '/wp/v2/attachment_cat'});
		fetchedCategories.unshift({name:'All', id:-1});
		storeFetchedCats( fetchedCategories);
	} , []);

	return (
		<>
			<InspectorControls>
				<Panel>
					<PanelBody>
						Select a category you want to include media for
						{cats}
					</PanelBody>
				</Panel>
			</InspectorControls>
			<div {...useBlockProps()}>
				{wp.element.RawHTML( { children: html })}
			</div>
		</>
	);
}

export default Edit;
