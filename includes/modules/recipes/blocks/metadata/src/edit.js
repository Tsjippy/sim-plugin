import { __ } from '@wordpress/i18n';
import './editor.scss';
import apiFetch from "@wordpress/api-fetch";
import {useState, useEffect} from "@wordpress/element";
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import {TextareaControl, TimePicker, __experimentalRadio as Radio, __experimentalRadioGroup as RadioGroup, Button, Autocomplete, Snackbar, SearchControl,  DatePicker, RadioControl, DateTimePicker, TextControl, ToggleControl, Panel, PanelBody, Spinner, CheckboxControl, __experimentalNumberControl as NumberControl, __experimentalInputControl as InputControl} from "@wordpress/components";
import { store as coreDataStore } from '@wordpress/core-data';
import {useBlockProps, InspectorControls} from "@wordpress/block-editor";

const Edit = ({ setAttributes, attributes } ) => {
	const blockProps = useBlockProps();
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);

	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

	const ingredients		= meta[ 'ingredients' ];
	const time_needed		= meta[ 'time_needed' ];
	const serves			= meta[ 'serves' ];

	const updateMetaValue = ( value, key ) => {
		let newMeta	= { ...meta };
		newMeta[key]	= value;
		setMeta( newMeta );
	};	

	return (
		<div { ...blockProps }>
			<h2>{__('Recipe Details')}</h2>
			
			<h3>{__('Ingredients')}</h3>
			<TextareaControl
				label="One per line"
				value={ ingredients }
				onChange={(value) => updateMetaValue(value, 'ingredients')}
			/>

			<h3>{__('Time needed')}</h3>
			<NumberControl
				value={ time_needed }
				onChange={(value) => updateMetaValue(value, 'time_needed')}
			/>

			<h3>{__('Serves')}</h3>
			<NumberControl
				value={ serves }
				onChange={(value) => updateMetaValue(value, 'serves')}
			/>
		</div>
	);
}

export default Edit;

