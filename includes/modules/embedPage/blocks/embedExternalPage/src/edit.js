import { __ } from '@wordpress/i18n';
import {useBlockProps, InspectorControls} from "@wordpress/block-editor";
import './editor.scss';
import {useState, useEffect} from "@wordpress/element";
import {TextControl,PanelBody, Spinner} from "@wordpress/components";
import { useSelect } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';
import { decodeEntities } from '@wordpress/html-entities';	


const Edit = ({attributes, setAttributes}) => {
	const {url} = attributes;

	const [ embededPage, setEmbededPage ]   = useState( <Spinner/> );

	useEffect(
		() => {
			if(url == ''){
				setEmbededPage(
					<>
					{ __('Please give an url', 'sim') }
					{ UrlInput() }
					</>
				);
			}else{
				setEmbededPage(<iframe src={ url }></iframe>);
			}
			
		},
		[url]
	);

	const UrlInput	= function(){
		return (
			<TextControl
				label		= "Page url"
				value		= { url }
				onChange	= { (val) => setAttributes({url: val}) }
			/>
		);
	}

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Page Embed Settings', 'sim' ) }>
					{UrlInput}
				</PanelBody>
			</InspectorControls>
			<div {...useBlockProps()}>
				{ embededPage }
				
			</div>
		</>
	);
}

export default Edit;
