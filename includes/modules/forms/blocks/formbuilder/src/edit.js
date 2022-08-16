import { __ } from '@wordpress/i18n';
import './editor.scss';
import apiFetch from "@wordpress/api-fetch";
import {useState, useEffect} from "@wordpress/element";
import {Panel, PanelBody, __experimentalInputControl as InputControl} from "@wordpress/components";
import {useBlockProps, InspectorControls} from "@wordpress/block-editor";

const Edit = ({attributes, setAttributes}) => {
	const {formname} = attributes;

	const [html, setHtml] = useState('');

	useEffect( 
		async () => {
			if(formname != undefined){
				let response = await apiFetch({path: `/sim/v1/forms/form_builder?formname=${formname}`});
				setHtml( response );
			}
		} ,
		[formname]
	);

	function ShowResult(){
		if(html == '' || !html){
			return <InputControl
				label={__('Form name', 'sim')}
				isPressEnterToChange={true}
				value={formname}
				onChange={(value) => setAttributes({ formname: value })}
			/>
		}

		return wp.element.RawHTML( { children: html })
	}

	return (
		<>
			<InspectorControls>
				<Panel>
					<PanelBody>
						<InputControl
                            label={__('Form name', 'sim')}
							isPressEnterToChange={true}
                            value={formname}
                            onChange={(value) => setAttributes({ formname: value })}
                        />
					</PanelBody>
				</Panel>
			</InspectorControls>
			<div {...useBlockProps()}>
				{ShowResult()}
			</div>
		</>
	);
}

export default Edit;
