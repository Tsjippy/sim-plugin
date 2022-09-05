import { __ } from '@wordpress/i18n';
import {useBlockProps, InspectorControls} from "@wordpress/block-editor";
import './editor.scss';
import apiFetch from "@wordpress/api-fetch";
import {useState, useEffect} from "@wordpress/element";
import {Spinner, Panel, PanelBody, CheckboxControl, __experimentalNumberControl as NumberControl} from "@wordpress/components";

const Edit = ({attributes, setAttributes}) => {
	const {items, months, categories} = attributes;

	const onCatChanged	= function(checked){
		let copy =	{ ...categories }

		// this is the cat id
		copy[this]	= checked;
		setAttributes({categories: copy});
	}

	const [cats, setCats] = useState( < Spinner /> );

	useEffect( async () => {
		setCats( <Spinner />);
		const fetchedCats = await apiFetch({path: "/wp/v2/events"});

		setCats( fetchedCats.map( c => (
			<CheckboxControl
				label		= {c.name}
				onChange	= {onCatChanged.bind(c.id)}
				checked		= {categories[c.id]}
			/>
		)));
	} , [attributes.categories]);


	// variable, function name to set variable
	const [events, storeEvents] = useState([]);

	const fetchEvents = async () => {
		storeEvents(fetchedEvents);
		
		let param	= '';
		
		if(items != undefined){
			param += "?items"+items;
		}

		if(months != undefined){
			if(param == ''){
				param += "?";
			}else{
				param += "&";
			}
			param += "months="+months;
		}

		if(categories != undefined){
			if(param == ''){
				param += "?";
			}else{
				param += "&";
			}
			param += "categories=";
			for (const key in categories) {param += key+','}
		}
		
		let fetchedEvents = await apiFetch({path: `sim/v2/events/upcoming_events${param}`});

		if(!fetchedEvents){
			fetchedEvents	= [];
		}
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
							<div>
								<h4 class="event-title">
									<a href={event.url}>
										{event.title}
									</a>
								</h4>
								<div class="event-detail">
									{event.time}
								</div>
							</div>
						</div>
					</article>
				);
			})
		)
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
