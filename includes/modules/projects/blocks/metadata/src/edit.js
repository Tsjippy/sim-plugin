import { __ } from '@wordpress/i18n';
import './editor.scss';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import {__experimentalInputControl as InputControl} from "@wordpress/components";
import {useBlockProps} from "@wordpress/block-editor";

const addressLookup	= (newLocation) => {
	if(typeof google == 'undefined'){
		return newLocation;
	}

	var geocoder = new google.maps.Geocoder();
	if (newLocation['latitude'] != undefined && newLocation['longitude'] != undefined){
		var latlng = { lat: parseFloat(newLocation['latitude']), lng: parseFloat(newLocation['longitude']) };
		
		geocoder.geocode(
			{ location: latlng }, 
			function(results, status) {
				console.log(results)
				if (status === "OK" && results[0]) {
					newLocation['address'] = results[0].formatted_address;
				}
			}
		);
	}

	return newLocation;
}

function coordLookup(newLocation){
	if(typeof google == 'undefined'){
		return newLocation;
	}
	
	//Geocode address to coordinates
	var geocoder = new google.maps.Geocoder();
	geocoder.geocode({ address: newLocation['address']}, 
		function(results, status) {
			if (status === "OK") {
				console.log(results)
				newLocation['latitude'] 	= (results[0].geometry.location.lat()).toFixed(7);
				newLocation['longitude'] 	= (results[0].geometry.location.lng()).toFixed(7);
			}
		}
	);

	return newLocation;
}

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

