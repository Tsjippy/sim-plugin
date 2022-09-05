import { __ } from '@wordpress/i18n';
import {useBlockProps, InspectorControls} from "@wordpress/block-editor";
import './editor.scss';
import apiFetch from "@wordpress/api-fetch";
import {useState, useEffect} from "@wordpress/element";
import {Panel, PanelBody, Spinner, ToggleControl, CheckboxControl, __experimentalNumberControl as NumberControl, __experimentalInputControl as InputControl} from "@wordpress/components";

const Edit = ({attributes, setAttributes}) => {
	const {roles, key, folder, family} = attributes;

	if(roles == undefined){
		setAttributes({ roles: [] });
	}

	const [html, setHtml] = useState(<Spinner />);
	const [availableRoles, setAvailableRoles] = useState(<Spinner />);

	useEffect( async () => {
		setHtml(<Spinner />);
		const result = await apiFetch({path: `${sim.restApiPrefix}/bulkchange/bulk_change_meta_html`});
		setHtml(result);
	} , []);

	useEffect( async () => {
		const result = await apiFetch({path: `${sim.restApiPrefix}/user_roles`});
		setAvailableRoles(result);
	} , []);


	const RoleSelected	= function(selected){
		if(selected){
			setAttributes({ roles: [...roles, this] });
		}else{
			setAttributes({ roles: roles.filter(role => {return role != this}) });
		}
	}

	const RoleControls	= function(){
		return (			
			Object.entries(availableRoles).map(([k, v]) => {
				return (<CheckboxControl
					label		= {v}
					onChange	= {RoleSelected.bind(k)}
					checked		= {roles.includes(k)}
				/>)
			})
		)
	}

	return (
		<>
			<InspectorControls>
				<Panel>
					<PanelBody>
						Select roles with permission to use
						< RoleControls />

						<InputControl
							label={__('Which meta key to change', 'sim')}
							isPressEnterToChange={true}
							value={ key }
							onChange={(value) => setAttributes({ key: value })}
						/>

						In case of a file upload stored in meta:
						<InputControl
							label={__('Which folder to change', 'sim')}
							isPressEnterToChange={true}
							value={ folder }
							onChange={(value) => setAttributes({ folder: value })}
						/>

						<ToggleControl
                            label={__('Apply also to family members', 'sim')}
                            checked={!!attributes.family}
                            onChange={() => setAttributes({ family: !attributes.family })}
                        />
					</PanelBody>
				</Panel>
			</InspectorControls>
			<div {...useBlockProps()}>
				{html}
			</div>
		</>
	);
}

export default Edit;
