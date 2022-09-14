import { __ } from '@wordpress/i18n';
import {useBlockProps, InspectorControls} from "@wordpress/block-editor";
import './editor.scss';
import apiFetch from "@wordpress/api-fetch";
import {useState, useEffect} from "@wordpress/element";
import {Panel, PanelBody, Spinner, CheckboxControl} from "@wordpress/components";
import { store as coreDataStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';

const Edit = ({ setAttributes, attributes, context }) => {
	let selPostTypes									= attributes.postTypes;
	const curPostType									= context['postType'];

	if(selPostTypes.length == 0){
		setAttributes({postTypes: [curPostType]});
	}

	const [html, setHtml] 								= useState(< Spinner /> );

	const [postTypeCheckboxes, setPostTypeCheckboxes]	= useState( < Spinner /> );

	const [catCheckboxes, setCatCheckboxes]				= useState( {} );

	const [fetchedCats, storeFetchedCats]				= useState( < Spinner /> );

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

	const catSelected	= function(checked, id){
		let copy;

		if(checked && !categories.includes(id)){
			copy = [ ...categories, id];
		}else if(!checked){
			copy = categories.filter(val => val != id);
		}

		setAttributes({categories: copy});
	}

	const postTypeSelected = function (slug, checked){
		let newPostTypes	= [...selPostTypes];

		if( !checked){
			newPostTypes   = newPostTypes.filter(el => el != slug);
		}else if(!newPostTypes.includes(slug)){
			newPostTypes.push(slug)
		}

		setAttributes({postTypes: newPostTypes});
	}

	useEffect( async () => {
		if(postTypes == null){
			return;
		}

		setPostTypeCheckboxes(
			postTypes.filter( type => !['revision','nav_menu_item','custom_css','customize_changeset','oembed_cache','user_request','wp_block','wp_template', 'wp_template_part', 'wp_navigation'].includes(type.slug)).map( c => (
				<CheckboxControl
					label		= { c.name }
					onChange	= { (checked) => {postTypeSelected(c.slug, checked)} }
					checked		= { selPostTypes.includes(c.slug) || c.slug == curPostType }
				/>
			))
		);
	} , [ postTypes, attributes.postTypes]);

	useEffect( () => {	
		if(taxonomies == null){
			return;
		}

		selPostTypes.forEach(async(type) => {

			// find the tax page
			let tax	= taxonomies.filter(cat => cat.types.includes(type));

			tax.map(async t => {
				let cats =  await apiFetch({path: `/${t.rest_namespace}/${t.rest_base}`});

				let checkboxes	= {...catCheckboxes};

				if(checkboxes[type] == undefined){
					checkboxes[type] = {};
				}
				
				console.log(checkboxes[type][t.slug]);

				checkboxes[type][t.slug] = cats.map(c => {
					<CheckboxControl
						label		= { c.name }
						onChange	= { (checked) => {catSelected(c.slug, checked)} }
						checked		= { false }
					/>
				});
				console.log(checkboxes);

				setCatCheckboxes( checkboxes);
				console.log(catCheckboxes);
			})

			/* let cats;
			tax.forEach(async t => {
				cats = await apiFetch({path: `/${t.rest_namespace}/${t.rest_base}`});
			}) */

			//console.log(catCheckboxes);
		});

		
		
	} , [ postTypes, selPostTypes ]);

	useEffect( 
		async () => {
			setHtml( < Spinner /> );
			const response = await apiFetch({path: sim.restApiPrefix+'/frontendposting/pending_pages'});
			setHtml( response );
		} ,
		[]
	);

	return (
		<>
			<InspectorControls>
					<Panel>
						<PanelBody>
							Select the post types you want to include in the gallery:
							{ postTypeCheckboxes }
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
