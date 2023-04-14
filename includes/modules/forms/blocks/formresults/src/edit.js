import { __ } from '@wordpress/i18n';
import {useBlockProps, InspectorControls} from "@wordpress/block-editor";
import './editor.scss';
import apiFetch from "@wordpress/api-fetch";
import {useState, useEffect} from "@wordpress/element";
import {ToggleControl, SelectControl, Panel, PanelBody, Spinner} from "@wordpress/components";

const Edit = ({attributes, setAttributes}) => {
	let {formid, onlyOwn, archived, tableid} = attributes;

	const [forms, setForms] = useState([]);

	useEffect( 
		() => {
			async function getForms(){
				const fetchedForms = await apiFetch({path: sim.restApiPrefix+'/forms/get_forms'});

				let options	= fetchedForms.map( c => (
					{ label: c.name, value: c.id }
				));

				options.unshift({ label: __('Please select a form', 'sim'), value: '' });

				setForms( options );
			}
			getForms();
		} , 
		[]
	);

	const [html, setHtml] = useState(< Spinner />);

	useEffect( 
		() => {
			async function getHtml() {
				if(formid != undefined){
					setHtml( < Spinner /> );

					// add shortcode id if not given
					if(tableid == undefined){
						tableid = await apiFetch({path: `${sim.restApiPrefix}/forms/add_form_table?formid=${formid}`});
						setAttributes({tableid: tableid});
					}

					let response = await apiFetch({path: `${sim.restApiPrefix}/forms/show_form_results?formid=${formid}&tableid=${String(tableid)}`});
					setHtml( response );
				}
			}
			getHtml();
		} ,
		[formid]
	);

	function dropDown(){
		return <>
			{__('Select the form you want to show the results of', 'sim')}
			<SelectControl
				label	= {__('Form to show', 'sim')}
				value={ formid }
				options={ forms }
				onChange={ (value) => {setAttributes({formid: value})} }
				__nextHasNoMarginBottom
			/>
		</>
	}

	function ShowResult(){
		if(html == '' || !html){
			return dropDown();
		}

		return wp.element.RawHTML( { children: html })
	}

	return (
		<>
			<InspectorControls>
				<Panel>
					<PanelBody>
						{dropDown()}
						<ToggleControl
                            label={__('Show only personal entries', 'sim')}
                            checked={!!attributes.onlyOwn}
                            onChange={() => setAttributes({ onlyOwn: !attributes.onlyOwn })}
                        />
						<ToggleControl
                            label={__('Show archived entries', 'sim')}
                            checked={!!attributes.archived}
                            onChange={() => setAttributes({ archived: !attributes.archived })}
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
