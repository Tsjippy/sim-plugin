
import { __ } from '@wordpress/i18n';
import {useBlockProps, InspectorControls} from "@wordpress/block-editor";
import './editor.scss';
import {Panel, PanelBody, ToggleControl, Spinner, __experimentalNumberControl as NumberControl, SelectControl} from "@wordpress/components";
import {useState, useEffect} from "@wordpress/element";
import apiFetch from "@wordpress/api-fetch";

const Edit = ({attributes, setAttributes, context}) => {
	const {title, listtype, grandchildren, parents, grantparents} = attributes;
	const {postId} = context;
	
	const [html, setHtml] = useState(< Spinner />);

	useEffect( 
		() => {
			async function getHtml(){
				setHtml( < Spinner /> );
				const response = await apiFetch({
					path: sim.restApiPrefix+'/show_children',
					method: 'POST',
					data: { 
						title: title,
						listtype: listtype,
						grandchildren:grandchildren,
						parents:parents,
						grantparents:grantparents,
						postid: postId
					},
				});
				setHtml( response );
			}
			getHtml();
		 } ,
		[attributes]
	);

	return (
		<>
			<InspectorControls>
				<Panel>
					<PanelBody>
						<ToggleControl
                            label={__('Show title', 'sim')}
                            checked={!!attributes.title}
                            onChange={() => setAttributes({ title: !attributes.title })}
                        />
						<SelectControl
							label	="List style"
							value	={ attributes.listtype }
							options	={ [		
								{ label: 'none', value: 'none' },
								{ label: 'disc', value: 'disc' },
								{ label: 'circle', value: 'circle' },
								{ label: 'square', value: 'square' },
								{ label: 'decimal', value: 'decimal' },
								{ label: 'decimal-leading-zero', value: 'decimal-leading-zero' },
								{ label: 'lower-roman', value: 'lower-roman' },
								{ label: 'upper-roman', value: 'upper-roman' },
								{ label: 'lower-greek', value: 'lower-greek' },
								{ label: 'lower-latin', value: 'lower-latin' },
								{ label: 'upper-latin', value: 'upper-latin' },
								{ label: 'armenian', value: 'armenian' },
								{ label: 'georgian', value: 'georgian' },
								{ label: 'lower-alpha', value: 'lower-alpha' },
								{ label: 'upper-alpha', value: 'upper-alpha' }
							] }
							onChange={ ( value ) => setAttributes({ listtype: value}) }
							__nextHasNoMarginBottom
						/>
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
				{wp.element.RawHTML( { children: html })}
			</div>
		</>
	);
}

export default Edit;
