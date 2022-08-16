import { __ } from '@wordpress/i18n';
import './editor.scss';
import apiFetch from "@wordpress/api-fetch";
import {useState, useEffect} from "@wordpress/element";
import {Panel, PanelBody, Spinner, CheckboxControl, __experimentalNumberControl as NumberControl, __experimentalInputControl as InputControl} from "@wordpress/components";
import {useBlockProps, InspectorControls} from "@wordpress/block-editor";

const Edit = ({attributes, setAttributes}) => {
	const {name} = attributes;

	const [html, setHtml] = useState('');

	useEffect( 
		async () => {
			if(name != undefined){
				const response = await apiFetch({path: `/sim/v1/forms/form_builder?name=${name}`});
				setHtml( response );
			}
		} ,
		[name]
	);

	function ShowResult(){
		if(html == '' || !html){
			return <InputControl
				label={__('Form name', 'sim')}
				isPressEnterToChange={true}
				value={name}
				onChange={(value) => setAttributes({ name: value })}
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
                            value={name}
                            onChange={(value) => setAttributes({ name: value })}
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
