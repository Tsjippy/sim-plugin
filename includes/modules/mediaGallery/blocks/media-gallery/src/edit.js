import { __ } from '@wordpress/i18n';
import {useBlockProps, InspectorControls} from "@wordpress/block-editor";
import './editor.scss';
import apiFetch from "@wordpress/api-fetch";
import {useState, useEffect} from "@wordpress/element";
import {Panel, PanelBody, PanelRow, CheckboxControl, Spinner, ColorPicker } from "@wordpress/components";

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
	const {categories, color} = attributes;

	const [html, setHtml] = useState(<>Loading < Spinner /></>);

	const [cats, setCats] = useState(< Spinner />);

	const [fetchedCats, storeFetchedCats] = useState(<Spinner/>);

	const onCatChanged	= function(checked, id){

		let copy;

		if(checked && !categories.includes(id)){
			copy = [ ...categories, id];
		}else if(!checked){
			copy = categories.filter(val => val != id);
		}

		setAttributes({categories: copy});
	}

	const updatecolor = function(color){
		setAttributes({color: color});
	}

	useEffect( 
		() => {
			async function buildCatChecks(){
				setHtml(<>Loading < Spinner /></>);

				if(React.isValidElement(fetchedCats)){
					return;
				}

				setCats( fetchedCats.map( c => (
					<CheckboxControl
						label		= {c.name}
						onChange	= {(checked) => onCatChanged(checked, c.id)}
						checked		= {categories.includes(c.id)}
						key			= {c.id}
					/>
				)));

				const response = await apiFetch({
					path: sim.restApiPrefix+'/mediagallery/show',
					method: 'POST',
					data: { categories: categories, color: color  },
				});
				setHtml(wp.element.RawHTML( { children: response }));
			}
			buildCatChecks();
		} ,
		[fetchedCats, attributes.categories]
	);

 	useEffect( 
		() => {
			async function getCats() {

				let fetchedCategories 	= await apiFetch({path: '/wp/v2/attachment_cat'});
				fetchedCategories.unshift({name:'All', id:-1});

				storeFetchedCats( fetchedCategories);
			}
			getCats();
	}, []);

	return (
		<>
			<InspectorControls>
				<Panel>
					<PanelBody title="Background color" initialOpen={ false }>
						<PanelRow>
							<ColorPicker
								color			= { color }
								onChange		= {(color) => setAttributes({color: color})}
								enableAlpha
								defaultValue	= "#000"
							/>
						</PanelRow>
					</PanelBody>
					<PanelBody title="Categories" initialOpen={ true }>
						<PanelRow>
							Select a category you want to include media for
						</PanelRow>
						{cats}
					</PanelBody>
				</Panel>
			</InspectorControls>
			<div {...useBlockProps()}>
				{html}
			</div>
		</>
	);
}

export default Edit;
