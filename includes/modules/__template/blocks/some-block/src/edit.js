import { __ } from '@wordpress/i18n';
import {useBlockProps, InspectorControls} from "@wordpress/block-editor";
import './editor.scss';
import apiFetch from "@wordpress/api-fetch";
import {useState, useEffect} from "@wordpress/element";
import {Panel, PanelBody, Spinner, CheckboxControl, __experimentalNumberControl as NumberControl} from "@wordpress/components";

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


	// variable, function name to set variable
	const [events, storeEvents] = useState([]);

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
