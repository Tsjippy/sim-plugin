import { __ } from '@wordpress/i18n';
import './editor.scss';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import {SelectControl , __experimentalInputControl as InputControl} from "@wordpress/components";
import {useBlockProps} from "@wordpress/block-editor";
import apiFetch from "@wordpress/api-fetch";
import {useState, useEffect} from "@wordpress/element";

const Edit = ( ) => {
	const blockProps = useBlockProps();
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);

	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );
	const [ministries, setMinistries] = useState([]);

	const number			= meta[ 'number' ];
	const url				= meta[ 'url' ];
	const ministry			= meta[ 'ministry'];
	const manager			= meta[ 'manager' ] == undefined || meta[ 'manager' ] == '' ? {} : JSON.parse(meta[ 'manager' ]);

	useEffect( 
		async () => {
			const response = await apiFetch({path: '/sim/v2/projects/ministries?slug=ministry'});

			let options	= response.map( c => (
				{ label: c.post_title, value: c.ID }
			));

			options.unshift({ label: __('Please select a ministry', 'sim'), value: '' });

			setMinistries( options );
		} ,
		[]
	);

	const updateMetaValue = ( value, key ) => {
		let newMeta	= { ...meta };
		if( key.startsWith( 'manager')){
			let subkey	= key.split('-')[1];
			key	= 'manager';
			let newManager	= {};

			if(manager != ''){
				newManager		= { ...manager };
			}

			newManager[subkey]	= value;
			
			value	=  JSON.stringify(newManager);
		}
		newMeta[key]	= value;

		setMeta( newMeta );
	};

	return (
		<div { ...blockProps }>
			<h2>{__('Project Details')}</h2>

			<InputControl
				isPressEnterToChange={true}
				label={__('Project number')}
				value={ number }
				onChange={(value) => updateMetaValue(value, 'number')}
			/>

			<InputControl
				isPressEnterToChange={true}
				label={__('Manager name')}
				value={ manager['name'] }
				onChange={(value) => updateMetaValue(value, 'manager-name')}
			/>
			
			<InputControl
				isPressEnterToChange={true}
				label={__('Phone number')}
				value={ manager['tel'] }
				onChange={(value) => updateMetaValue(value, 'manager-tel')}
			/>

			<InputControl
				isPressEnterToChange={true}
				label={__('E-mail address')}
				value={ manager['email'] }
				onChange={(value) => updateMetaValue(value, 'manager-email')}
			/>

			<InputControl
				isPressEnterToChange={true}
				label={__('Website url')}
				value={ url }
				onChange={(value) => updateMetaValue(value, 'url')}
			/>

			<SelectControl
				label="Ministry"
				value={ ministry }
				options={ ministries }
				onChange={ ( value ) => updateMetaValue(value, 'ministry')}
				__nextHasNoMarginBottom
			/>
		</div>
	);
}

export default Edit;

