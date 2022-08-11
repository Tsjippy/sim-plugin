import { __ } from '@wordpress/i18n';
import {useBlockProps, InspectorControls} from "@wordpress/block-editor";
import './editor.scss';
import {useState, useEffect} from "@wordpress/element";
import {Panel,SearchControl, TextControl,PanelBody, Spinner, CheckboxControl, FocusableIframe} from "@wordpress/components";
import { useSelect } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';
import { decodeEntities } from '@wordpress/html-entities';	


const Edit = ({attributes, setAttributes}) => {
	const {page} = attributes;

	const [ searchTerm, setSearchTerm ]     = useState( '' );
	const [ embededPage, setEmbededPage ]    = useState( '' );

	const { pages, resolved } = useSelect(
		( select) => {
			// do not show results if not searching
			if ( !searchTerm ) {
				return{
					pages: [],
					resolved: true
				}
			}

			// find all pages excluding the already selected pages
			const query = {
				search  : searchTerm,
				per_page: 100,
				orderby : 'relevance'
			};

			const pagesArgs         = [ 'postType', 'page', query ];

			return {
				pages: select( coreDataStore ).getEntityRecords(
					...pagesArgs
				),
				resolved: select( coreDataStore ).hasFinishedResolution(
					'getEntityRecords',
					pagesArgs
				)
			};
		},
		[ searchTerm ]
	);

	const PageSelected	= function(selected){
		if(selected){
			setAttributes({ page: this });
		}else{
			setAttributes({ page: null });
		}
	}

	const BuildCheckboxControls = function(){
		if(page == undefined){
			return '';
		}
		return (
			<>
			Currently embeded page:
			<CheckboxControl
				label		= {decodeEntities( page.title.rendered )}
				onChange	= {PageSelected.bind(page)}
				checked		= {true}
			/>
			</>
		)
	}

	const SearchResults	= function({ hasResolved, pageList }){
		if ( ! hasResolved ) {
			return(
				<>
				<Spinner />
				<br></br>
				</>
			);
		}
	
		if ( ! pageList?.length ) {
			if ( !searchTerm ) {
				return '';
			}
			return <div> {__('No search results', 'sim')}</div>;
		}
		
		return pages?.map( ( p ) => {
		
			return (
			<CheckboxControl
				label		= {decodeEntities( p.title.rendered )}
				onChange	= {PageSelected.bind(p)}
				checked		= {false}
			/>)
		} )
	}

	const { pageContent, pageResolved } = useSelect(
		( select) => {

			if(attributes.page == undefined){
				return {
					pageContent:	false,
					pageResolved:	false
				}
			}

			const pagesArgs         = [ 'postType', 'page', { include : [attributes.page.id] } ];

			return {
				pageContent: select( coreDataStore ).getEntityRecords(
					...pagesArgs
				),
				pageResolved: select( coreDataStore ).hasFinishedResolution(
					'getEntityRecords',
					pagesArgs
				)
			};
		},
		[ attributes.page ]
	);

	useEffect(
		() => {
			if(!pageContent){
				setEmbededPage(__('Please select a page...', 'sim'));
			}else{
				setEmbededPage(wp.element.RawHTML( { children: pageContent[0].content.raw }));
			}
			
		},
		[pageResolved]
	)

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Page Embed Settings', 'sim' ) }>
					< BuildCheckboxControls  />
					<i>{__('Use searchbox below to search for a page', 'sim')}</i>
					< SearchControl onChange={setSearchTerm} value={ searchTerm } autoFocus={true}/>
					< SearchResults hasResolved= {resolved} pageList= {pages} />
				</PanelBody>
			</InspectorControls>
			<div {...useBlockProps()}>
				< SearchControl onChange={setSearchTerm} value={ searchTerm } autoFocus={true}/>
				< SearchResults hasResolved= {resolved} pageList= {pages} />
				{embededPage}
			</div>
		</>
	);
}

export default Edit;
