import { __ } from '@wordpress/i18n';
import './editor.scss';
import apiFetch from "@wordpress/api-fetch";
import {useState, useEffect} from "@wordpress/element";
import {SearchControl, RadioControl, ToggleControl, Panel, PanelBody, Spinner, __experimentalInputControl as InputControl} from "@wordpress/components";
import { store as coreDataStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import {useBlockProps, InspectorControls} from "@wordpress/block-editor";

const Edit = ({attributes, setAttributes}) => {
	const {id, picture, phone, email, style} = attributes;

	const [ searchTerm, setSearchTerm ]     = useState( '' );

	const { users, hasResolved} = useSelect(
		( select) => {
			// do not show results if not searching
			if ( !searchTerm ) {
				return{
					users: [],
					hasResolved: true
				}
			}

			// find all pages excluding the already selected pages
			const query = {
				exclude : attributes.onlyOn,
				search  : searchTerm,
				per_page: 100,
				context : 'view'
			};

			return {
				users: select( coreDataStore ).getUsers(query),
				hasResolved: select( coreDataStore ).hasFinishedResolution(
					'getUsers',
					[query]
				)
			};
		},
		[searchTerm]
	);

	const BuildRadioControls = function(){
		if ( ! hasResolved ) {
			return(
				<>
				<Spinner />
				<br></br>
				</>
			);
		}
	
		if ( ! users?.length ) {
			return <div> {__('No users found', 'sim')}</div>;
		}

		let options	= users.map( c => (
			{ label: c.name, value: c.id }
		));
		
		return (
			<>
			<RadioControl
				selected= { parseInt(id) }
				options = {options}
				onChange={ ( value ) => {setAttributes({id: value})} }
			/>
			</>
		)
	}

	const [html, setHtml] = useState(< Spinner />);

	useEffect( 
		async () => {
			const response = await apiFetch({path: `${sim.restApiPrefix}/userpage/linked_user_description?id=${id}&picture=${picture}&phone=${phone}&email=${email}&style=${style}`});
			setHtml( response );
		} ,
		[attributes]
	);

	return (
		<>
			<InspectorControls>
				<Panel>
					<PanelBody>
						<i>{__('Use searchbox below to search an user to display', 'sim')}</i>
                        < SearchControl onChange={ setSearchTerm } value={ searchTerm } />
                        < BuildRadioControls />
						<ToggleControl
                            label={__('Show picture', 'sim')}
                            checked={!!attributes.picture}
                            onChange={() => setAttributes({ picture: !attributes.picture })}
                        />
						<ToggleControl
                            label={__('Show phonenumbers', 'sim')}
                            checked={!!attributes.phone}
                            onChange={() => setAttributes({ phone: !attributes.phone })}
                        />
						<ToggleControl
                            label={__('Show e-mail address', 'sim')}
                            checked={!!attributes.email}
                            onChange={() => setAttributes({ email: !attributes.email })}
                        />
						<InputControl
                            isPressEnterToChange={true}
                            label="Optional extra styling"
                            value={ style }
                            onChange={(value) => setAttributes({ style: value })}
                        />
					</PanelBody>
				</Panel>
			</InspectorControls>
			<div {...useBlockProps()}>
				{wp.element.RawHTML( { children: html })}
			</div>
		</>
	);
}

export default Edit;
