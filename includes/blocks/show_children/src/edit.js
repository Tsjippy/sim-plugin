
import { __ } from '@wordpress/i18n';
import {useBlockProps, InspectorControls} from "@wordpress/block-editor";
import './editor.scss';
import {Panel, PanelBody, ToggleControl, Spinner, __experimentalNumberControl as NumberControl} from "@wordpress/components";
import { useSelect } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';

const Edit = ({attributes, setAttributes}) => {
	const {grandchildren} = attributes;

	return (
		<>
			<InspectorControls>
				<Panel>
					<PanelBody>
						<ToggleControl
                            label={__('Show grandchildren', 'sim')}
                            checked={!!attributes.grandchildren}
                            onChange={() => setAttributes({ grandchildren: !attributes.grandchildren })}
                        />
						<ToggleControl
                            label={__('Show parents', 'sim')}
                            checked={!!attributes.parents}
                            onChange={() => setAttributes({ parents: !attributes.parents })}
                        />
						<NumberControl
							label		= {__('Show grantparents', 'sim')}
							value		= {attributes.grantparents}
							onChange	= {(val) => setAttributes({grantparents: parseInt(val)})}
							min			= {1}
							max			= {12}
						/>
					</PanelBody>
				</Panel>
			</InspectorControls>
			<div {...useBlockProps()}>
				A list of child pages will show here
			</div>
		</>
	);
}

export default Edit;
