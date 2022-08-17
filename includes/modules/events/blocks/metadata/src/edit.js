import { __ } from '@wordpress/i18n';
import {useBlockProps} from "@wordpress/block-editor";
import './editor.scss';
import apiFetch from "@wordpress/api-fetch";
import {useState, useEffect} from "@wordpress/element";
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import {TextControl, ToggleControl, Panel, PanelBody, Spinner, CheckboxControl, __experimentalNumberControl as NumberControl, __experimentalInputControl as InputControl} from "@wordpress/components";

const Edit = ({ setAttributes, attributes } ) => {
	const blockProps = useBlockProps();
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);

	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

	const metaFieldValue = meta[ 'event' ];
	const updateMetaValue = ( newValue ) => {
		let newMeta	= JSON.stringify({"allday":newValue});

		console.log(newMeta)

		setMeta( { ...meta,  event:'{"allday":"dfsdfd"}'} );
	};

	return (
		<div { ...blockProps }>
			<InputControl
				label="Meta Block Field"
				value={ meta[ 'event' ] }
				onChange={ updateMetaValue }
			/>
		</div>
	);
}

export default Edit;
