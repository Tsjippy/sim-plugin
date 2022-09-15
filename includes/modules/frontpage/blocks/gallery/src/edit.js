import { __ } from '@wordpress/i18n';
import {useBlockProps, InspectorControls} from "@wordpress/block-editor";
import './editor.scss';
import apiFetch from "@wordpress/api-fetch";
import {useState, useEffect} from "@wordpress/element";
import {Panel, PanelBody, Spinner, CheckboxControl, __experimentalNumberControl as NumberControl, __experimentalInputControl as InputControl} from "@wordpress/components";
import { store as coreDataStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';

const Edit = ({ setAttributes, attributes, context }) => {
	let selPostTypes									= attributes.postTypes;
	let selCategories									= JSON.parse(attributes.categories);
	const curPostType									= context['postType'];

	const [usedPostTypes, setUsedPostTypes]				= useState( [] );

	const [availableCats, setAvailableCats]				= useState( {} );

	const [html, setHtml] 								= useState(< Spinner /> );

	const [postTypeCheckboxes, setPostTypeCheckboxes]	= useState( <> <br></br>< Spinner /> </>);

	const [catCheckboxes, setCatCheckboxes]				= useState( <> <br></br>< Spinner /> </>);

	const [trigger, setTrigger] 						= useState(false); // dummy to fore rerender

	const taxonomies = useSelect(
		( select) => {
			return select( coreDataStore ).getTaxonomies({per_page: -1});
		},
		[]
	);

	const postTypes = useSelect(
		( select) => {
			return select( coreDataStore ).getPostTypes({per_page: -1});
		},
		[]
	);

	// Get all categories
	useEffect( 
		() => {
			if( postTypes == null || taxonomies == null){
				return;
			}
			let usedPostTypes	= postTypes.filter( type => !['revision','nav_menu_item','custom_css','customize_changeset','oembed_cache','user_request','wp_block','wp_template', 'wp_template_part', 'wp_navigation'].includes(type.slug));
			setUsedPostTypes(usedPostTypes);
			let copy	= {...availableCats}

			usedPostTypes.map( type =>{
				let tax	= taxonomies.filter(cat => cat.types.includes(type.slug));

				if(copy[type.slug] == undefined){
					copy[type.slug] = {};
				}

 				tax.map(async t => {
					copy[type.slug][t.slug] =  await apiFetch({path: `/${t.rest_namespace}/${t.rest_base}`});
				});
			});
			setAvailableCats(copy);
		} ,
		[ postTypes, taxonomies ]
	);

	const postTypeSelected = function (slug, checked){
		let newPostTypes	= [...selPostTypes];

		if( !checked){
			newPostTypes   = newPostTypes.filter(el => el != slug);
		}else if(!newPostTypes.includes(slug)){
			newPostTypes.push(slug)
		}

		console.log(newPostTypes)

		setAttributes({postTypes: newPostTypes});
	}

	const postCatSelected = function (type, tax, slug, checked){
		let newSelCategories	= {...selCategories};

		if(newSelCategories[type] == undefined){
			newSelCategories[type]	= {};
		}

		if(newSelCategories[type][tax] == undefined){
			newSelCategories[type][tax]	= [];
		}

		if( !checked){
			newSelCategories   = newSelCategories[type][tax].filter(el => el != slug);
		}else if(!newSelCategories[type][tax].includes(slug)){
			newSelCategories[type][tax].push(slug)
		}
		setAttributes({categories: JSON.stringify(newSelCategories)});
	}

	// build the checkboxes for the post type selections
	useEffect( async () => {

		if(usedPostTypes.length == 0){
			return;
		}

		setPostTypeCheckboxes(
			usedPostTypes.map( c => (
				<CheckboxControl
					label		= { c.name }
					onChange	= { (checked) => {postTypeSelected(c.slug, checked)} }
					checked		= { selPostTypes.includes(c.slug) }
				/>
			))
		);
	} , [ usedPostTypes, attributes.postTypes]);

	// build the checkboxes for the category selection
	useEffect( 
		() => {
			let selected	= true;
			
			if(Object.keys(availableCats).length == 0){
				setCatCheckboxes(< Spinner />);
				return;
			}

			let rendered	= [];

			if(selPostTypes.length == 0 && availableCats[curPostType] != undefined ){
				setAttributes({postTypes: [curPostType]});
			}

			selPostTypes.forEach(postType =>{
				rendered.push(<h2>{postType.charAt(0).toUpperCase() + postType.slice(1)}</h2>);

				if(availableCats[postType] == undefined || Object.entries(availableCats[postType]).length == 0){
					setCatCheckboxes(< Spinner />);

					// Check every second
					setTimeout(
						setTrigger,
						1000,
						!trigger
					);
					return;
				}

				Object.keys(availableCats[postType]).forEach(tax=>{
					rendered.push(tax.charAt(0).toUpperCase() + tax.slice(1));
					Object.values(availableCats[postType][tax]).map(c=>{
						selected	= true;
						try{
							selected	= selCategories[postType][tax].includes(c.slug);
						}catch (e) {
							selected	= false;
						}
						rendered.push(
							<CheckboxControl
								label		= { c.name }
								onChange	= { (checked) => { postCatSelected(postType, tax, c.slug, checked) } }
								checked		= { selected }
							/>
						)
					});
				});
				
			});

			setCatCheckboxes(rendered);
		} ,
		[ availableCats, attributes.categories, attributes.postTypes, trigger ]
	);

	// retrieve the html
	useEffect( async () => {
		setHtml(<Spinner />);
		const response = await apiFetch({
			path: sim.restApiPrefix+'/frontpage/show_page_gallery',
			method: 'POST',
			data: { 
				'postTypes'	: selPostTypes,
				'amount'	: attributes.amount,
				'categories': selCategories,
				'speed'		: attributes.speed,
				'title'		: attributes.title,
			},
		});

		setHtml(wp.element.RawHTML( { children: response }));
	} , [attributes]);

	return (
		<>
			<InspectorControls>
					<Panel>
						<PanelBody>
							<InputControl
								label				= {__('Title', 'sim') }
								isPressEnterToChange= { true }
								value				= { attributes.title }
								onChange			= { (value) => setAttributes({ title: value }) }
							/>

							{__('How many pages should be shown at once', 'sim')}
							<NumberControl
								label		= {__('Page count', 'sim')}
								value		= {attributes.amount}
								onChange	= {(val) => setAttributes({amount: parseInt(val)})}
								min			= {1}
								max			= {12}
							/>
							<br></br>
							{__('How often should we refresh in seconds', 'sim')}
							<NumberControl
								label		= {__('Refresh rate', 'sim')}
								value		= {attributes.speed}
								onChange	= {(val) => setAttributes({speed: parseInt(val)})}
								min			= {30}
							/>
							<br></br>
							Select the post types you want to include in the gallery:
							{ postTypeCheckboxes }
							<br></br>
							Select the categories you want from any post type.
							Leave empty for all
							{ catCheckboxes }
						</PanelBody>
					</Panel>
			</InspectorControls>
			<div {...useBlockProps()}>
				{ html }
			</div>
		</>
	);
}

export default Edit;
