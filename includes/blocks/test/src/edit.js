import { __ } from '@wordpress/i18n';
import {useBlockProps} from "@wordpress/block-editor";
import './editor.scss';
import apiFetch from "@wordpress/api-fetch";
import {useState, useEffect} from "@wordpress/element";
const { PanelBody, ToggleControl, CheckboxControl }      = wp.components;
import { SearchControl, Spinner, __experimentalInputControl as InputControl } from '@wordpress/components';
const { InspectorControls }             = wp.blockEditor;

const Edit = () => {

	const [html, setHtml] = useState([]);

	const [ searchTerm, setSearchTerm ]     = useState( '' );

	const Search	= () => {
		return(
			< SearchControl onChange={ setSearchTerm } value={ searchTerm } />
		)
	}

	return (
		<>
			<Fragment>
				<InspectorControls>
					<PanelBody title={ __( 'Block Visibility', 'sim' ) }>
						{Search()}
					</PanelBody>
				</InspectorControls>
			</Fragment>
			<div {...useBlockProps()}>
				This is the test blcok
			</div>
		</>
	);
}

export default Edit;
