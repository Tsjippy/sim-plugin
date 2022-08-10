
import { __ } from '@wordpress/i18n';
import {useBlockProps, InspectorControls} from "@wordpress/block-editor";
import './editor.scss';
import apiFetch from "@wordpress/api-fetch";
import {useState, useEffect} from "@wordpress/element";
import {Panel, PanelBody, CheckboxControl,ToggleControl, Spinner, __experimentalNumberControl as NumberControl} from "@wordpress/components";
import { useSelect } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';
import { decodeEntities } from '@wordpress/html-entities';

const apiPath	= "/sim/v1/events/upcoming_events";
const catsPath	= "/wp/v2/events";

const Edit = ({attributes, setAttributes}) => {
	const {count} = attributes;

	const { categories, hasResolved} = useSelect(
		( select) => {
			const query = {
				per_page: 100,
				orderby : 'title'
			};

			// Find all selected pages
			const args = [ 'taxonomy', 'category'];

			return {
				categories: select( coreDataStore ).getEntityRecords(
					...args
				),
				hasResolved: select( coreDataStore ).hasFinishedResolution(
					'getEntityRecords',
					args
				)
			};
		},
		[]
	);

	const BuildCategoryList = function(){
		if ( ! hasResolved ) {
			return(
				<>
				<Spinner />
				<br></br>
				</>
			);
		}
	
		if ( ! categories?.length ) {
			return <div> {__('No categories found', 'sim')}</div>;
		}
		
		return (
			<ul>
			{categories?.map( ( category ) => {
				let nr	= '';
				if(count){
					nr	= <>  (<span class='cat-count'>{category.count}</span>)</>;
				}
				return (<li><a href={category.link}>{category.name}{nr}</a></li>);
			} )}
			</ul>
		)
	}

	return (
		<>
			<InspectorControls>
				<Panel>
					<PanelBody>
						<ToggleControl
                            label={__('Show categories count', 'sim')}
                            checked={!!attributes.count}
                            onChange={() => setAttributes({ count: !attributes.count })}
                        />
					</PanelBody>
				</Panel>
			</InspectorControls>
			<div {...useBlockProps()}>
				<aside class='event'>
					<h4 class="title">Categories</h4>
					<div class="upcomingevents_wrapper">
						{BuildCategoryList()}
					</div>
				</aside>
			</div>
		</>
	);
}

export default Edit;
