import { __ } from '@wordpress/i18n';
import {useBlockProps, InspectorControls} from "@wordpress/block-editor";
import './editor.scss';
import apiFetch from "@wordpress/api-fetch";
import {useState, useEffect} from "@wordpress/element";
import {TextControl , Spinner, Panel, PanelBody, __experimentalNumberControl as NumberControl} from "@wordpress/components";

const Edit = ({attributes, setAttributes}) => {
	console.log(attributes);
	const {title, months} = attributes;

	console.log(title);

	const [html, setHtml] = useState(< Spinner />);

	useEffect( 
		async () => {
			setHtml( < Spinner /> );
			const response = await apiFetch({path: sim.restApiPrefix+'/events/upcoming_arrivals'});
			setHtml( response );
		} ,
		[]
	);

	return (
		<>
			<InspectorControls>
				<Panel>
					<PanelBody>
						<TextControl
							label		= "Block title"
							value		= { title }
							onChange	= { (val) => setAttributes({title: val}) }
						/>
						<NumberControl
							label		= {__("Give the amount of months", "sim")}
							value		= {months || 2}
							onChange	= {(val) => setAttributes({months: parseInt(val)})}
							min			= {1}
							max			= {12}
						/>
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
