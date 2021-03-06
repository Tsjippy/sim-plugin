/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/packages/packages-block-editor/#useBlockProps
 */
 import {useBlockProps, InspectorControls} from "@wordpress/block-editor";

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/developers/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
// blocks/mylatests/src/edit.js

import apiFetch from "@wordpress/api-fetch";
import {useState, useEffect} from "@wordpress/element";
import {Panel, PanelBody, CheckboxControl, __experimentalNumberControl as NumberControl} from "@wordpress/components";

const apiPath	= "/sim/v1/events/upcoming_events";
const catsPath	= "/wp/v2/events";

const Edit = ({attributes, setAttributes}) => {

	let {items, months, categories, home} = attributes;

	if(categories == undefined){
		categories	= [];
	}

	const onCatChanged	= function(checked){
		let copy = Object.assign({}, categories);
		// this is the cat id
		copy[this]	= checked;
		setAttributes({categories: copy});
	}

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
	} , []);


	// variable, function name to set variable
	const [events, storeEvents] = useState('');

	const fetchEvents = async () => {
		const path = items ? `${apiPath}?items=${items}&months=${months}&categories=${categories}` : apiPath;
		const fetchedEvents = await apiFetch({path});
		storeEvents(fetchedEvents);
	}

	useEffect( () => { fetchEvents(); }, [items, months, categories]);

	const buildHtml	= () => {

		if ( events.length === 0 ) {
			return <p>No events found!</p>;
		}

		return (
			events.map(event => {
				return (
					<article class="event-article">
						<div class="event-wrapper">
							<div class="event-date">
								<span>{event.day}</span> {event.month}
							</div>
							<h4 class="event-title">
								<a href={event.url}>
									{event.title}
								</a>
							</h4>
							<div class="event-detail">
								{event.time}
							</div>
						</div>
					</article>
				);
			})
		)
	}

	if ( events.length === 0 ) {
		return <div {...useBlockProps()}>Loading events...</div>;
	}

	return (
		<>
			<InspectorControls>
				<Panel>
					<PanelBody>
						Select an category you want to exclude from the list
						{cats}
						<NumberControl
							label		= {__("Select the maximum amount of events", "sim")}
							value		= {items || 10}
							onChange	= {(val) => setAttributes({items: parseInt(val)})}
							min			= {1}
							max			= {20}
						/>
						<NumberControl
							label		= {__("Select the range in months we will retrieve", "sim")}
							value		= {months || 2}
							onChange	= {(val) => setAttributes({months: parseInt(val)})}
							min			= {1}
							max			= {12}
						/>
						<CheckboxControl
							label		= {__("Only show on homepage", "sim")}
							onChange	= {(val) => setAttributes({home: val})}
							checked		= {home}
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
