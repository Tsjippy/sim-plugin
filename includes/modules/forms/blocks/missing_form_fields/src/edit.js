import { __ } from '@wordpress/i18n';
import {useBlockProps, InspectorControls} from "@wordpress/block-editor";
import './editor.scss';
import apiFetch from "@wordpress/api-fetch";
import {useState, useEffect} from "@wordpress/element";
import {Panel, PanelBody, Spinner, RadioControl} from "@wordpress/components";

const Edit = ({attributes, setAttributes}) => {
	const {type} = attributes;

	const [html, setHtml] = useState( < Spinner />);

	useEffect( 
		() => {
			async function getHTML(){
				setHtml( < Spinner />);
				const response = await apiFetch({path: `${sim.restApiPrefix}/forms/missing_form_fields?type=${type}`});
				setHtml( response );
			}
		} ,
		[type]
	);

	return (
		<>
			<InspectorControls>
				<Panel>
					<PanelBody>
						<RadioControl
							label="Type of fields"
							selected={ type }
							options={ [
								{ label: 'Recommended', value: 'recommended' },
								{ label: 'Mandatory', value: 'mandatory' },
								{ label: 'Both', value: 'all' },
							] }
							onChange={ ( value ) => setAttributes({type:value}) }
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
