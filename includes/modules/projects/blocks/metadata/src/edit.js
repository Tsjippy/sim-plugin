import { __ } from '@wordpress/i18n';
import './editor.scss';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import {__experimentalInputControl as InputControl} from "@wordpress/components";
import {useBlockProps} from "@wordpress/block-editor";

const Edit = ( ) => {
	const blockProps = useBlockProps();
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);

	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

	const tel				= meta[ 'tel' ];
	const url				= meta[ 'url' ];
	const locationDetails	= meta[ 'location' ] == undefined || meta[ 'location' ] == '' ? {} : JSON.parse(meta[ 'location' ]);

	const updateMetaValue = ( value, key ) => {
		let newMeta	= { ...meta };
		if( key.startsWith( 'location')){
			let subkey	= key.split('-')[1];
			key	= 'location';
			let newLocation	= {};

			if(locationDetails != ''){
				newLocation		= { ...locationDetails };
			}

			newLocation[subkey]	= value;

			if(subkey == 'latitude' || subkey == 'longitude'){
				newLocation	= addressLookup(newLocation);
			}else{
				newLocation	= coordLookup(newLocation);
			}
			
			value	=  JSON.stringify(newLocation);
		}
		newMeta[key]	= value;

		setMeta( newMeta );
	};	

	return (
		<div { ...blockProps }>
			<h2>{__('Location Details')}</h2>
			
			<InputControl
				isPressEnterToChange={true}
				label={__('Phone number')}
				value={ tel }
				onChange={(value) => updateMetaValue(value, 'tel')}
			/>

			<InputControl
				isPressEnterToChange={true}
				label={__('Website url')}
				value={ url }
				onChange={(value) => updateMetaValue(value, 'url')}
			/>

			<InputControl
				isPressEnterToChange={true}
				label={__('Address')}
				value={ locationDetails['address'] }
				onChange={(value) => updateMetaValue(value, 'location-address')}
			/>

			<InputControl
				isPressEnterToChange={true}
				label={__('Latitude')}
				value={ locationDetails['latitude'] }
				onChange={(value) => updateMetaValue(value, 'location-latitude')}
			/>

			<InputControl
				isPressEnterToChange={true}
				label={__('Longitude')}
				value={ locationDetails['longitude'] }
				onChange={(value) => updateMetaValue(value, 'location-longitude')}
			/>
		</div>
	);
}

export default Edit;

