import { __ } from '@wordpress/i18n';
import {useBlockProps, InspectorControls} from "@wordpress/block-editor";
import './editor.scss';
import apiFetch from "@wordpress/api-fetch";
import {useState, useEffect} from "@wordpress/element";
import {ToggleControl, Panel, PanelBody, Spinner, CheckboxControl, __experimentalNumberControl as NumberControl, __experimentalInputControl as InputControl} from "@wordpress/components";

const Edit = ({attributes, setAttributes}) => {
	const [html, setHtml] = useState([]);

	useEffect( 
		async () => {
			const response = await apiFetch({path: sim.restApiPrefix+'/frontendposting/your_posts'});
			setHtml( response );
		} ,
		[]
	);

	return (
		<>
			<div {...useBlockProps()}>
				{wp.element.RawHTML( { children: html })}
			</div>
		</>
	);
}

export default Edit;
