import { __ } from '@wordpress/i18n';
import {useBlockProps, InspectorControls} from "@wordpress/block-editor";
import './editor.scss';
import apiFetch from "@wordpress/api-fetch";
import {useState, useEffect} from "@wordpress/element";
import {ToggleControl, Panel, PanelBody, Spinner, CheckboxControl, __experimentalNumberControl as NumberControl, __experimentalInputControl as InputControl} from "@wordpress/components";
import { store as coreDataStore } from '@wordpress/core-data';
import { decodeEntities } from '@wordpress/html-entities';
import { useSelect } from '@wordpress/data';

const catsPath	= "/wp/v2/events";

const Edit = ({attributes, setAttributes}) => {
	const {items, months, categories} = attributes;

	const [cats, setCats] = useState([]);

	useEffect( async () => {
		const fetchedCats = await apiFetch({path: catsPath});
		setCats( fetchedCats.map( c => (
			<CheckboxControl
				label		= {c.name}
				onChange	= {onCatChanged.bind(c.id)}
				checked		= {categories[c.id]}
			/>
		)));
	} , [attributes.categories]);

	const { pages, hasResolved} = useSelect(
		( select) => {
			const query = {
				per_page: 100,
				orderby : 'title'
			};

			// Find all selected pages
			const args = [ 'taxonomy', 'category'];

			return {
				pages: select( coreDataStore ).getEntityRecords(
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
	
		if ( ! pages?.length ) {
			return <div> {__('No categories found', 'sim')}</div>;
		}
		
		return (
			<ul>
			{pages?.map( ( category ) => {
				let nr	= '';
				if(count){
					nr	= <>  (<span class='cat-count'>{category.count}</span>)</>;
				}
				return (<li><a href={category.link}>{category.name}{nr}</a></li>);
			} )}
			</ul>
		)
	}


	// variable, function name to set variable
	const [events, storeEvents] = useState([]);

	return (
		<>
			<InspectorControls>
				<Panel>
					<PanelBody>
						Select an category you want to exclude from the list
						{cats}
						<ToggleControl
                            label={__('Hide on mobile', 'sim')}
                            checked={!!attributes.hideOnMobile}
                            onChange={() => setAttributes({ hideOnMobile: !attributes.hideOnMobile })}
                        />
						<InputControl
                            isPressEnterToChange={true}
                            label="Add php filters by name. I.e 'is_tax'"
                            value={ phpFilter }
                            onChange={onPhpFiltersChanged.bind('')}
                        />
						<NumberControl
							label		= {__("Select the range in months we will retrieve", "sim")}
							value		= {months || 2}
							onChange	= {(val) => setAttributes({months: parseInt(val)})}
							min			= {1}
							max			= {12}
						/>
						{__('Select the form you want to show the results of', 'sim')}
						<SelectControl
							label	= {__('Form to show', 'sim')}
							value={ formid }
							options={ forms }
							onChange={ (value) => {setAttributes({formid: value})} }
							__nextHasNoMarginBottom
						/>
					</PanelBody>
				</Panel>
			</InspectorControls>
			<div {...useBlockProps()}>
				<aside class='event'>
					<h4 class="title">Upcoming events</h4>
					<div class="upcomingevents_wrapper">
						{buildHtml()}
					</div>
					<a class='calendar button sim' href="./events">
						Calendar
					</a>
				</aside>
			</div>
		</>
	);
}

export default Edit;
