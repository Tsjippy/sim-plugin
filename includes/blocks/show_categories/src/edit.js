
import { __ } from '@wordpress/i18n';
import {useBlockProps, InspectorControls} from "@wordpress/block-editor";
import './editor.scss';
import {Panel, PanelBody, ToggleControl, Spinner} from "@wordpress/components";
import { useSelect } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';

const Edit = ({attributes, setAttributes}) => {
	const {count} = attributes;

	const { categories, hasResolved} = useSelect(
		( select) => {
			const query = {
				per_page: 100,
				orderby : 'title'
			};

			// Find all selected pages
			const args = [ 'taxonomy', 'category'];

			return {
				categories: select( coreDataStore ).getEntityRecords(
					...args
				),
				hasResolved: select( coreDataStore ).hasFinishedResolution(
					'getEntityRecords',
					args
				)
			};
		},
		[]
	);

	const BuildCategoryList = function(){
		if ( ! hasResolved ) {
			return(
				<>
				<Spinner />
				<br></br>
				</>
			);
		}
	
		if ( ! categories?.length ) {
			return <div> {__('No categories found', 'sim')}</div>;
		}
		
		return (
			<ul>
			{categories?.map( ( category ) => {
				let nr	= '';
				if(count){
					nr	= <>  (<span className='cat-count'>{category.count}</span>)</>;
				}
				return (<li key={category.id}><a href={category.link}>{category.name}{nr}</a></li>);
			} )}
			</ul>
		)
	}

	return (
		<>
			<InspectorControls>
				<Panel>
					<PanelBody>
						<ToggleControl
                            label={__('Show categories count', 'sim')}
                            checked={!!attributes.count}
                            onChange={() => setAttributes({ count: !attributes.count })}
                        />
					</PanelBody>
				</Panel>
			</InspectorControls>
			<div {...useBlockProps()}>
				<aside className='event'>
					<h4 className="title">Categories</h4>
					<div className="upcoming-events-wrapper">
						{BuildCategoryList()}
					</div>
				</aside>
			</div>
		</>
	);
}

export default Edit;
