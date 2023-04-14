import { __ } from '@wordpress/i18n';
import './editor.scss';
import apiFetch from "@wordpress/api-fetch";
import {useState, useEffect} from "@wordpress/element";
import {Panel, PanelBody, __experimentalInputControl as InputControl} from "@wordpress/components";
import {useBlockProps, InspectorControls} from "@wordpress/block-editor";

const Edit = ({attributes, setAttributes, context}) => {

	const {formname} = attributes;
	const {postId} = context;

	const [html, setHtml] = useState('');

	useEffect( 
		() => {
			async function getHTML(){
				if(formname != undefined){
					let response = await apiFetch({path: `${sim.restApiPrefix}/forms/form_builder?formname=${formname}&post=${postId}`});
					setHtml( response );
				}
			}
			getHTML();
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

		return wp.element.RawHTML( { children: html.html })
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
